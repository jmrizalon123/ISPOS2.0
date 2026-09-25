<?php

namespace Tests\Feature\Reporting;

use App\Models\Company;
use App\Models\PosShift;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_report_viewer_can_list_shift_reports(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $register = Register::factory()->create(['store_id' => $store->id]);
        $cashier = User::factory()->create(['company_id' => $company->id, 'email_verified_at' => now()]);
        $viewer = User::factory()->create(['company_id' => $company->id, 'email_verified_at' => now()]);
        $viewer->assignRole('Report Viewer');
        $viewer->stores()->sync([$store->id]);

        $shift = PosShift::factory()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'user_id' => $cashier->id,
            'status' => 'closed',
            'opening_float' => 1000,
            'closing_float' => 1500,
            'expected_cash' => 1500,
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);

        $sale = Sale::factory()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'pos_shift_id' => $shift->id,
            'user_id' => $cashier->id,
            'status' => 'completed',
            'grand_total' => 500,
            'completed_at' => now(),
        ]);

        SalePayment::query()->create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 500,
            'paid_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.reports.shift-reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Reports/Shifts/Index')
                ->has('shifts.data', 1)
                ->where('shifts.data.0.id', $shift->id)
                ->where('shifts.data.0.transaction_count', 1));
    }

    public function test_shift_z_report_shows_cash_reconciliation_and_sales(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $register = Register::factory()->create(['store_id' => $store->id]);
        $cashier = User::factory()->create(['company_id' => $company->id, 'email_verified_at' => now()]);
        $viewer = User::factory()->create(['company_id' => $company->id, 'email_verified_at' => now()]);
        $viewer->assignRole('Report Viewer');
        $viewer->stores()->sync([$store->id]);

        $shift = PosShift::factory()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'user_id' => $cashier->id,
            'status' => 'closed',
            'opening_float' => 1000,
            'closing_float' => 1500,
            'expected_cash' => 1500,
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);

        $sale = Sale::factory()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'pos_shift_id' => $shift->id,
            'user_id' => $cashier->id,
            'status' => 'completed',
            'grand_total' => 500,
            'completed_at' => now(),
        ]);

        SalePayment::query()->create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 500,
            'paid_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.reports.shift-reports.show', $shift))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Reports/Shifts/Show')
                ->where('report.shift.id', $shift->id)
                ->where('report.summary.transaction_count', 1)
                ->where('report.shift.variance', '0.0000')
                ->has('report.sales', 1)
                ->has('report.payments', 1));
    }

    public function test_cashier_cannot_access_shift_reports(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $user->assignRole('Cashier');

        $this->actingAs($user)
            ->get(route('admin.reports.shift-reports.index'))
            ->assertForbidden();
    }
}
