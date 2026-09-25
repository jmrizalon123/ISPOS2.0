<?php

namespace Database\Seeders;

use App\Domains\Accounting\Services\DefaultChartOfAccountsService;
use App\Domains\Crm\Services\CustomerMembershipService;
use App\Domains\Inventory\Services\StoreInventoryService;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Domains\Purchasing\Services\PurchaseReceivingService;
use App\Domains\Purchasing\Services\PurchaseReturnService;
use App\Models\Customer;
use App\Models\LoyaltyProgram;
use App\Models\MembershipPlan;
use App\Models\Promotion;
use App\Models\Supplier;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\PriceGroup;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductComponent;
use App\Models\ProductIngredient;
use App\Models\ProductModifierGroup;
use App\Models\ProductModifierOption;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\PosShift;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SalePayment;
use App\Models\StoreProductInventory;
use App\Models\Setting;
use App\Models\Store;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['company_code' => 'DEMO'],
            [
                'name' => 'Demo Retail PH',
                'legal_name' => 'Demo Retail PH Corporation',
                'display_name' => 'Demo Retail PH',
                'trade_name' => 'Demo Retail',
                'company_type' => 'corporation',
                'industry' => 'Retail',
                'vat_registered' => true,
                'taxpayer_type' => 'vat',
                'default_tax_rate' => 12,
                'email' => 'hello@demo.ispos.local',
                'country' => 'PH',
                'base_currency' => 'PHP',
                'currency_symbol' => '₱',
                'timezone' => 'Asia/Manila',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'language' => 'en',
                'enable_pos' => true,
                'enable_inventory' => true,
                'enable_crm' => true,
                'enable_accounting' => true,
                'receipt_footer' => 'Thank you for shopping with us!',
                'status' => 'active',
                'is_active' => true,
            ],
        );

        $mainStore = Store::query()->firstOrCreate(
            ['company_id' => $company->id, 'store_code' => 'MAIN'],
            [
                'store_name' => 'Main Branch — Makati',
                'legal_name' => 'Demo Retail PH — Makati Branch',
                'store_type' => 'branch',
                'store_category' => 'retail',
                'address_line_1' => 'Ayala Ave, Makati City',
                'city' => 'Makati',
                'province' => 'Metro Manila',
                'country' => 'PH',
                'phone' => '+63 2 8123 4567',
                'currency' => 'PHP',
                'timezone' => 'Asia/Manila',
                'enable_pos' => true,
                'enable_inventory' => true,
                'operating_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
                'status' => 'active',
                'is_active' => true,
            ],
        );

        $northStore = Store::query()->firstOrCreate(
            ['company_id' => $company->id, 'store_code' => 'NORTH'],
            [
                'store_name' => 'North Branch — Quezon City',
                'legal_name' => 'Demo Retail PH — QC Branch',
                'store_type' => 'branch',
                'store_category' => 'retail',
                'address_line_1' => 'Quezon Ave, Quezon City',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'country' => 'PH',
                'phone' => '+63 2 8765 4321',
                'currency' => 'PHP',
                'timezone' => 'Asia/Manila',
                'enable_pos' => true,
                'enable_inventory' => true,
                'operating_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
                'status' => 'active',
                'is_active' => true,
            ],
        );

        foreach ([
            [$mainStore, 'REG1', 'Register 1'],
            [$mainStore, 'REG2', 'Register 2'],
            [$northStore, 'REG1', 'Register 1'],
        ] as [$store, $code, $name]) {
            Register::query()->firstOrCreate(
                ['store_id' => $store->id, 'register_code' => $code],
                ['register_name' => $name, 'status' => 'active'],
            );
        }

        app(SettingService::class)->set('company.receipt_footer', 'Thank you for shopping with us!', Setting::SCOPE_COMPANY, $company->id);
        app(SettingService::class)->set('company.tax_rate', 12, Setting::SCOPE_COMPANY, $company->id);

        $this->seedCatalog($company, $mainStore);
        $this->seedStoreInventory($company, $mainStore, $northStore);

        $users = [
            ['name' => 'Developer', 'email' => 'developer@demo.ispos.local', 'role' => 'Developer', 'stores' => [$mainStore->id, $northStore->id], 'company' => $company->id],
            ['name' => 'Super Admin', 'email' => 'superadmin@demo.ispos.local', 'role' => 'Super Admin', 'stores' => [$mainStore->id, $northStore->id], 'company' => $company->id],
            ['name' => 'Company Admin', 'email' => 'companyadmin@demo.ispos.local', 'role' => 'Company Admin', 'stores' => [$mainStore->id, $northStore->id], 'company' => $company->id],
            ['name' => 'Branch Manager', 'email' => 'branchmanager@demo.ispos.local', 'role' => 'Branch Manager', 'stores' => [$mainStore->id, $northStore->id], 'company' => $company->id],
            ['name' => 'Store Manager', 'email' => 'storemanager@demo.ispos.local', 'role' => 'Store Manager', 'stores' => [$mainStore->id], 'company' => $company->id],
            ['name' => 'Supervisor', 'email' => 'supervisor@demo.ispos.local', 'role' => 'Supervisor', 'stores' => [$mainStore->id], 'company' => $company->id],
            ['name' => 'Cashier One', 'email' => 'cashier@demo.ispos.local', 'role' => 'Cashier', 'stores' => [$mainStore->id], 'company' => $company->id],
            ['name' => 'Inventory Clerk', 'email' => 'inventory@demo.ispos.local', 'role' => 'Inventory Clerk', 'stores' => [$mainStore->id, $northStore->id], 'company' => $company->id],
            ['name' => 'Purchasing Officer', 'email' => 'purchasing@demo.ispos.local', 'role' => 'Purchasing Officer', 'stores' => [$mainStore->id, $northStore->id], 'company' => $company->id],
            ['name' => 'Accountant', 'email' => 'accountant@demo.ispos.local', 'role' => 'Accountant', 'stores' => [$mainStore->id, $northStore->id], 'company' => $company->id],
            ['name' => 'Auditor', 'email' => 'auditor@demo.ispos.local', 'role' => 'Auditor', 'stores' => [$mainStore->id, $northStore->id], 'company' => $company->id],
            ['name' => 'Report Viewer', 'email' => 'reports@demo.ispos.local', 'role' => 'Report Viewer', 'stores' => [$mainStore->id, $northStore->id], 'company' => $company->id],
            ['name' => 'Kitchen Staff', 'email' => 'kitchen@demo.ispos.local', 'role' => 'Kitchen Staff', 'stores' => [$mainStore->id], 'company' => $company->id],
        ];

        foreach ($users as $data) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'company_id' => $data['company'],
                    'default_store_id' => $data['stores'][0] ?? null,
                    'password' => 'password',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ],
            );

            $user->update([
                'name' => $data['name'],
                'company_id' => $data['company'],
                'default_store_id' => $data['stores'][0] ?? null,
                'status' => 'active',
            ]);

            $user->syncRoles([$data['role']]);
            $user->stores()->sync($data['stores']);
        }

        $this->seedAccounting($company);
        $this->seedPurchasing($company, $mainStore);
        $this->seedCrm($company, $mainStore);
        $this->seedDemoSales($company, $mainStore, $northStore);
    }

    protected function seedCatalog(Company $company, Store $mainStore): void
    {
        $beverages = Category::query()->firstOrCreate(
            ['company_id' => $company->id, 'category_code' => 'BEV'],
            ['name' => 'Beverages', 'sort_order' => 1, 'status' => 'active'],
        );
        $food = Category::query()->firstOrCreate(
            ['company_id' => $company->id, 'category_code' => 'FOOD'],
            ['name' => 'Food', 'sort_order' => 2, 'status' => 'active'],
        );
        Category::query()->firstOrCreate(
            ['company_id' => $company->id, 'category_code' => 'MERCH'],
            ['name' => 'Merchandise', 'sort_order' => 3, 'status' => 'active'],
        );

        $houseBrand = Brand::query()->firstOrCreate(
            ['company_id' => $company->id, 'brand_code' => 'HOUSE'],
            ['name' => 'House Brand', 'status' => 'active'],
        );
        Brand::query()->firstOrCreate(
            ['company_id' => $company->id, 'brand_code' => 'COKE'],
            ['name' => 'Coca-Cola', 'status' => 'active'],
        );

        $pc = Unit::query()->firstOrCreate(
            ['company_id' => $company->id, 'unit_code' => 'PC'],
            ['name' => 'Piece', 'symbol' => 'pc', 'status' => 'active'],
        );
        Unit::query()->firstOrCreate(
            ['company_id' => $company->id, 'unit_code' => 'KG'],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'status' => 'active'],
        );

        $vat = Tax::query()->firstOrCreate(
            ['company_id' => $company->id, 'tax_code' => 'VAT12'],
            ['name' => 'VAT 12%', 'rate' => 12, 'is_inclusive' => false, 'status' => 'active'],
        );

        $retail = PriceGroup::query()->firstOrCreate(
            ['company_id' => $company->id, 'group_code' => 'RETAIL'],
            ['name' => 'Retail', 'description' => 'Default retail pricing', 'is_default' => true, 'status' => 'active'],
        );

        $mainStore->update(['price_group_id' => $retail->id]);

        $sampleProducts = [
            ['sku' => 'WATER-500', 'name' => 'Bottled Water 500ml', 'category' => $beverages, 'price' => 25, 'qty' => 120, 'ideal' => 100, 'warning' => 20],
            ['sku' => 'COLA-330', 'name' => 'Cola 330ml Can', 'category' => $beverages, 'price' => 35, 'brand' => 'COKE', 'qty' => 8, 'ideal' => 80, 'warning' => 15],
            ['sku' => 'CHIPS-50', 'name' => 'Potato Chips 50g', 'category' => $food, 'price' => 45, 'qty' => 0, 'ideal' => 50, 'warning' => 10],
            ['sku' => 'BREAD-WH', 'name' => 'White Bread Loaf', 'category' => $food, 'price' => 65, 'qty' => 25, 'ideal' => 40, 'warning' => 12],
            ['sku' => 'RICE-1KG', 'name' => 'Premium Rice 1kg', 'category' => $food, 'price' => 55, 'unit' => 'KG', 'qty' => 5, 'ideal' => 30, 'warning' => 8],
            ['sku' => 'BAG-TOTE', 'name' => 'Reusable Tote Bag', 'category' => null, 'price' => 99, 'qty' => 45, 'ideal' => 30, 'warning' => 10],
            ['sku' => 'PEN-BLU', 'name' => 'Ballpen Blue', 'category' => null, 'price' => 15, 'qty' => 200, 'ideal' => 100, 'warning' => 25],
        ];

        foreach ($sampleProducts as $index => $row) {
            $product = Product::query()->firstOrCreate(
                ['company_id' => $company->id, 'sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'category_id' => $row['category']?->id,
                    'brand_id' => isset($row['brand']) ? Brand::query()->where('company_id', $company->id)->where('brand_code', $row['brand'])->value('id') : $houseBrand->id,
                    'unit_id' => isset($row['unit']) && $row['unit'] === 'KG'
                        ? Unit::query()->where('company_id', $company->id)->where('unit_code', 'KG')->value('id')
                        : $pc->id,
                    'tax_id' => $vat->id,
                    'cost' => round($row['price'] * 0.6, 4),
                    'base_price' => $row['price'],
                    'track_inventory' => true,
                    'qty' => $row['qty'] ?? 0,
                    'ideal_qty' => $row['ideal'] ?? null,
                    'warning_qty' => $row['warning'] ?? null,
                    'has_variants' => false,
                    'status' => 'active',
                ],
            );

            ProductBarcode::query()->firstOrCreate(
                ['company_id' => $company->id, 'barcode' => '8901234567'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                ['product_id' => $product->id, 'is_primary' => true],
            );

            ProductPrice::query()->firstOrCreate(
                [
                    'price_group_id' => $retail->id,
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                ],
                ['price' => $row['price']],
            );
        }

        $drink = Product::query()->firstOrCreate(
            ['company_id' => $company->id, 'sku' => 'ICED-COFFEE'],
            [
                'name' => 'Iced Coffee',
                'category_id' => $beverages->id,
                'brand_id' => $houseBrand->id,
                'unit_id' => $pc->id,
                'tax_id' => $vat->id,
                'cost' => 45,
                'base_price' => 89,
                'track_inventory' => true,
                'has_variants' => true,
                'status' => 'active',
            ],
        );

        foreach ([
            ['code' => 'REG', 'name' => 'Regular', 'price' => 89],
            ['code' => 'LRG', 'name' => 'Large', 'price' => 109],
        ] as $sort => $variant) {
            $variantModel = ProductVariant::query()->firstOrCreate(
                ['product_id' => $drink->id, 'variant_code' => $variant['code']],
                [
                    'name' => $variant['name'],
                    'sku' => 'ICED-COFFEE-'.$variant['code'],
                    'cost' => 45 + ($sort * 5),
                    'selling_price' => $variant['price'],
                    'sort_order' => $sort,
                    'status' => 'active',
                ],
            );

            ProductBarcode::query()->firstOrCreate(
                ['company_id' => $company->id, 'barcode' => '8901234599'.($sort + 1)],
                ['product_id' => $drink->id, 'product_variant_id' => $variantModel->id, 'is_primary' => $sort === 0],
            );

            ProductPrice::query()->firstOrCreate(
                [
                    'price_group_id' => $retail->id,
                    'product_id' => $drink->id,
                    'product_variant_id' => $variantModel->id,
                ],
                ['price' => $variant['price']],
            );
        }

        $this->seedRestaurantCatalog($company, $food, $houseBrand, $pc, $vat, $retail);
    }

    protected function seedRestaurantCatalog(
        Company $company,
        Category $food,
        Brand $houseBrand,
        Unit $pc,
        Tax $vat,
        PriceGroup $retail,
    ): void {
        $ingredients = [
            ['sku' => 'ING-BUN', 'name' => 'Burger Bun', 'cost' => 8, 'price' => 0],
            ['sku' => 'ING-PATTY', 'name' => 'Beef Patty', 'cost' => 45, 'price' => 0],
            ['sku' => 'ING-CHEESE', 'name' => 'Cheddar Slice', 'cost' => 12, 'price' => 0],
            ['sku' => 'ING-LETTUCE', 'name' => 'Lettuce Leaf', 'cost' => 3, 'price' => 0],
        ];

        $ingredientModels = [];

        foreach ($ingredients as $row) {
            $ingredientModels[$row['sku']] = Product::query()->firstOrCreate(
                ['company_id' => $company->id, 'sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'product_type' => 'ingredient',
                    'category_id' => $food->id,
                    'brand_id' => $houseBrand->id,
                    'unit_id' => $pc->id,
                    'tax_id' => $vat->id,
                    'cost' => $row['cost'],
                    'base_price' => $row['price'],
                    'track_inventory' => true,
                    'qty' => 50,
                    'ideal_qty' => 40,
                    'warning_qty' => 10,
                    'has_variants' => false,
                    'has_modifiers' => false,
                    'has_components' => false,
                    'status' => 'active',
                ],
            );
        }

        $burger = Product::query()->firstOrCreate(
            ['company_id' => $company->id, 'sku' => 'BURGER-CLASSIC'],
            [
                'name' => 'Classic Burger',
                'product_type' => 'menu_item',
                'category_id' => $food->id,
                'brand_id' => $houseBrand->id,
                'unit_id' => $pc->id,
                'tax_id' => $vat->id,
                'cost' => 85,
                'base_price' => 189,
                'track_inventory' => false,
                'has_variants' => false,
                'has_modifiers' => true,
                'has_components' => false,
                'status' => 'active',
            ],
        );

        ProductPrice::query()->firstOrCreate(
            [
                'price_group_id' => $retail->id,
                'product_id' => $burger->id,
                'product_variant_id' => null,
            ],
            ['price' => 189],
        );

        foreach ([
            ['sku' => 'ING-BUN', 'qty' => 1, 'optional' => false],
            ['sku' => 'ING-PATTY', 'qty' => 1, 'optional' => false],
            ['sku' => 'ING-CHEESE', 'qty' => 1, 'optional' => true],
            ['sku' => 'ING-LETTUCE', 'qty' => 1, 'optional' => true],
        ] as $sort => $row) {
            ProductIngredient::query()->firstOrCreate(
                [
                    'product_id' => $burger->id,
                    'ingredient_product_id' => $ingredientModels[$row['sku']]->id,
                ],
                [
                    'quantity' => $row['qty'],
                    'unit_id' => $pc->id,
                    'is_optional' => $row['optional'],
                    'sort_order' => $sort,
                ],
            );
        }

        $sizeGroup = ProductModifierGroup::query()->firstOrCreate(
            ['product_id' => $burger->id, 'group_code' => 'SIZE'],
            [
                'name' => 'Size',
                'selection_type' => 'single',
                'is_required' => true,
                'min_selections' => 1,
                'max_selections' => 1,
                'sort_order' => 0,
                'status' => 'active',
            ],
        );

        foreach ([
            ['code' => 'REG', 'name' => 'Regular', 'price' => 0, 'default' => true],
            ['code' => 'DBL', 'name' => 'Double Patty', 'price' => 60, 'default' => false],
        ] as $sort => $option) {
            ProductModifierOption::query()->firstOrCreate(
                ['product_modifier_group_id' => $sizeGroup->id, 'option_code' => $option['code']],
                [
                    'name' => $option['name'],
                    'price_adjustment' => $option['price'],
                    'is_default' => $option['default'],
                    'sort_order' => $sort,
                    'status' => 'active',
                ],
            );
        }

        $addonGroup = ProductModifierGroup::query()->firstOrCreate(
            ['product_id' => $burger->id, 'group_code' => 'ADDON'],
            [
                'name' => 'Add-ons',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => null,
                'sort_order' => 1,
                'status' => 'active',
            ],
        );

        foreach ([
            ['code' => 'CHEESE', 'name' => 'Extra Cheese', 'price' => 25],
            ['code' => 'BACON', 'name' => 'Bacon Strip', 'price' => 35],
        ] as $sort => $option) {
            ProductModifierOption::query()->firstOrCreate(
                ['product_modifier_group_id' => $addonGroup->id, 'option_code' => $option['code']],
                [
                    'name' => $option['name'],
                    'price_adjustment' => $option['price'],
                    'is_default' => false,
                    'sort_order' => $sort,
                    'status' => 'active',
                ],
            );
        }

        $cola = Product::query()->where('company_id', $company->id)->where('sku', 'COLA-330')->first();

        if ($cola) {
            $combo = Product::query()->firstOrCreate(
                ['company_id' => $company->id, 'sku' => 'COMBO-BURGER-DRINK'],
                [
                    'name' => 'Burger Combo Meal',
                    'product_type' => 'menu_item',
                    'category_id' => $food->id,
                    'brand_id' => $houseBrand->id,
                    'unit_id' => $pc->id,
                    'tax_id' => $vat->id,
                    'cost' => 95,
                    'base_price' => 219,
                    'track_inventory' => false,
                    'has_variants' => false,
                    'has_modifiers' => false,
                    'has_components' => true,
                    'status' => 'active',
                ],
            );

            ProductPrice::query()->firstOrCreate(
                [
                    'price_group_id' => $retail->id,
                    'product_id' => $combo->id,
                    'product_variant_id' => null,
                ],
                ['price' => 219],
            );

            foreach ([
                ['product_id' => $burger->id, 'qty' => 1, 'optional' => false],
                ['product_id' => $cola->id, 'qty' => 1, 'optional' => false],
            ] as $sort => $row) {
                ProductComponent::query()->firstOrCreate(
                    [
                        'product_id' => $combo->id,
                        'component_product_id' => $row['product_id'],
                    ],
                    [
                        'quantity' => $row['qty'],
                        'unit_id' => $pc->id,
                        'is_optional' => $row['optional'],
                        'sort_order' => $sort,
                    ],
                );
            }
        }
    }

    protected function seedStoreInventory(Company $company, Store $mainStore, Store $northStore): void
    {
        $storeInventory = app(StoreInventoryService::class);

        Product::query()
            ->where('company_id', $company->id)
            ->where('track_inventory', true)
            ->each(function (Product $product) use ($storeInventory, $mainStore, $northStore) {
                $storeInventory->migrateLegacyQty($product, $mainStore);

                StoreProductInventory::query()->firstOrCreate(
                    ['store_id' => $northStore->id, 'product_id' => $product->id],
                    ['company_id' => $product->company_id, 'qty' => 0],
                );
            });

        $cola = Product::query()->where('company_id', $company->id)->where('sku', 'COLA-330')->first();
        if ($cola) {
            StoreProductInventory::query()
                ->where('store_id', $northStore->id)
                ->where('product_id', $cola->id)
                ->update(['qty' => 20]);
        }
    }

    protected function seedPurchasing(Company $company, Store $mainStore): void
    {
        if (\App\Models\PurchaseOrder::query()
            ->where('company_id', $company->id)
            ->where('notes', 'Demo received PO')
            ->exists()) {
            return;
        }

        $purchasingUser = User::query()->where('email', 'purchasing@demo.ispos.local')->first();
        if (! $purchasingUser) {
            return;
        }

        $metro = Supplier::query()->firstOrCreate(
            ['company_id' => $company->id, 'supplier_code' => 'METRO'],
            ['name' => 'Metro Foods Supply', 'contact_name' => 'Ana Reyes', 'status' => 'active'],
        );

        $beverageCo = Supplier::query()->firstOrCreate(
            ['company_id' => $company->id, 'supplier_code' => 'BEVCO'],
            ['name' => 'Beverage Distributors Inc.', 'contact_name' => 'Ben Cruz', 'status' => 'active'],
        );

        $cola = Product::query()->where('company_id', $company->id)->where('sku', 'COLA-330')->first();
        $water = Product::query()->where('company_id', $company->id)->where('sku', 'WATER-500')->first();
        $bread = Product::query()->where('company_id', $company->id)->where('sku', 'BREAD-WH')->first();

        if (! $cola || ! $water || ! $bread) {
            return;
        }

        $poService = app(PurchaseOrderService::class);
        $receivingService = app(PurchaseReceivingService::class);
        $returnService = app(PurchaseReturnService::class);

        $approvedPo = $poService->create($purchasingUser, $mainStore, $beverageCo, [
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(3)->toDateString(),
            'notes' => 'Demo PO awaiting receipt',
        ], [
            ['product_id' => $cola->id, 'ordered_qty' => 48, 'unit_cost' => 22],
            ['product_id' => $water->id, 'ordered_qty' => 24, 'unit_cost' => 12],
        ]);
        $poService->approve($approvedPo, $purchasingUser);

        $receivedPo = $poService->create($purchasingUser, $mainStore, $metro, [
            'order_date' => now()->subDays(2)->toDateString(),
            'notes' => 'Demo received PO',
        ], [
            ['product_id' => $bread->id, 'ordered_qty' => 30, 'unit_cost' => 40],
        ]);
        $poService->approve($receivedPo, $purchasingUser);
        $receivedPo = $receivedPo->fresh(['lines']);
        $receivingService->receive($receivedPo, $purchasingUser, [
            ['line_id' => $receivedPo->lines->first()->id, 'receive_qty' => 30],
        ]);

        $return = $returnService->create($purchasingUser, $mainStore, $metro, [
            'purchase_order_id' => $receivedPo->id,
            'reason' => 'Damaged packaging',
            'notes' => 'Demo purchase return',
        ], [
            ['product_id' => $bread->id, 'qty' => 2, 'unit_cost' => 40, 'purchase_order_line_id' => $receivedPo->lines->first()->id],
        ]);
        $returnService->post($return, $purchasingUser);
    }

    protected function seedCrm(Company $company, Store $mainStore): void
    {
        $admin = User::query()->where('email', 'companyadmin@demo.ispos.local')->first();
        if (! $admin) {
            return;
        }

        $retail = PriceGroup::query()->where('company_id', $company->id)->where('group_code', 'RETAIL')->first();

        $loyaltyProgram = LoyaltyProgram::query()->firstOrCreate(
            ['company_id' => $company->id, 'program_code' => 'REWARDS'],
            [
                'name' => 'Demo Rewards',
                'earn_rate' => 1,
                'is_default' => true,
                'status' => 'active',
            ],
        );

        $membershipPlan = MembershipPlan::query()->firstOrCreate(
            ['company_id' => $company->id, 'plan_code' => 'GOLD'],
            [
                'name' => 'Gold Member',
                'description' => '5% member discount and member pricing',
                'discount_percent' => 5,
                'duration_days' => 365,
                'price_group_id' => $retail?->id,
                'status' => 'active',
            ],
        );

        $membershipService = app(CustomerMembershipService::class);

        $maria = Customer::query()->firstOrCreate(
            ['company_id' => $company->id, 'customer_code' => 'MARIA001'],
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'maria.santos@example.test',
                'phone' => '+63 917 000 0001',
                'loyalty_program_id' => $loyaltyProgram->id,
                'loyalty_points' => 120,
                'status' => 'active',
            ],
        );

        if (! $maria->memberships()->where('status', 'active')->exists()) {
            $membershipService->assign($maria, $membershipPlan, $admin);
        }

        Customer::query()->firstOrCreate(
            ['company_id' => $company->id, 'customer_code' => 'JUAN002'],
            [
                'first_name' => 'Juan',
                'last_name' => 'Cruz',
                'email' => 'juan.cruz@example.test',
                'loyalty_program_id' => $loyaltyProgram->id,
                'status' => 'active',
            ],
        );

        $beverages = Category::query()->where('company_id', $company->id)->where('category_code', 'BEV')->first();
        $cola = Product::query()->where('company_id', $company->id)->where('sku', 'COLA-330')->first();

        $promotion = Promotion::query()->firstOrCreate(
            ['company_id' => $company->id, 'promo_code' => 'BEV10'],
            [
                'name' => '10% Off Beverages',
                'promo_type' => 'percent_off',
                'discount_value' => 10,
                'applies_to' => $beverages ? 'categories' : 'all',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'status' => 'active',
            ],
        );

        if ($beverages && $promotion->categories()->count() === 0) {
            $promotion->categories()->sync([$beverages->id]);
        }

        Promotion::query()->firstOrCreate(
            ['company_id' => $company->id, 'promo_code' => 'SAVE50'],
            [
                'name' => '₱50 Off ₱500+',
                'promo_type' => 'fixed_amount',
                'discount_value' => 50,
                'min_purchase_amount' => 500,
                'applies_to' => 'all',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(2),
                'status' => 'active',
            ],
        );
    }

    protected function seedAccounting(Company $company): void
    {
        $company->update(['enable_accounting' => true]);

        $admin = User::query()->where('email', 'companyadmin@demo.ispos.local')->first();
        app(DefaultChartOfAccountsService::class)->seedForCompany($company, $admin);
    }

    protected function seedDemoSales(Company $company, Store $mainStore, Store $northStore): void
    {
        if (Sale::query()->where('company_id', $company->id)->exists()) {
            return;
        }

        $cashier = User::query()->where('email', 'cashier@demo.ispos.local')->first();
        $cola = Product::query()->where('company_id', $company->id)->where('sku', 'COLA-330')->first();
        $burger = Product::query()->where('company_id', $company->id)->where('sku', 'BURGER-CLASSIC')->first();

        if (! $cashier || ! $cola) {
            return;
        }

        $registers = [
            $mainStore->id => Register::query()->where('store_id', $mainStore->id)->where('register_code', 'REG1')->first(),
            $northStore->id => Register::query()->where('store_id', $northStore->id)->where('register_code', 'REG1')->first(),
        ];

        $samples = [
            ['store' => $mainStore, 'days_ago' => 0, 'items' => [['product' => $cola, 'qty' => 2, 'price' => 35]]],
            ['store' => $mainStore, 'days_ago' => 1, 'items' => [['product' => $cola, 'qty' => 1, 'price' => 35]]],
            ['store' => $mainStore, 'days_ago' => 3, 'items' => [['product' => $burger ?? $cola, 'qty' => 1, 'price' => $burger ? 189 : 35]]],
            ['store' => $mainStore, 'days_ago' => 7, 'items' => [['product' => $cola, 'qty' => 4, 'price' => 35]]],
            ['store' => $northStore, 'days_ago' => 2, 'items' => [['product' => $cola, 'qty' => 3, 'price' => 35]]],
            ['store' => $northStore, 'days_ago' => 5, 'items' => [['product' => $cola, 'qty' => 2, 'price' => 35]]],
        ];

        $sequence = 1;

        foreach ($samples as $sample) {
            /** @var Store $store */
            $store = $sample['store'];
            $register = $registers[$store->id] ?? null;

            if (! $register) {
                continue;
            }

            $completedAt = now()->subDays($sample['days_ago'])->setTime(14, 30);
            $subtotal = 0.0;

            foreach ($sample['items'] as $item) {
                $subtotal += $item['qty'] * $item['price'];
            }

            $taxTotal = round($subtotal * 0.12, 4);
            $grandTotal = round($subtotal + $taxTotal, 4);

            $shift = PosShift::query()->create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'register_id' => $register->id,
                'user_id' => $cashier->id,
                'status' => 'closed',
                'opening_float' => 1000,
                'closing_float' => 1000,
                'opened_at' => $completedAt->copy()->subHours(6),
                'closed_at' => $completedAt,
                'created_by' => $cashier->id,
                'updated_by' => $cashier->id,
            ]);

            $sale = Sale::query()->create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'sale_number' => sprintf('%s-DEMO-%04d', $store->store_code, $sequence++),
                'company_id' => $company->id,
                'store_id' => $store->id,
                'register_id' => $register->id,
                'pos_shift_id' => $shift->id,
                'user_id' => $cashier->id,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => 0,
                'grand_total' => $grandTotal,
                'completed_at' => $completedAt,
                'created_by' => $cashier->id,
                'updated_by' => $cashier->id,
            ]);

            foreach ($sample['items'] as $index => $item) {
                $lineSubtotal = $item['qty'] * $item['price'];
                $lineTax = round($lineSubtotal * 0.12, 4);

                SaleLine::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'line_number' => $index + 1,
                    'sku' => $item['product']->sku,
                    'name' => $item['product']->name,
                    'qty' => $item['qty'],
                    'unit_price' => $item['price'],
                    'line_subtotal' => $lineSubtotal,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineSubtotal + $lineTax,
                ]);
            }

            SalePayment::query()->create([
                'sale_id' => $sale->id,
                'payment_method' => 'cash',
                'amount' => $grandTotal,
                'paid_at' => $completedAt,
            ]);
        }
    }
}
