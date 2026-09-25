<?php

namespace Tests\Feature\Reporting;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_cannot_view_reports(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Cashier');

        $this->actingAs($user)
            ->get(route('admin.reports.sales-summary.index'))
            ->assertForbidden();
    }

    public function test_report_viewer_can_access_sales_register(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Report Viewer');

        $this->actingAs($user)
            ->get(route('admin.reports.sales-register.index'))
            ->assertOk();
    }

    public function test_report_viewer_cannot_export_without_export_permission(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Report Viewer');

        $this->actingAs($user)
            ->get(route('admin.reports.sales-register.export'))
            ->assertForbidden();
    }
}
