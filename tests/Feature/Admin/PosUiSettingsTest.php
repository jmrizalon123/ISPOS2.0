<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosUiSettingsTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_view_pos_ui_settings_page(): void
    {
        ['store' => $store, 'cashier' => $cashier] = $this->createPosFixtures();
        $cashier->givePermissionTo('settings.view');

        $this->actingAs($cashier)
            ->get(route('admin.pos-ui.edit', ['company_id' => $store->company_id]))
            ->assertOk();
    }

    public function test_admin_can_save_store_pos_ui_override(): void
    {
        ['store' => $store, 'cashier' => $cashier] = $this->createPosFixtures();
        $cashier->givePermissionTo(['settings.view', 'settings.update']);

        $this->actingAs($cashier)
            ->put(route('admin.pos-ui.update'), [
                'scope' => 'store',
                'scope_id' => $store->id,
                'ui_layout' => 'wholesale',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'scope' => Setting::SCOPE_STORE,
            'scope_id' => $store->id,
            'key' => 'pos.ui_layout',
        ]);
    }

    public function test_user_without_settings_permission_cannot_update_pos_ui(): void
    {
        ['store' => $store] = $this->createPosFixtures();
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Report Viewer');

        $this->actingAs($user)
            ->put(route('admin.pos-ui.update'), [
                'scope' => 'store',
                'scope_id' => $store->id,
                'ui_layout' => 'wholesale',
            ])
            ->assertForbidden();
    }
}
