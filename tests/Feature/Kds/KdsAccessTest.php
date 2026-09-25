<?php

namespace Tests\Feature\Kds;

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KdsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_kds(): void
    {
        $this->get(route('kds.index'))->assertRedirect(route('login'));
    }

    public function test_cashier_without_kds_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $user->assignRole('Cashier');

        $this->actingAs($user)->get(route('kds.index'))->assertForbidden();
    }

    public function test_kitchen_staff_can_open_kds_setup(): void
    {
        $store = Store::factory()->create(['status' => 'active']);
        $user = User::factory()->create([
            'company_id' => $store->company_id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Kitchen Staff');
        $user->stores()->sync([$store->id]);

        $this->actingAs($user)
            ->get(route('kds.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('KDS/Setup'));
    }
}
