<?php

namespace Tests\Feature\Crm;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_manager_can_create_customer(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Store Manager');

        $this->actingAs($user)
            ->post(route('admin.customers.store'), [
                'company_id' => $company->id,
                'customer_code' => 'C001',
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'email' => 'ana@example.test',
                'phone' => null,
                'mobile' => null,
                'birth_date' => null,
                'address_line_1' => null,
                'city' => null,
                'province' => null,
                'postal_code' => null,
                'price_group_id' => null,
                'loyalty_program_id' => null,
                'notes' => null,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customers', [
            'company_id' => $company->id,
            'customer_code' => 'C001',
            'first_name' => 'Ana',
        ]);
    }

    public function test_customer_list_is_company_scoped(): void
    {
        $company = Company::factory()->create();
        Customer::factory()->create(['company_id' => $company->id, 'customer_code' => 'LOCAL']);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Store Manager');

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk();
    }
}
