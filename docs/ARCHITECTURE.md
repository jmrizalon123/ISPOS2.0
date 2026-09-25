# Architecture (Phase 1–6)

## Stack

- **Backend:** Laravel 12, Sanctum (session SPA + future token API), Spatie Permission
- **Frontend:** Vue 3 + TypeScript + Inertia.js + Tailwind + Vite
- **Data:** MySQL 8 (ULID primary keys on domain tables)
- **Money:** future monetary columns reserved as `DECIMAL(19,4)`; default currency PHP (₱)

## Package layout

The host Laravel app holds domain models, services, policies, and API routes. UI is split into two Composer packages:

| Package | Path | Responsibility |
|---------|------|----------------|
| `ispos/backoffice` | `packages/ispos/backoffice/` | Admin UI, auth, dashboard, KDS, shared Vue components |
| `ispos/pos` | `packages/ispos/pos/` | POS terminal UI, device sync controllers, WebView2 host |

Shared Inertia middleware lives in `app/Http/Middleware/HandleInertiaRequests.php` and selects the root Blade view per route (`backoffice::app` vs `pos::pos`).

## Multi-store model

```
Company
  └── Store(s)          unique (company_id, code)
        └── Register(s) unique (store_id, code)
User
  ├── belongs to Company (nullable for Super Admin)
  └── many-to-many Stores (store_user) for access scoping
```

Policies enforce company/store boundaries. Super Admin bypasses org scope.

## Catalog model (Phase 2)

```
Company
  ├── Category(s)      unique (company_id, category_code); optional parent_id
  ├── Brand(s)         unique (company_id, brand_code)
  ├── Unit(s)          unique (company_id, unit_code)
  ├── Tax(es)          unique (company_id, tax_code)
  ├── PriceGroup(s)    unique (company_id, group_code); one is_default per company
  └── Product(s)       unique (company_id, sku); product_type: retail | menu_item | ingredient
        ├── ProductVariant(s)
        ├── ProductBarcode(s)   unique (company_id, barcode)
        ├── ProductPrice(s)     per price_group + product + optional variant
        ├── ProductImage(s)     multi-upload; one default → products.image URL
        ├── ProductModifierGroup(s) / ProductModifierOption(s)   menu items
        ├── ProductComponent(s)   bundled sellable products (combos)
        └── ProductIngredient(s)  recipe lines (menu items)

Store.price_group_id → PriceGroup (retail tier for that branch)
```

Services live in `app/Domains/Catalog/Services/`. `ProductService` transactionally syncs variants, barcodes, prices, modifiers, components, ingredients, and images on create/update.

Inventory (Phase 2 + 4): `ideal_qty` and `warning_qty` thresholds on products; on-hand qty lives in per-store balances. `products.qty` is deprecated (kept for migration compat only).

```
Store
  └── StoreProductInventory(s)   unique (store_id, product_id); qty on-hand
        └── StockMovement(s)     append-only ledger (sale, void, adjustment, …)
```

| Movement type | Trigger |
|---------------|---------|
| `sale` | POS checkout (`SaleInventoryService`) |
| `void` | Sale void — reverses original movement |
| `adjustment` | Manual ± via `InventoryAdjustmentService` |
| `recipe_consumption` / `component_consumption` | Menu item / combo explosion |
| `purchase_receipt` | PO goods receive (`PurchaseReceivingService`) |
| `purchase_return` | Return to supplier (`PurchaseReturnService`) |
| `transfer_out` / `transfer_in` | Inter-store transfer (`StockTransferService`) |

Services live in `app/Domains/Inventory/Services/`:

| Service | Role |
|---------|------|
| `StoreInventoryService` | Read/update balances; init rows for new tracked products |
| `StockMovementService` | Append movement, atomically update balance |
| `SaleInventoryService` | Deduct on checkout; reverse on void |
| `InventoryAdjustmentService` | Manual adjustments with reason |
| `StockTransferService` | Inter-store transfer complete + paired movements |
| `StockTransferQueryService` | Transfer index and detail |
| `StoreInventoryQueryService` | Stock index, movements ledger, low-stock queries |
| `LowStockAlertService` | Dashboard stats, header bell, `/admin/inventory/low-stock` |

