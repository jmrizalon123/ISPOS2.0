# Phase 4 — Inventory Movements & Store Stock

Per-store inventory balances with an append-only stock movement ledger. POS checkout deducts stock; sale void reverses it. Manual adjustments are gated on `inventory.adjust`. Recipe/component explosion for restaurant items and combos.

**Status: Complete** — verified September 2026.

## Stack additions
- [x] Inventory domain services under `app/Domains/Inventory/Services/`
- [x] Eloquent models: `StoreProductInventory`, `StockMovement`
- [x] Policy: `StockMovementPolicy` (`inventory.view`, `inventory.adjust`, store scope)

## Database
- [x] `store_product_inventories` — per-store on-hand qty; unique `(store_id, product_id)`
- [x] `stock_movements` — append-only ledger with `qty_before` / `qty_after`, sale traceability, reversal links
- [x] Data migration: backfill migration + demo seeder — MAIN store gets legacy `products.qty`; other stores start at zero
- [x] `products.qty` deprecated (kept for backward compat; no longer written on create/update)

## Backend
- [x] `StoreInventoryService` — get/create balance, init zero rows for new tracked products
- [x] `StockMovementService` — atomic movement + balance update
- [x] `SaleInventoryService` — deduct on checkout; reverse on void (retail, recipe, combo components)
- [x] `InventoryAdjustmentService` — manual ± adjustment with reason and audit
- [x] `StoreInventoryQueryService` — paginated stock index, movements ledger, low-stock queries
- [x] `LowStockAlertService` — rewritten for store-scoped `store_product_inventories`
- [x] `SaleService` hooks — deduct after checkout lines; reverse inside void transaction
- [x] `ProductService` — strips `qty` from payload; initializes store rows when `track_inventory`

## Stock deduction rules
- [x] **Retail + tracked** — deduct line qty at sale store
- [x] **Menu item + not tracked** — deduct required recipe ingredients (`is_optional = false`) × line qty
- [x] **Combo (`has_components`)** — recurse included `sale_line_components` (burger → ingredients; cola → direct)
- [x] **Void** — reverse original movements via `reversal_of_id`, movement type `void`
- [x] No hard block on negative qty (oversell allowed; ledger records it)

## HTTP routes
- [x] `GET /admin/inventory/stock` — per-store balances (`inventory.view`)
- [x] `GET /admin/inventory/movements` — movement ledger with filters (`inventory.view`)
- [x] `GET/POST /admin/inventory/adjustments` — manual adjust form (`inventory.adjust`)
- [x] `GET /admin/inventory/low-stock` — store-filterable low-stock alerts

## Frontend (Inertia admin)
- [x] `Admin/Inventory/Stock/Index.vue` — store + product balances, stock status badge
- [x] `Admin/Inventory/Movements/Index.vue` — ledger table with sale links
- [x] `Admin/Inventory/Adjustments/Form.vue` — store picker, product select, ± qty, reason
- [x] `Admin/Inventory/LowStock.vue` — store-scoped low-stock list
- [x] Navigation: Store Stock, Stock Movements, Adjust Stock, Low Stock Alerts
- [x] Product form: removed editable on-hand qty; read-only note; keep ideal/warning thresholds
- [x] Product index: company total qty (`total_qty` sum across stores) for tracked products
- [x] Header bell + dashboard low-stock props use store inventory

## Demo data
- [x] MAIN store gets legacy demo qty values from `products.qty`
- [x] NORTH store gets partial stock (e.g. Cola qty 20) to show per-store differences

## Tests
- [x] `StoreInventoryInitTest` — new tracked product creates zero rows at all stores
- [x] `InventoryAdjustmentTest` — manual adjust + movement; requires `inventory.adjust`
- [x] `SaleInventoryDeductionTest` — retail POS checkout deducts store balance
- [x] `SaleRecipeDeductionTest` — Classic Burger deducts bun + patty
- [x] `SaleComponentDeductionTest` — Burger Combo deducts cola + burger ingredients
- [x] `SaleVoidReversalTest` — void restores all movements for sale
- [x] `StoreLowStockAlertTest` — low stock uses store inventory, not `products.qty`
- [x] `LowStockAlertTest` — updated for store-scoped inventory
- [x] `DashboardInventoryTest` — dashboard loads with store-scoped low-stock stats

## Explicitly out of scope (Phase 5 delivered purchasing; still later)
- Inter-store transfers (delivered Phase 15)
- Modifier-to-ingredient links (Double Patty won't deduct extra patty yet)
- Variant-level stock
- Offline sync

Refunds (`pos.refund`) delivered in Phase 14.

## Verification

```bash
php artisan migrate
php artisan test --filter=Inventory
php artisan test --filter=Sales
php artisan test
npm run build
```

Manual: login as `cashier@demo.ispos.local` → sell Cola at MAIN → verify **Store Stock** and **Stock Movements**; void sale → stock restored. Login as `inventory@demo.ispos.local` → **Adjust Stock** → add qty at NORTH store.
