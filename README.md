# iSPOS 2.0 — Enterprise POS Back Office

Enterprise multi-store POS back office: Laravel 12 + Vue 3 / Inertia / TypeScript / Tailwind.

**Phase 1 status:** Complete — see [`docs/PHASE_1.md`](docs/PHASE_1.md).  
**Phase 2 status:** Complete — see [`docs/PHASE_2.md`](docs/PHASE_2.md).  
**Phase 3 status:** Complete — see [`docs/PHASE_3.md`](docs/PHASE_3.md).  
**Phase 4 status:** Complete — see [`docs/PHASE_4.md`](docs/PHASE_4.md).  
**Phase 5 status:** Complete — see [`docs/PHASE_5.md`](docs/PHASE_5.md).  
**Phase 6 status:** Complete — see [`docs/PHASE_6.md`](docs/PHASE_6.md).  
**Phase 7 status:** Complete — see [`docs/PHASE_7.md`](docs/PHASE_7.md).  
**Phase 8 status:** Complete — see [`docs/PHASE_8.md`](docs/PHASE_8.md).  
**Phase 9 status:** Complete — see [`docs/PHASE_9.md`](docs/PHASE_9.md).  
**Phase 10 status:** Complete — see [`docs/PHASE_10.md`](docs/PHASE_10.md).  
**Phase 11 status:** Complete — see [`docs/PHASE_11.md`](docs/PHASE_11.md).  
**Phase 12 status:** Complete — see [`docs/PHASE_12.md`](docs/PHASE_12.md).  
**Phase 13 status:** Complete — see [`docs/PHASE_13.md`](docs/PHASE_13.md).  
**Phase 14 status:** Complete — see [`docs/PHASE_14.md`](docs/PHASE_14.md).  
**Phase 15 status:** Complete — see [`docs/PHASE_15.md`](docs/PHASE_15.md).  
**Phase 16 status:** Complete — see [`docs/PHASE_16.md`](docs/PHASE_16.md).  
**Phase 17 status:** Complete — see [`docs/PHASE_17.md`](docs/PHASE_17.md).

## Project layout

The Laravel host app lives in this folder (`ispos.2.0/backoffice/`). UI packages are under `packages/ispos/backoffice/` (admin) and `packages/ispos/pos/` (terminal). Run all Composer, Artisan, and npm commands from here.

## Quick start

See [`docs/INSTALLATION.md`](docs/INSTALLATION.md). Demo logins: [`docs/DEMO_CREDENTIALS.md`](docs/DEMO_CREDENTIALS.md). Architecture: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

```bash
cd backoffice   # if you are at the ispos.2.0 root
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve --port=8002
```

Open `http://127.0.0.1:8002/login` — default demo password is `password`.

## Implemented modules

**Phase 1 — Foundation**
- Companies / stores / registers / users / roles / settings / audit logs
- Sanctum session auth + Spatie RBAC (13 roles)
- Design system shell (light/dark/system, Ctrl+K palette)
- API `GET /api/v1/health` and `GET /api/v1/me`
- Back office UI package (`packages/ispos/backoffice/`)
- WebView2 POS desktop host (`packages/ispos/pos/desktop/`)

**Phase 2 — Product catalog**
- Products with variants, barcodes, price-group pricing, and multi-image upload
- Categories, brands, units, taxes, price groups (company-scoped CRUD + audit)
- Restaurant: menu items, modifiers, recipe ingredients, combo product components
- Inventory thresholds: ideal / warning qty, low-stock page, dashboard widget
- Back-office index tables: compact viewport-fit layout, sticky `#` and actions columns

**Phase 3 — POS cart & payments**
- Authenticated `/pos` terminal (Inertia): store/register setup, shift open/close
- Session cart with variants, restaurant modifiers, and combo components
- Cash checkout → persisted sales, lines, modifier/component snapshots, payments
- Sale void (`pos.void`); dashboard today-sales KPI from completed sales

**Phase 4 — Inventory movements & store stock**
- Per-store balances (`store_product_inventories`) and append-only movement ledger
- POS checkout deducts stock; void reverses; recipe/component explosion for menu items and combos
- Manual stock adjustments (`inventory.adjust`); store-scoped low-stock alerts
- Admin pages: Store Stock, Stock Movements, Adjust Stock

**Phase 5 — Purchasing**
- Suppliers, purchase orders (draft → approved → receive), purchase returns
- Goods receipt and returns update store inventory via movement ledger
- Admin pages: Suppliers, Purchase Orders, Purchase Returns

**Phase 6 — CRM**
- Customers, loyalty programs (point ledger), membership plans, promotions
- POS: attach customer, auto-apply promos/member discounts, earn loyalty points
- Admin pages: Customers, Loyalty Programs, Membership Plans, Promotions

**Phase 7 — Sales reporting**
- Sales summary KPIs, daily trend, store breakdown
- Sales register with search; product sales ranking
- CSV export (`reports.export`); dashboard live stats and 14-day chart
- Admin pages: Sales Summary, Sales Register, Product Sales

**Phase 8 — Accounting**
- Chart of accounts, manual journal entries (draft → post)
- Trial balance report; auto GL posting from POS sales and void reversals
- Admin pages: Chart of Accounts, Journal Entries, Trial Balance

**Phase 9 — Accounts payable**
- Purchase receipt GL (Dr Inventory / Cr AP); return and payment posting
- Vendor bills from goods receipt; supplier payments with bill allocations
- AP aging report
- Admin pages: Vendor Bills, Supplier Payments, AP Aging

**Phase 10 — Financial statements**
- Profit & loss (income statement) for a date range
- Balance sheet as of a date with unclosed net income
- Admin pages: Profit & Loss, Balance Sheet

**Phase 11 — Offline sync & Windows shell**
- POS device registration with Sanctum sync tokens
- Offline catalog bootstrap and idempotent sale upload (`/api/v1/sync/*`)
- Admin page: POS Devices (revoke terminals)
- WebView2 desktop host (`packages/ispos/pos/desktop/IsposPosHost/`)

**Phase 12 — Kitchen display (KDS)**
- Auto kitchen tickets from POS menu-item sales
- Three-column board: New → Preparing → Ready → Bump
- Kitchen staff role + `/kds` full-screen UI

**Phase 13 — Shift reports (Z-reports)**
- Shift list with cash variance and sales totals
- Z-report detail: drawer reconciliation, payment breakdown, sales in shift
- Admin page: Shift Reports (`reports.view`)

**Phase 14 — POS refunds**
- Full cash refund of completed sales (`pos.refund`)
- Inventory restore, loyalty/GL reversal, KDS cancel
- POS Refund modal with sale lookup

**Phase 15 — Inter-store stock transfers**
- Transfer tracked products between stores (same company)
- Paired `transfer_out` / `transfer_in` stock movements
- Admin pages: Stock Transfers (list, create, detail)

**Phase 16 — Card & split payments**
- POS checkout: cash, card, or split (cash + card)
- Card clearing GL account; shift expected cash counts cash only
- Checkout modal tender modes

**Phase 17 — POS receipt printing**
- Printable sale receipt (80mm-style) with store/company branding
- Auto-open print window after checkout; reprint via `/pos/sales/{id}/receipt`

MSIX/WiX installer and full local DB sync are later enhancements.
