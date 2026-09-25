# Phase 10 — Financial Statements (P&L & Balance Sheet)

Profit & loss and balance sheet reports derived from posted GL journal activity.

**Status: Complete** — September 2026.

## Stack additions
- [x] `FinancialStatementReportService` — P&L period activity and balance sheet as-of queries
- [x] Controllers: `ProfitAndLossReportController`, `BalanceSheetReportController`

## Reports
- [x] **Profit & Loss** — revenue and expense accounts for a date range; net income = revenue − expenses
- [x] **Balance Sheet** — asset, liability, and equity balances as of a date; includes unclosed net income under equity

## Default COA
- [x] Added account `3000` Owner's Equity (equity type) to `DefaultChartOfAccountsService`

## HTTP routes
- [x] `admin.accounting.profit-and-loss.index` — P&L report (`accounting.view`)
- [x] `admin.accounting.balance-sheet.index` — balance sheet (`accounting.view`)

## Frontend (Inertia admin)
- [x] Accounting nav: Profit & Loss, Balance Sheet
- [x] Report pages with date filters, section tables, and summary cards

## Tests
- [x] `ProfitAndLossReportTest` — revenue, expenses, net income totals
- [x] `BalanceSheetReportTest` — assets balance to liabilities + equity + net income

## Out of scope (future)
- Year-end closing entries to retained earnings
- Cash flow statement
- Comparative periods / budget vs actual
- PDF export of financial statements
- Multi-currency revaluation

## Verification

```bash
php artisan test --filter=ProfitAndLossReportTest
php artisan test --filter=BalanceSheetReportTest
npm run build
```

Manual: login as `accountant@demo.ispos.local` → **Accounting** → Profit & Loss / Balance Sheet.
