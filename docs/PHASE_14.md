# Phase 14 — POS Refunds

Full cash refund of completed POS sales with inventory restore, loyalty/GL reversal, and shift cash drawer impact.

**Status: Complete** — September 2026.

Deferred from Phase 3/4 (`pos.refund`).

## Stack additions
- [x] `sale_refunds` table — one refund record per sale (amount, reason, refunding shift)
- [x] `sales.refunded_at`, `sales.refunded_by`, `sales.refund_reason`; status `refunded`
- [x] `SaleService::refund()` — full refund workflow
- [x] Inventory reverse with movement type `refund`
- [x] Cross-shift refunds reduce expected cash on the refunding shift

## Workflow
- [x] Only `completed` sales can be refunded (not voided / already refunded)
- [x] Requires open POS shift and `pos.refund` permission
- [x] Restores stock, reverses loyalty points, posts GL reversal, cancels active KDS tickets
- [x] Same-shift refund: sale leaves completed cash totals via status change
- [x] Later-shift refund: cash out applied to current shift expected cash

## HTTP routes (`pos.access` + `pos.refund`)
- [x] `GET /pos/sales/refundable` — JSON lookup of completed store sales
- [x] `POST /pos/sales/{sale}/refund` — issue cash refund with reason

## Frontend (Inertia POS)
- [x] **Refund** button on terminal (when `can_refund`)
- [x] `RefundModal` — search sales, select, enter reason, submit

## RBAC
- [x] `pos.refund` — Store Manager, Supervisor (seeded Phase 1)
- [x] Cashiers cannot refund; managers can

## Tests
- [x] `PosRefundTest` — permission denied, refund restores stock, voided blocked, lookup lists sales

## Out of scope (future)
- Partial line-item refunds
- Card/original-tender refunds (cash only)
- Manager override PIN for cashiers
- Refund receipt print template
- Exchange (refund + new sale) single flow

## Verification

```bash
php artisan migrate
php artisan test --filter=PosRefundTest
php artisan test --filter=Sales
npm run build
```

Manual: login as `storemanager@demo.ispos.local` → `/pos` → open shift → **Refund** → pick a completed sale → reason → confirm. Verify stock restored and sale status `refunded`.