`SaleService` calls `SaleInventoryService` after checkout lines persist and inside the void transaction.

## Purchasing model (Phase 5)

```
Supplier(s)     unique (company_id, supplier_code)
  └── PurchaseOrder(s)   unique (store_id, po_number); status draft → approved → received
        └── PurchaseOrderLine(s)   ordered_qty, received_qty, unit_cost
  └── PurchaseReturn(s)   optional PO link; post deducts stock
```

Services live in `app/Domains/Purchasing/Services/`:

| Service | Role |
|---------|------|
| `SupplierService` | Vendor CRUD |
| `PurchaseOrderService` | Draft PO create/update, approve, cancel |
| `PurchaseReceivingService` | Receive lines → `purchase_receipt` movements |
| `PurchaseReturnService` | Post returns → `purchase_return` movements |
| `PurchaseOrderQueryService` | PO index pagination and filters |

Receiving and returns integrate with `StockMovementService` using polymorphic `reference_type` / `reference_id` on PO/return lines.

## CRM model (Phase 6)

```
Customer(s)     unique (company_id, customer_code)
  ├── LoyaltyProgram (optional) + loyalty_points balance
  ├── LoyaltyTransaction(s)   earn | adjust | void_reversal
  ├── CustomerMembership(s)   → MembershipPlan (discount %, price group)
  └── Sale(s)                 optional customer_id + promotion_id

Promotion(s)    unique (company_id, promo_code); applies_to all | products | categories
```

Services live in `app/Domains/Crm/Services/`:

| Service | Role |
|---------|------|
| `CustomerService` | Customer CRUD, POS search |
| `LoyaltyProgramService` | Program CRUD, default program |
| `LoyaltyTransactionService` | Earn on sale, void reversal, manual adjust |
| `MembershipPlanService` | Plan CRUD |
| `CustomerMembershipService` | Assign/cancel membership |
| `PromotionService` | Promotion CRUD, activate/cancel |
| `PromotionApplicationService` | Best promo + membership discount for POS cart |

POS cart session stores optional `customer_id`. Checkout applies membership discount and best active promotion, persists `customer_id` / `promotion_id` on `Sale`, and earns loyalty points via `LoyaltyTransactionService`.

## Sales model (Phase 3)

```
PosShift (open/closed per register)
  └── Sale(s)              unique (store_id, sale_number); status completed | voided; optional customer + promotion
        ├── SaleLine(s)    product snapshot, qty, unit/line totals
        │     ├── SaleLineModifier(s)   modifier snapshots
        │     └── SaleLineComponent(s)  combo component snapshots
        └── SalePayment(s) cash / card (split supported)
```

Services live in `app/Domains/Sales/Services/`:

| Service | Role |
|---------|------|
| `PosContextService` | Session store/register/shift; `canAccessStore` validation |
| `PosShiftService` | Open/close shift; one open shift per register |
| `PosCatalogService` | POS catalog, barcode lookup, store price group pricing |
| `PosPricingService` | Line totals with modifier adjustments and tax |
| `PosCartService` | Session cart; modifier group rules |
| `SaleService` | Checkout transaction, void, refund, audit |
| `SaleReceiptService` | Printable POS receipt payload (branding, lines, payments) |
| `SaleQueryService` | Dashboard today-sales KPIs, 14-day trend, refundable sale lookup |

Reporting services live in `app/Domains/Reporting/Services/`:

| Service | Role |
|---------|------|
| `SalesSummaryReportService` | KPI summary, daily trend, by-store breakdown |
| `SalesRegisterReportService` | Paginated sales register; base query for export |
| `ProductSalesReportService` | Top products by revenue and quantity |
| `SalesReportExportService` | Chunked CSV export of sales register |
| `PosShiftReportService` | Shift list + Z-report detail (cash reconciliation, payments, sales) |

Admin report pages require `reports.view`; CSV export requires `reports.export`. Filters default to the last 30 days and respect company/store RBAC via `ScopesSalesReports`.

Accounting services live in `app/Domains/Accounting/Services/`:

