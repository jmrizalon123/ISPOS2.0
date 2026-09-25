# Phase 5 — Purchasing (Suppliers, POs, Receiving, Returns)

Company-scoped suppliers, purchase orders (draft → approved → receive), goods receipt into per-store inventory via the stock movement ledger, and purchase returns.

**Status: Complete** — verified September 2026.

## Stack additions
- [x] Purchasing domain services under `app/Domains/Purchasing/Services/`
- [x] Eloquent models: `Supplier`, `PurchaseOrder`, `PurchaseOrderLine`, `PurchaseReturn`, `PurchaseReturnLine`
- [x] Policies: `SupplierPolicy`, `PurchaseOrderPolicy`, `PurchaseReturnPolicy`

## Database
- [x] `suppliers` — company-scoped vendor master
- [x] `purchase_orders` + `purchase_order_lines` — PO header/lines with partial receiving
- [x] `purchase_returns` + `purchase_return_lines` — return to supplier
- [x] Stock movements: `purchase_receipt`, `purchase_return` linked via polymorphic reference

## Backend
- [x] `SupplierService` — CRUD, paginate
- [x] `PurchaseOrderService` — create/update draft, approve, cancel, delete
- [x] `PurchaseReceivingService` — partial/full receive → `StockMovementService`
- [x] `PurchaseReturnService` — draft create/update, post (deduct stock)
- [x] `PurchaseOrderQueryService` — paginated PO index with filters

## Workflow
- [x] PO: `draft` → `approved` → `partially_received` / `received`
- [x] Only tracked products on PO/return lines
- [x] Receive qty validated against remaining ordered qty
- [x] Return post requires `purchasing.approve`

## HTTP routes (`purchasing.view` / action policies)
- [x] `admin.suppliers.*` — supplier CRUD
- [x] `admin.purchase-orders.*` — PO CRUD + approve/cancel
- [x] `admin.purchase-orders.receive.*` — goods receipt
- [x] `admin.purchase-returns.*` — return CRUD + post

## Frontend (Inertia admin)
- [x] Purchasing nav section: Suppliers, Purchase Orders, Purchase Returns
- [x] Supplier Index/Form
- [x] PO Index/Form/Receive
- [x] Return Index/Form with post action
- [x] Stock movements filter includes purchase types

## Demo data
- [x] Metro Foods Supply + Beverage Distributors Inc.
- [x] Approved PO (Cola + Water) awaiting receipt at MAIN
- [x] Received PO (Bread) with movements
- [x] Posted demo purchase return

## Tests
- [x] `SupplierCrudTest`
- [x] `PurchaseOrderWorkflowTest`
- [x] `PurchaseReceivingTest`
- [x] `PurchaseReceivingInventoryTest` — untracked products rejected on PO lines
- [x] `PurchaseReturnTest`
- [x] `PurchasePolicyTest`

## Explicitly out of scope (Phase 7+)
- Supplier payments / accounts payable (Phase 8)
- PO email/PDF, batch/expiry tracking
- Variant-level receiving, inter-store PO transfers

## Verification

```bash
php artisan migrate
php artisan test --filter=Purchasing
php artisan test
npm run build
```

Manual: login as `purchasing@demo.ispos.local` → approve demo PO → receive Cola at MAIN → verify **Store Stock** and **Stock Movements** (`purchase_receipt`).
