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

class SalesSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_branch_manager_can_view_sales_summary(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $register = Register::factory()->create(['store_id' => $store->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Branch Manager');
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
            'status' => 'completed',
            'grand_total' => 500,
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.reports.sales-summary.index'))
            ->assertOk();
    }

    public function test_summary_scopes_sales_to_user_stores(): void
    {
        $company = Company::factory()->create();
        $main = Store::factory()->create(['company_id' => $company->id, 'store_code' => 'MAIN']);
        $north = Store::factory()->create(['company_id' => $company->id, 'store_code' => 'NORTH']);

        $mainRegister = Register::factory()->create(['store_id' => $main->id]);
        $northRegister = Register::factory()->create(['store_id' => $north->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Store Manager');
        $user->stores()->sync([$main->id]);

        $mainShift = PosShift::factory()->create([
            'company_id' => $company->id,
            'store_id' => $main->id,
            'register_id' => $mainRegister->id,
            'user_id' => $user->id,
        ]);

        $northShift = PosShift::factory()->create([
            'company_id' => $company->id,
            'store_id' => $north->id,
            'register_id' => $northRegister->id,
            'user_id' => $user->id,
        ]);

        Sale::factory()->create([
            'company_id' => $company->id,
            'store_id' => $main->id,
            'register_id' => $mainRegister->id,
            'pos_shift_id' => $mainShift->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'grand_total' => 100,
            'completed_at' => now(),
        ]);

        Sale::factory()->create([
            'company_id' => $company->id,
            'store_id' => $north->id,
            'register_id' => $northRegister->id,
            'pos_shift_id' => $northShift->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'grand_total' => 999,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.reports.sales-summary.index'))
            ->assertOk();

        $summary = $response->viewData('page')['props']['summary'];
        $this->assertEquals(100, (float) $summary['gross_sales']);
        $this->assertSame(1, $summary['transactions']);
    }
}
