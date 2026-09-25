# Phase 8 — Accounting (GL, Journal Entries, POS Posting)

Company-scoped chart of accounts, manual journal entries, trial balance report, and automatic GL posting from POS sales checkout and void.

**Status: Complete** — verified September 2026.

## Stack additions
- [x] Accounting domain services under `app/Domains/Accounting/Services/`
- [x] Eloquent models: `ChartOfAccount`, `GlAccountMapping`, `JournalEntry`, `JournalEntryLine`
- [x] Policies: `ChartOfAccountPolicy`, `JournalEntryPolicy`
- [x] Permissions: `accounting.view`, `accounting.post` (seeded in Phase 1)

## Database
- [x] `chart_of_accounts` — company GL master (asset/liability/equity/revenue/expense)
- [x] `gl_account_mappings` — POS posting account map (cash, revenue, output tax)
- [x] `journal_entries` + `journal_entry_lines` — draft/posted journals with source link to sales

## Backend
- [x] `ChartOfAccountService` — CRUD + paginate
- [x] `JournalEntryService` — draft create/update, post, delete draft; balanced lines enforced
- [x] `GlPostingService` — auto-post POS sale; reversal on void
- [x] `DefaultChartOfAccountsService` — seed default COA + POS mappings
- [x] `TrialBalanceReportService` — posted activity by account
- [x] `SaleService` integration — posts GL on checkout/void when `company.enable_accounting`

## HTTP routes
- [x] `admin.chart-of-accounts.*` — chart of accounts CRUD (`accounting.view` / `accounting.post`)
- [x] `admin.journal-entries.*` — journal entry CRUD (`accounting.view` / `accounting.post`)
- [x] `admin.journal-entries.post` — post draft entry (`accounting.post`)
- [x] `admin.accounting.trial-balance.index` — trial balance (`accounting.view`)

## Frontend (Inertia admin)
- [x] Accounting nav section: Chart of Accounts, Journal Entries, Trial Balance
- [x] Types: `chartOfAccount.ts`, `journalEntry.ts`
- [x] Journal entry form with balanced line grid and post action

## Demo data
- [x] Demo company `enable_accounting = true`
- [x] Default COA: Cash (1010), Output VAT (2200), Sales Revenue (4010), General Expenses (5100)
- [x] POS GL mappings for cash, revenue, and output tax accounts

## Tests
- [x] `AccountingPolicyTest`
- [x] `ChartOfAccountCrudTest`
- [x] `JournalEntryWorkflowTest`
- [x] `JournalEntryBalanceValidationTest` — rejects unbalanced lines
- [x] `TrialBalanceReportTest`
- [x] `SalesGlPostingTest` — checkout posts GL; void creates reversal

## Explicitly out of scope (Phase 9+)
- Accounts payable / supplier payments
- P&L, balance sheet, cash flow statement UI
- Bank reconciliation, invoicing, payment allocation
- Purchasing receipt GL posting
- Multi-currency GL, cost center / department dimensions

## Verification

```bash
php artisan migrate
php artisan test --filter=Accounting
php artisan test
npm run build
```

Manual: login as `accountant@demo.ispos.local` → **Accounting** → Chart of Accounts / Journal Entries / Trial Balance. Complete a POS sale and verify a posted system journal entry appears.
