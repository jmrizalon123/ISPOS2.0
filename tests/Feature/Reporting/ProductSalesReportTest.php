<?php

namespace Tests\Feature\Reporting;

use App\Models\Company;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\Store;
use App\Models\Tax;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_product_sales_report_lists_top_sellers(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $register = Register::factory()->create(['store_id' => $store->id]);
        $tax = Tax::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Branch Manager');
        $user->stores()->sync([$store->id]);

        $cola = Product::factory()->create([
            'company_id' => $company->id,
            'tax_id' => $tax->id,
            'sku' => 'COLA-330',
            'name' => 'Cola 330ml',
            'base_price' => 35,
        ]);

        $shift = PosShift::factory()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'user_id' => $user->id,
        ]);

        $sale = Sale::factory()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'register_id' => $register->id,
            'pos_shift_id' => $shift->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        SaleLine::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $cola->id,
            'line_number' => 1,
            'sku' => 'COLA-330',
            'name' => 'Cola 330ml',
            'qty' => 3,
            'unit_price' => 35,
            'line_subtotal' => 105,
            'tax_amount' => 12.6,
            'line_total' => 117.6,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.reports.product-sales.index'))
            ->assertOk();

        $products = $response->viewData('page')['props']['products'];
        $this->assertCount(1, $products);
        $this->assertSame('COLA-330', $products[0]['sku']);
        $this->assertEquals(3, (float) $products[0]['qty_sold']);
    }
}
