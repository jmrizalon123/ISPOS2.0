<?php

namespace Tests\Feature\Reporting;

use App\Models\Company;
use App\Models\PosShift;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesRegisterExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_accountant_can_export_sales_register_csv(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $register = Register::factory()->create(['store_id' => $store->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Accountant');
        $user->stores()->sync([$store->id]);

        $shift = PosShift::factory()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'user_id' => $user->id,
        ]);

        Sale::factory()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'pos_shift_id' => $shift->id,
            'user_id' => $user->id,
            'sale_number' => 'MAIN-TEST-0001',
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.reports.sales-register.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('MAIN-TEST-0001', $response->streamedContent());
    }
}