| Service | Role |
|---------|------|
| `ChartOfAccountService` | GL account CRUD |
| `JournalEntryService` | Manual journal draft/post workflow |
| `GlPostingService` | Auto-post POS sales, purchase receipts, returns, supplier payments |
| `DefaultChartOfAccountsService` | Seed default COA + POS/purchase account mappings |
| `TrialBalanceReportService` | Posted activity trial balance |
| `VendorBillService` | AP bills from purchase receipts |
| `SupplierPaymentService` | Record payments and allocate to open bills |
| `ApReportService` | AP aging buckets |
| `FinancialStatementReportService` | Profit & loss and balance sheet from posted GL |

When `company.enable_accounting` is true, `SaleService` calls `GlPostingService` on checkout and void; `PurchaseReceivingService` posts Dr Inventory / Cr AP on goods receipt; `PurchaseReturnService` reverses inventory/AP on post; supplier payments post Dr AP / Cr Cash. GL mappings include `pos_cash`, `pos_card` (Card Clearing), `pos_sales_revenue`, `pos_output_tax`, `purchase_inventory`, and `purchase_ap`. POS sales debit cash and/or card clearing by tender amounts.

POS UI: authenticated Inertia pages at `/pos` (`permission:pos.access`). Session cart lives in Laravel session until checkout persists a `Sale`. After checkout, a printable receipt opens at `/pos/sales/{sale}/receipt` (store/company receipt header/footer branding).

## Settings hierarchy

`SettingService` resolves keys System → Company → Store → Register (most specific wins).

## Auth & RBAC

- Back office: session cookies via Sanctum SPA / Breeze Inertia flow
- Permissions seeded for Phase 1 modules plus reserved names for later phases
- `audit_logs` append-only trail; `login_activities` success/failure

## API foundation

- `GET /api/v1/health` — public health envelope
- `GET /api/v1/me` — authenticated identity + roles/permissions

JSON envelope via `App\Support\ApiResponse`.

## Offline sync (Phase 11)

```
PosDevice(s)   unique (company_id, fingerprint); Sanctum token pos-device:{id}
  └── SyncLog(s)   bootstrap | pull | push attempts

Sale.sync_source   pos | offline_sync
Sale.uuid          client idempotency key for offline push
```

Services live in `app/Domains/Sync/Services/`:

| Service | Role |
|---------|------|
| `PosDeviceService` | Register/revoke devices; issue sync tokens |
| `SyncBootstrapService` | Full catalog bootstrap + incremental product pull |
| `SyncPushService` | Idempotent offline sale ingestion |
| `PosDeviceQueryService` | Admin device listing |
| `SyncLogService` | Sync audit trail per device |

API routes (device bearer token via `pos.device` middleware):

- `POST /api/v1/devices/register`
- `GET /api/v1/sync/bootstrap`
- `GET /api/v1/sync/catalog?since=`
- `POST /api/v1/sync/push`

Frontend: `useOfflineSync` composable caches catalog in `localStorage`, queues offline sales, and flushes on reconnect. POS header shows online/sync status.

## Kitchen display (Phase 12)

```
Sale (menu_item lines)
  └── KitchenTicket   unique (sale_id); status pending → preparing → ready → completed
```

Services live in `app/Domains/Kds/Services/`:

| Service | Role |
|---------|------|
| `KitchenTicketService` | Create from sale, cancel on void, advance status |
| `KitchenTicketQueryService` | Active tickets for store board |
| `KdsContextService` | Session store selection for kitchen terminals |

`SaleService` and `SyncPushService` call `KitchenTicketService::createFromSale()` after checkout. Void cancels active tickets.

Routes (Inertia + JSON poll at `/kds`, `kds.view` permission):

- `GET /kds` — setup or board
- `GET /kds/tickets` — active ticket JSON
- `PATCH /kds/tickets/{id}` — bump workflow

## Windows POS host

WebView2 is the locked shell decision. Minimal launcher: `packages/ispos/pos/desktop/IsposPosHost/` (.NET 8 WinForms). MSIX/WiX installer and auto-update remain future work.
