# Phase 7 — Sales & Financial Reporting

Company- and store-scoped sales reports with summary KPIs, sales register, product sales ranking, CSV export, and dashboard wired to live sales data.

**Status: Complete** — verified September 2026.

## Stack additions
- [x] Reporting domain services under `app/Domains/Reporting/Services/`
- [x] Shared scoping concern: `ScopesSalesReports` (company/store RBAC + date/status filters)
- [x] Permissions: `reports.view`, `reports.export` (seeded in Phase 1)

## Backend
- [x] `SalesSummaryReportService` — gross/net sales, transactions, void count, average ticket, daily trend, by-store breakdown
- [x] `SalesRegisterReportService` — paginated sales register with search
- [x] `ProductSalesReportService` — top products by revenue and quantity
- [x] `SalesReportExportService` — chunked CSV export of sales register
- [x] `ResolvesReportFilters` — default 30-day date range, company/store/status filters
- [x] `SaleQueryService` — dashboard today stats + 14-day trend delegate to summary service

## HTTP routes
- [x] `admin.reports.sales-summary.index` — summary report (`reports.view`)
- [x] `admin.reports.sales-register.index` — sales register (`reports.view`)
- [x] `admin.reports.product-sales.index` — product sales (`reports.view`)
- [x] `admin.reports.sales-register.export` — CSV export (`reports.export`)

## Frontend (Inertia admin)
- [x] Reports nav section: Sales Summary, Sales Register, Product Sales
- [x] Shared filter composable: `useReportFilters.ts`
- [x] Types: `resources/js/types/salesReport.ts`
- [x] Dashboard: live today sales/transactions, 14-day trend chart, link to sales summary

## Demo data
- [x] Six completed demo sales across MAIN and NORTH stores (last 7 days) with lines and payments
- [x] Idempotent: skipped when demo company already has sales

## Tests
- [x] `SalesSummaryReportTest` — access + store scoping
- [x] `ProductSalesReportTest` — top products by revenue/qty
- [x] `ReportPolicyTest` — cashier denied, report viewer access, export permission
- [x] `SalesRegisterExportTest` — accountant CSV export

## Explicitly out of scope (Phase 8+)
- Accounting GL posting, profit & loss, balance sheet (delivered Phase 8–10)
- Shift/Z reports (delivered Phase 13)
- Payment-method breakdown beyond cash, tax filing exports
- Scheduled email reports, PDF templates
- Inventory valuation and COGS reporting

## Verification

```bash
php artisan migrate
php artisan test --filter=Reporting
php artisan test
npm run build
```

Manual: login as `accountant@demo.ispos.local` or `reports@demo.ispos.local` → **Reports** → Sales Summary / Register / Product Sales → export CSV from Sales Register.
