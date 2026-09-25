<?php

namespace Tests\Feature\Organization;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\Company;
use App\Models\SalesPlan;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPlanCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_company_admin_can_create_sales_plan_and_assign_stores(): void
    {
        $company = Company::factory()->create();
        $retailStore = Store::factory()->create([
            'company_id' => $company->id,
            'store_name' => 'Retail Main',
            'store_category' => 'retail',
        ]);
        $restaurantStore = Store::factory()->create([
            'company_id' => $company->id,
            'store_name' => 'Dining Hall',
            'store_category' => 'restaurant',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAsAdmin($admin)
            ->post(route('admin.sales-plans.store'), [
                'company_id' => $company->id,
                'plan_code' => 'RETAIL',
                'name' => 'Retail',
                'description' => 'Retail catalog',
                'status' => 'active',
                'store_ids' => [$retailStore->id],
            ])
            ->assertRedirect(route('admin.sales-plans.index'));

        $plan = SalesPlan::query()->where('plan_code', 'RETAIL')->firstOrFail();
        $this->assertSame('Retail', $plan->name);
        $this->assertSame($plan->id, $retailStore->fresh()->sales_plan_id);
        $this->assertNull($restaurantStore->fresh()->sales_plan_id);

        $this->actingAsAdmin($admin)
            ->get(route('admin.sales-plans.edit', $plan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/SalesPlans/Form')
                ->where('salesPlan.id', $plan->id));
    }

    public function test_updating_sales_plan_moves_stores_between_plans(): void
    {
        $company = Company::factory()->create();
        $retailPlan = SalesPlan::factory()->create([
            'company_id' => $company->id,
            'plan_code' => 'RETAIL',
            'name' => 'Retail',
        ]);
        $restoPlan = SalesPlan::factory()->create([
            'company_id' => $company->id,
            'plan_code' => 'RESTO',
            'name' => 'Restaurant',
        ]);
        $store = Store::factory()->create([
            'company_id' => $company->id,
            'sales_plan_id' => $retailPlan->id,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $admin->assignRole('Company Admin');

        $this->actingAsAdmin($admin)
            ->put(route('admin.sales-plans.update', $restoPlan), [
                'company_id' => $company->id,
                'plan_code' => 'RESTO',
                'name' => 'Restaurant',
                'description' => null,
                'status' => 'active',
                'store_ids' => [$store->id],
            ])
            ->assertRedirect(route('admin.sales-plans.index'));

        $this->assertSame($restoPlan->id, $store->fresh()->sales_plan_id);
    }

    protected function actingAsAdmin(User $admin)
    {
        return $this->actingAs($admin)->withSession([
            BackofficeContextService::SESSION_KEY => [
                'scope' => 'company',
                'company_id' => $admin->company_id,
                'store_id' => null,
            ],
            BackofficeContextService::ESTABLISHED_SESSION_KEY => true,
        ]);
    }
}
