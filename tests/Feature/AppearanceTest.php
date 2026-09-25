<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_authenticated_user_can_open_appearance_settings(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Cashier');

        $this->actingAs($user)
            ->get(route('appearance.edit'))
            ->assertOk();
    }

    public function test_authenticated_user_can_save_appearance_preferences_per_color_mode(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Cashier');

        $payload = [
            'theme' => 'system',
            'appearance' => [
                'light' => [
                    'accentSource' => 'preset',
                    'accent' => 'blue',
                    'customAccentColor' => '#0f766e',
                    'sidebar' => 'minimal',
                    'topbar' => 'solid',
                    'customSidebarColor' => null,
                    'customTopbarColor' => null,
                ],
                'dark' => [
                    'accentSource' => 'preset',
                    'accent' => 'violet',
                    'customAccentColor' => '#0f766e',
                    'sidebar' => 'dark',
                    'topbar' => 'accent',
                    'customSidebarColor' => null,
                    'customTopbarColor' => null,
                ],
            ],
        ];

        $this->actingAs($user)
            ->from(route('appearance.edit'))
            ->put(route('appearance.update'), $payload)
            ->assertRedirect(route('appearance.edit'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('system', $user->preferences['theme']);
        $this->assertSame('blue', $user->preferences['appearance']['light']['accent']);
        $this->assertSame('violet', $user->preferences['appearance']['dark']['accent']);
        $this->assertSame('minimal', $user->preferences['appearance']['light']['sidebar']);
        $this->assertSame('dark', $user->preferences['appearance']['dark']['sidebar']);
    }
}
