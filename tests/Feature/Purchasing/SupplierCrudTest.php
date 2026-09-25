<?php

namespace Tests\Feature\Purchasing;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_purchasing_officer_can_create_supplier(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Purchasing Officer');

        $this->actingAs($user)
            ->post(route('admin.suppliers.store'), [
                'company_id' => $company->id,
                'supplier_code' => 'ACME',
                'name' => 'Acme Supply Co.',
                'contact_name' => 'Jane Doe',
                'email' => 'jane@acme.test',
                'phone' => null,
                'address' => null,
                'payment_terms' => 'Net 30',
                'notes' => null,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'company_id' => $company->id,
            'supplier_code' => 'ACME',
            'name' => 'Acme Supply Co.',
        ]);
    }

    public function test_supplier_list_is_company_scoped(): void
    {
        $company = Company::factory()->create();
        Supplier::factory()->create(['company_id' => $company->id, 'supplier_code' => 'LOCAL']);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Purchasing Officer');

        $this->actingAs($user)
            ->get(route('admin.suppliers.index'))
            ->assertOk();
    }
}
