<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sales\Concerns\CreatesPosFixtures;
use Tests\TestCase;

class PosAccessTest extends TestCase
{
    use CreatesPosFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_pos(): void
    {
        $this->get(route('pos.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_pos_access_is_forbidden(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $user->assignRole('Report Viewer');

        $this->actingAs($user)->get(route('pos.index'))->assertForbidden();
    }

    public function test_cashier_with_pos_access_can_view_pos(): void
    {
        ['cashier' => $cashier] = $this->createPosFixtures();

        $this->actingAs($cashier)->get(route('pos.index'))->assertOk();
    }
}
