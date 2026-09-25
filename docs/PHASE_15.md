# Phase 15 — Inter-Store Stock Transfers

Move tracked inventory between stores in the same company, with paired ledger movements and transfer history.

**Status: Complete** — September 2026.

Deferred from Phase 4 (“Inter-store transfers”).

## Stack additions
- [x] `stock_transfers` + `stock_transfer_items` (expanded document fields, types, statuses)
- [x] `StockTransferService` — validate, complete transfer, write movements
- [x] `StockTransferQueryService` — company/store-scoped listing + detail
- [x] Movement types: `transfer_out`, `transfer_in` (linked via `reference_type` / `reference_id`)

## Workflow
- [x] Create transfer with one or more product lines (qty > 0)
- [x] Source and destination must differ and share a company
- [x] Insufficient source stock is rejected
- [x] Completes immediately as **received**: deduct source, add destination
- [x] User must `canAccessStore` on both stores; requires `inventory.adjust` to create

## HTTP routes
- [x] `admin.inventory.transfers.index` — list (`inventory.view`)
- [x] `admin.inventory.transfers.create` / `.store` — new transfer (`inventory.adjust`)
- [x] `admin.inventory.transfers.show` — detail (`inventory.view`)

## Frontend (Inertia admin)
- [x] Inventory nav: **Stock Transfers**
- [x] Index, create form (multi-line), show page

## Tests
- [x] `StockTransferTest` — transfer moves qty, insufficient blocked, cashier forbidden, index lists

## Out of scope (future)
- Draft / in-transit / receive two-step workflow
- Transfer requests / approvals
- Serial/lot tracking on transfers
- GL inventory valuation posting for transfers
- Partial receive of an in-transit transfer

## Verification

```bash
php artisan migrate
php artisan test --filter=StockTransferTest
php artisan test --filter=Inventory
npm run build
```

Manual: login as `inventory@demo.ispos.local` → **Inventory → Stock Transfers → New transfer** → MAIN → NORTH → pick tracked product with stock → Complete. Verify Store Stock and Stock Movements (`transfer_out` / `transfer_in`).
