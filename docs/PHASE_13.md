# Phase 13 — POS Shift Reports (Z-Reports)

Shift-level reconciliation reports for POS cash drawer activity, sales totals, payment breakdown, and per-shift sale listing.

**Status: Complete** — September 2026.

Deferred from Phase 7 (“shift/Z reports”).

## Stack additions
- [x] `PosShiftReportService` — paginated shift list + Z-report detail
- [x] `ScopesShiftReports` — company/store RBAC for shift queries
- [x] `ShiftReportController` — index and show

## Reports
- [x] **Shift Reports index** — filterable list of open/closed shifts with gross sales and cash variance
- [x] **Shift Z-report detail** — opening/expected/counted cash, variance, sales KPIs, payment methods, sales list

## HTTP routes (`reports.view`)
- [x] `admin.reports.shift-reports.index` — shift list
- [x] `admin.reports.shift-reports.show` — Z-report for one shift

## Frontend (Inertia admin)
- [x] Reports nav: Shift Reports
- [x] `Admin/Reports/Shifts/Index.vue` — filters + table with “View Z-report”
- [x] `Admin/Reports/Shifts/Show.vue` — cash drawer, payments, sales in shift
- [x] Types: `resources/js/types/shiftReport.ts`

## Tests
- [x] `ShiftReportTest` — list access, Z-report detail, cashier denied

## Out of scope (future)
- PDF/thermal Z-report print template
- CSV export of shift reports
- X-report snapshot for open shifts from POS terminal UI
- Multi-payment-method beyond cash — delivered in Phase 16 (card/split)

## Verification

```bash
php artisan test --filter=ShiftReportTest
php artisan test --filter=Reporting
npm run build
```

Manual: login as `reports@demo.ispos.local` or `accountant@demo.ispos.local` → **Reports** → Shift Reports → open a closed demo shift Z-report.
