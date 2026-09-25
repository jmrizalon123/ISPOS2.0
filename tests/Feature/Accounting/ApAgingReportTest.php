<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VendorBill;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApAgingReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_ap_aging_lists_open_bill_balances(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Accountant');

        VendorBill::factory()->create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'amount_due' => 150,
            'amount_paid' => 50,
            'status' => 'partial',
            'due_date' => now()->subDays(15)->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.accounting.ap-aging.index'))
            ->assertOk();

        $rows = collect($response->viewData('page')['props']['rows']);
        $this->assertCount(1, $rows);
        $this->assertSame('100.0000', $rows->first()->balance_due);
        $this->assertSame('1_30', $rows->first()->bucket);
    }
}
