<?php

namespace Tests\Feature\Inventory;

use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_inventory_clerk_can_transfer_stock_between_stores(): void
    {
        $company = Company::factory()->create();
        $fromStore = Store::factory()->create(['company_id' => $company->id, 'store_code' => 'MAIN']);
        $toStore = Store::factory()->create(['company_id' => $company->id, 'store_code' => 'NORTH']);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'status' => 'active',
        ]);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $fromStore->id,
            'product_id' => $product->id,
            'qty' => 20,
        ]);

        $clerk = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $clerk->assignRole('Inventory Clerk');
        $clerk->stores()->sync([$fromStore->id, $toStore->id]);

        $this->actingAs($clerk)
            ->post(route('admin.inventory.transfers.store'), [
                'from_store_id' => $fromStore->id,
                'to_store_id' => $toStore->id,
                'notes' => 'Restock NORTH',
                'reason' => 'Restock NORTH',
                'reference_no' => 'REF-100',
                'priority' => 'high',
                'lines' => [
                    ['product_id' => $product->id, 'quantity' => 5],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('15.0000', (string) StoreProductInventory::query()
            ->where('store_id', $fromStore->id)
            ->where('product_id', $product->id)
            ->value('qty'));

        $this->assertSame('5.0000', (string) StoreProductInventory::query()
            ->where('store_id', $toStore->id)
            ->where('product_id', $product->id)
            ->value('qty'));

        $this->assertDatabaseHas('stock_transfers', [
            'from_store_id' => $fromStore->id,
            'to_store_id' => $toStore->id,
            'status' => 'received',
            'transfer_type' => 'store_to_store',
            'priority' => 'high',
            'reason' => 'Restock NORTH',
            'reference_no' => 'REF-100',
        ]);

        $this->assertDatabaseHas('stock_transfer_items', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'requested_quantity' => '5.0000',
            'received_quantity' => '5.0000',
            'status' => 'received',
        ]);

        $this->assertSame(1, StockMovement::query()->where('movement_type', 'transfer_out')->count());
        $this->assertSame(1, StockMovement::query()->where('movement_type', 'transfer_in')->count());
    }

    public function test_transfer_rejects_insufficient_stock(): void
    {
        $company = Company::factory()->create();
        $fromStore = Store::factory()->create(['company_id' => $company->id]);
        $toStore = Store::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'status' => 'active',
        ]);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $fromStore->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $clerk = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $clerk->assignRole('Inventory Clerk');
        $clerk->stores()->sync([$fromStore->id, $toStore->id]);

        $this->actingAs($clerk)
            ->from(route('admin.inventory.transfers.create'))
            ->post(route('admin.inventory.transfers.store'), [
                'from_store_id' => $fromStore->id,
                'to_store_id' => $toStore->id,
                'lines' => [
                    ['product_id' => $product->id, 'quantity' => 10],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('lines.0.quantity');

        $this->assertDatabaseCount('stock_transfers', 0);
    }

    public function test_cashier_cannot_create_stock_transfer(): void
    {
        $company = Company::factory()->create();
        $fromStore = Store::factory()->create(['company_id' => $company->id]);
        $toStore = Store::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
        ]);

        $cashier = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $cashier->assignRole('Cashier');
        $cashier->stores()->sync([$fromStore->id, $toStore->id]);

        $this->actingAs($cashier)
            ->post(route('admin.inventory.transfers.store'), [
                'from_store_id' => $fromStore->id,
                'to_store_id' => $toStore->id,
                'lines' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertForbidden();
    }

    public function test_inventory_clerk_can_list_transfers(): void
    {
        $company = Company::factory()->create();
        $fromStore = Store::factory()->create(['company_id' => $company->id]);
        $toStore = Store::factory()->create(['company_id' => $company->id]);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $company->id,
            'from_store_id' => $fromStore->id,
            'to_store_id' => $toStore->id,
            'status' => 'received',
            'transferred_at' => now(),
        ]);

        $clerk = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $clerk->assignRole('Inventory Clerk');
        $clerk->stores()->sync([$fromStore->id, $toStore->id]);

        $this->actingAs($clerk)
            ->get(route('admin.inventory.transfers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Inventory/Transfers/Index')
                ->has('transfers.data', 1)
                ->where('transfers.data.0.id', $transfer->id));
    }

    public function test_create_form_includes_company_tracked_products(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $fromStore = Store::factory()->create(['company_id' => $company->id]);
        $toStore = Store::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'status' => 'active',
            'name' => 'Bottled Water',
            'sku' => 'WATER-500',
        ]);
        Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => false,
            'status' => 'active',
            'name' => 'Gift Card',
        ]);
        Product::factory()->create([
            'company_id' => $otherCompany->id,
            'track_inventory' => true,
            'status' => 'active',
            'name' => 'Other Co Item',
        ]);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $fromStore->id,
            'product_id' => $product->id,
            'qty' => 12,
        ]);

        $clerk = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $clerk->assignRole('Inventory Clerk');
        $clerk->stores()->sync([$fromStore->id, $toStore->id]);

        $this->actingAs($clerk)
            ->get(route('admin.inventory.transfers.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Inventory/Transfers/Form')
                ->has('products', 1)
                ->where('products.0.id', $product->id)
                ->where('products.0.sku', 'WATER-500')
                ->where('products.0.company_id', $company->id)
                ->has('inventories', 1)
                ->where('inventories.0.product_id', $product->id)
                ->where('inventories.0.qty', '12.0000')
                ->has('stores', 2)
                ->has('warehouses')
                ->has('transferTypes')
                ->has('priorities'));
    }

    public function test_create_form_loads_company_products_from_assigned_stores_without_user_company(): void
    {
        $company = Company::factory()->create();
        $fromStore = Store::factory()->create(['company_id' => $company->id]);
        $toStore = Store::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'status' => 'active',
        ]);

        $clerk = User::factory()->create([
            'company_id' => null,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $clerk->assignRole('Inventory Clerk');
        $clerk->stores()->sync([$fromStore->id, $toStore->id]);

        $this->actingAs($clerk)
            ->get(route('admin.inventory.transfers.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Inventory/Transfers/Form')
                ->has('products', 1)
                ->where('products.0.id', $product->id)
                ->has('stores', 2));
    }
}
