# Phase 16 — Card & Split Payments

POS checkout supports cash, card, and split (cash + card) tenders with correct drawer expected cash and GL posting.

**Status: Complete** — September 2026.

Deferred from Phase 3 / Phase 13 (cash-only checkout).

## Stack additions
- [x] Multi-tender checkout on `SaleService::checkout()` (`cash` / `card` payment lines)
- [x] `PosCheckoutRequest` — `payments[]` + optional `cash_tendered` (legacy cash-only still works)
- [x] GL mapping `pos_card` → Chart of Account **1020 Card Clearing**
- [x] `GlPostingService::postSale` — debit cash and/or card clearing by tender amounts
- [x] Offline sync push accepts `payments[]` (or legacy single `payment`)

## Workflow
- [x] **Cash** — tendered ≥ total; payment amount = grand total; change = tendered − total
- [x] **Card** — full amount as `card` payment; optional auth/reference
- [x] **Split** — cash + card amounts must equal grand total; cash tendered ≥ cash portion
- [x] Expected shift cash counts **cash payments only** (card does not enter the drawer)
- [x] Refunds remain cash-only (unchanged from Phase 14)

## HTTP
- [x] `POST /pos/checkout` accepts:
  - Legacy: `{ cash_tendered }`
  - Multi-tender: `{ payments: [{ payment_method, amount, reference? }], cash_tendered? }`

## Frontend (Inertia POS)
- [x] Checkout modal tender modes: **Cash / Card / Split**
- [x] Split UI: cash amount, card amount, cash tendered, optional card reference

## Tests
- [x] `PosCardSplitPaymentTest` — card-only, split, amount mismatch, split GL debits

## Out of scope (future)
- E-wallet / gift card / store credit tenders
- Card terminal / PIN pad hardware integration
- Refund to original tender (card reverse)
- Partial line-item payments / tips
- Bank settlement / clearing reconciliation of card clearing account

## Verification

```bash
php artisan migrate
php artisan test --filter=PosCardSplitPaymentTest
php artisan test --filter=Sales
npm run build
```

Manual: login as `cashier@demo.ispos.local` → `/pos` → open shift → Checkout → try **Card** and **Split** → confirm sale payments and that close-shift expected cash only includes cash portions.
