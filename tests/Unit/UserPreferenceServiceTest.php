<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserPreferenceService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_it_returns_defaults_for_guest(): void
    {
        $service = app(UserPreferenceService::class);

        $this->assertSame('system', $service->resolve(null)['theme']);
        $this->assertSame('blue', $service->resolve(null)['appearance']['light']['accent']);
        $this->assertSame('dark', $service->resolve(null)['appearance']['dark']['sidebar']);
    }

    public function test_it_persists_user_preferences_per_color_mode(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $service = app(UserPreferenceService::class);

        $saved = $service->update($user, [
            'theme' => 'system',
            'appearance' => [
                'light' => [
                    'accentSource' => 'preset',
                    'accent' => 'blue',
                    'customAccentColor' => '#0f766e',
                    'sidebar' => 'light',
                    'topbar' => 'default',
                    'customSidebarColor' => null,
                    'customTopbarColor' => null,
                ],
                'dark' => [
                    'accentSource' => 'custom',
                    'accent' => 'teal',
                    'customAccentColor' => '#112233',
                    'sidebar' => 'dark',
                    'topbar' => 'glass',
                    'customSidebarColor' => null,
                    'customTopbarColor' => '#ffffff',
                ],
            ],
        ]);

        $this->assertSame('blue', $saved['appearance']['light']['accent']);
        $this->assertSame('#112233', $saved['appearance']['dark']['customAccentColor']);
        $this->assertSame('dark', $service->resolve($user->fresh())['appearance']['dark']['sidebar']);
    }

    public function test_it_persists_theme_preset(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $service = app(UserPreferenceService::class);

        $service->update($user, [
            'theme' => 'system',
            'appearance' => [
                'themePreset' => 'cupertino',
                'light' => $service->defaultAppearanceState(),
                'dark' => array_merge($service->defaultAppearanceState(), ['sidebar' => 'dark']),
            ],
        ]);

        $resolved = $service->resolve($user->fresh());

        $this->assertSame('cupertino', $resolved['themePreset']);
    }

    public function test_it_migrates_legacy_flat_appearance(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'preferences' => [
                'theme' => 'light',
                'appearance' => [
                    'accentSource' => 'preset',
                    'accent' => 'rose',
                    'customAccentColor' => '#0f766e',
                    'sidebar' => 'minimal',
                    'topbar' => 'solid',
                    'customSidebarColor' => null,
                    'customTopbarColor' => null,
                ],
            ],
        ]);

        $resolved = app(UserPreferenceService::class)->resolve($user);

        $this->assertSame('rose', $resolved['appearance']['light']['accent']);
        $this->assertSame('rose', $resolved['appearance']['dark']['accent']);
        $this->assertSame('minimal', $resolved['appearance']['light']['sidebar']);
    }
}
