# Phase 3 — POS Cart, Shifts & Payments

Authenticated Inertia POS at `/pos`: shift management, session cart, retail + restaurant checkout (modifiers and combo components), cash payments, sale persistence, void, and dashboard today-sales KPI.

**Status: Complete** — verified September 2026.

## Stack additions
- [x] Sales domain services under `app/Domains/Sales/Services/`
- [x] Eloquent models: `PosShift`, `Sale`, `SaleLine`, `SaleLineModifier`, `SaleLineComponent`, `SalePayment`
- [x] Policies: `PosShiftPolicy`, `SalePolicy` (store scope + `pos.void`)

## Database
- [x] `pos_shifts` — open/close per register, opening/closing float, expected cash
- [x] `sales` — UUID, sale number, org context, totals, completed/voided status
- [x] `sale_lines` — product snapshot, qty, unit/line totals, metadata JSON
- [x] `sale_line_modifiers` — modifier group/option snapshots with price adjustments
- [x] `sale_line_components` — combo component snapshots with included flag
- [x] `sale_payments` — cash tender records

## Backend
- [x] `PosContextService` — session store/register/shift keys, store access validation
- [x] `PosShiftService` — open/close shift; one open shift per register
- [x] `PosCatalogService` — POS product list, barcode lookup, price from store price group
- [x] `PosPricingService` — base + modifier adjustments + tax (inclusive/exclusive)
- [x] `PosCartService` — session cart CRUD; modifier min/max/required rules
- [x] `SaleService` — checkout transaction, payment, audit log, void
- [x] `SaleQueryService` — dashboard today sales / transactions / average

## HTTP routes (`auth`, `verified`, `permission:pos.access`)
- [x] `GET /pos` — setup or terminal
- [x] `POST /pos/session`, `POST /pos/shift/open`, `POST /pos/shift/close`
- [x] `GET /pos/catalog`, `GET /pos/catalog/lookup?barcode=`
- [x] `POST/PATCH/DELETE /pos/cart/lines`
- [x] `POST /pos/checkout`
- [x] `POST /pos/sales/{sale}/void` (`pos.void`)

## Frontend (Inertia POS)
- [x] `PosLayout` — full-screen dark shell, shift bar, cashier, clock, exit link
- [x] `POS/Setup` — store + register + opening float
- [x] `POS/Terminal` — product grid, search, cart panel, checkout
- [x] Modals: variant picker, modifier picker, combo components, checkout, close shift
- [x] Sidebar POS link for users with `pos.access`
- [x] Dashboard "Open POS" CTA + live today-sales stats

## Demo data
- [x] `cashier@demo.ispos.local` assigned to MAIN store
- [x] Classic Burger (modifiers) and Burger Combo Meal (components) usable at POS

## Tests
- [x] `PosAccessTest` — guest/unauthorized blocked; cashier allowed
- [x] `PosShiftTest` — open shift required; one open shift per register
- [x] `PosCheckoutTest` — retail product → sale + payment + line
- [x] `PosRestaurantCheckoutTest` — Classic Burger with modifier pricing
- [x] `PosComponentCheckoutTest` — combo component snapshots
- [x] `PosVoidTest` — void requires `pos.void`

## Explicitly out of scope (Phase 4 delivered inventory ledger; still later)
- Offline sync, `/api/v1/pos` terminal API
- Receipt printing — delivered in Phase 17 (browser/thermal-style; ESC/POS hardware later)
- Card/split payments — delivered in Phase 16

## Verification

```bash
php artisan migrate
php artisan test --filter=Sales
php artisan test
npm run build
```

Manual: login as `cashier@demo.ispos.local` → `/pos` → MAIN/REG1 → sell retail item + Classic Burger with modifiers + Burger Combo → verify dashboard sales count.
