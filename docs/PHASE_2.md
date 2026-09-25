# Phase 2 — Product Catalog

Full company-scoped product catalog for iSPOS 2.0 back office. Phase 3 (POS cart) and Phase 4 (inventory movements) remain out of scope.

**Status: Complete** — verified September 2026.

## Stack additions
- [x] Catalog domain services under `app/Domains/Catalog/Services/`
- [x] Eloquent models with ULIDs, soft deletes, audit users, UUID on create
- [x] Company-scoped policies mirroring Organization module patterns

## Database
- [x] `categories`, `brands`, `units`, `taxes`, `price_groups`
- [x] `products`, `product_variants`, `product_barcodes`, `product_prices`
- [x] Restaurant: `product_type` (retail / menu_item / ingredient), `product_modifier_groups`, `product_modifier_options`, `product_components`, `product_ingredients`
- [x] Inventory alerts: `qty`, `ideal_qty`, `warning_qty` on products; low stock scope, dashboard widget, alerts page
- [x] Product images: multi-upload (max 10, 1 MB each), default appearance image, `product_images` table
- [x] `stores.price_group_id` FK to `price_groups`
- [x] Unique constraints: `(company_id, sku)`, `(company_id, barcode)`, codes per company

## RBAC
- [x] Permissions: `categories.*`, `brands.*`, `units.*`, `taxes.*`, `price_groups.*`, `products.*`, `inventory.view`
- [x] Assigned to Company Admin, Inventory Clerk; view on Cashier / managers where applicable
- [x] Policies for all catalog entities (company scope + global org access)

## Back office UI
- [x] Sidebar **Catalog** section: Products, Categories, Brands, Units, Taxes, Price Groups
- [x] Sidebar **Inventory** section: Low Stock Alerts
- [x] Lookup CRUD (Index + Form) for categories through price groups
- [x] Tabbed Products form: Basic, Pricing, Inventory (qty alerts), Variants, Modifiers, Components, Ingredients (menu items), Barcodes, Status/Audit
- [x] Company filter for global users on index pages
- [x] Product images upload field with default image selection
- [x] Tab validation error badges on product, company, and store forms
- [x] Unified index page headers on all admin list modules
- [x] Compact viewport-fit data tables: row `#`, sticky actions, clickable names, product thumbnails (products index)

## Demo data
- [x] Categories: Beverages, Food, Merchandise
- [x] Brands, units (pc, kg), VAT 12%, default price group `RETAIL`
- [x] 7 simple products + Iced Coffee with 2 variants
- [x] Restaurant sample: ingredient products, Classic Burger with modifier groups and recipe, Burger Combo Meal with product components
- [x] MAIN store linked to default price group

## Tests
- [x] `ProductPolicyTest` — company admin within company; cashier denied create
- [x] `ProductCrudTest` — create with variant, barcode, price group price; update via PUT; menu item with modifiers & ingredients
- [x] `CategoryCrudTest` — category + brand smoke CRUD
- [x] `ProductImageTest` — multi-upload, default image, 1 MB limit enforcement
- [x] `ProductComponentTest` — menu item with bundled product components
- [x] `LowStockAlertTest` — low stock scope, alerts page, inventory qty on product save

## Explicitly out of scope (Phase 3+; movements delivered in Phase 4)
- POS cart, payments, shifts (Phase 3)
- Purchasing, promotions, advanced image editing
- Public product API endpoints

## Verification (last run)

```bash
php artisan test          # 65 passed
npm run build             # success
php artisan migrate:status  # catalog migrations through 2026_09_09_090000 applied
```

Optional full reset with demo data:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```
