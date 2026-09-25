<?php

namespace Tests\Feature\Auth;

use App\Domains\Identity\Services\TwoFactorAuthenticationService;
use App\Domains\Identity\Services\TwoFactorRememberDeviceService;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_begin_and_confirm_two_factor_setup(): void
    {
        $user = User::factory()->headOffice()->create();

        $this->actingAs($user)
            ->post(route('two-factor.enable'))
            ->assertRedirect();

        $user->refresh();

        $this->assertTrue($user->hasPendingTwoFactorAuthentication());
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());

        $code = (new Google2FA)->getCurrentOtp((string) $user->two_factor_secret);

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertRedirect()
            ->assertSessionHas('two_factor_recovery_codes');

        $user->refresh();

        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
        $this->assertCount(TwoFactorAuthenticationService::RECOVERY_CODE_COUNT, $user->two_factor_recovery_codes ?? []);
    }

    public function test_login_requires_two_factor_challenge_when_enabled(): void
    {
        $user = User::factory()->headOffice()->create([
            'email' => 'secured@example.com',
            'password' => 'password',
        ]);

        $secret = (new Google2FA)->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['ABCD-EFGH-IJKL'],
        ])->save();

        $this->post(route('login'), [
            'email' => 'secured@example.com',
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this->assertGuest();

        $code = (new Google2FA)->getCurrentOtp($secret);

        $this->withSession(['login.id' => $user->id, 'login.remember' => false])
            ->post(route('two-factor.login.store'), ['code' => $code])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_remembered_device_skips_two_factor_until_expired(): void
    {
        $user = User::factory()->headOffice()->create([
            'email' => 'remember@example.com',
            'password' => 'password',
        ]);

        $secret = (new Google2FA)->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['ABCD-EFGH-IJKL'],
        ])->save();

        $this->post(route('login'), [
            'email' => 'remember@example.com',
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $code = (new Google2FA)->getCurrentOtp($secret);

        $challengeResponse = $this->withSession(['login.id' => $user->id, 'login.remember' => false])
            ->post(route('two-factor.login.store'), [
                'code' => $code,
                'remember_device' => true,
            ])
            ->assertRedirect(route('dashboard'));

        $rememberCookie = $challengeResponse->getCookie(TwoFactorRememberDeviceService::COOKIE_NAME);
        $this->assertNotNull($rememberCookie);

        Auth::logout();

        $this->withCookie($rememberCookie->getName(), $rememberCookie->getValue())
            ->post(route('login'), [
                'email' => 'remember@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        Auth::logout();

        $this->travel(8)->days();

        $this->withCookie($rememberCookie->getName(), $rememberCookie->getValue())
            ->post(route('login'), [
                'email' => 'remember@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
    }

    public function test_user_can_disable_two_factor_with_password(): void
    {
        $user = User::factory()->headOffice()->create([
            'password' => 'password',
        ]);

        $secret = (new Google2FA)->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['ABCD-EFGH-IJKL'],
        ])->save();

        $this->actingAs($user)
            ->delete(route('two-factor.disable'), ['password' => 'password'])
            ->assertRedirect();

        $user->refresh();

        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
        $this->assertNull($user->two_factor_secret);
    }

    public function test_profile_page_includes_two_factor_setup_props(): void
    {
        $user = User::factory()->headOffice()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('twoFactor.enabled')
                ->has('twoFactor.pending')
            );
    }
}
