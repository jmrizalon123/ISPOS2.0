# Phase 9 — Accounts Payable & Purchasing GL

Purchase receipt GL posting, vendor bills, supplier payments, and AP aging — integrated with Phase 5 purchasing and Phase 8 accounting.

**Status: Complete** — September 2026.

## Stack additions
- [x] AP services under `app/Domains/Accounting/Services/`
- [x] Models: `PurchaseReceipt`, `PurchaseReceiptLine`, `VendorBill`, `SupplierPayment`, `SupplierPaymentAllocation`
- [x] Policies: `VendorBillPolicy`, `SupplierPaymentPolicy`
- [x] Permissions: `ap.view`, `ap.post`, `ap.pay`

## Database
- [x] `purchase_receipts` + `purchase_receipt_lines` — goods receipt records per receive batch
- [x] `vendor_bills` — AP open items linked to receipts
- [x] `supplier_payments` + `supplier_payment_allocations` — cash payments applied to bills

## GL posting
- [x] Purchase receive: Dr Inventory / Cr Accounts Payable
- [x] Purchase return post: Dr AP / Cr Inventory (reversal)
- [x] Supplier payment: Dr AP / Cr Cash
- [x] Extended `DefaultChartOfAccountsService` — Inventory (1300), AP (2100) + purchase mappings
- [x] `GlPostingService` — `postPurchaseReceipt`, `reversePurchaseReturn`, `postSupplierPayment`

## Purchasing integration
- [x] `PurchaseReceivingService` — creates receipt, vendor bill, and GL on each receive batch
- [x] `PurchaseReturnService` — GL reversal when return is posted

## HTTP routes
- [x] `admin.vendor-bills.index` — vendor bill list (`ap.view`)
- [x] `admin.supplier-payments.index` — payment history (`ap.view`)
- [x] `admin.supplier-payments.create|store` — record payment (`ap.pay`)
- [x] `admin.accounting.ap-aging.index` — AP aging report (`ap.view`)

## Frontend (Inertia admin)
- [x] Accounting nav: Vendor Bills, Supplier Payments, AP Aging
- [x] Types: `vendorBill.ts`, `supplierPayment.ts`
- [x] Supplier payment form with bill allocation grid

## Demo data
- [x] Default COA seed includes Inventory and AP accounts when accounting is enabled
- [x] Receive a demo PO after re-seeding to generate sample vendor bills

## Tests
- [x] `PurchasingGlPostingTest` — receive GL, return GL, supplier payment GL
- [x] `ApPolicyTest` — role access for AP screens
- [x] `ApAgingReportTest` — aging buckets and open balances

## Out of scope (future)
- Vendor credit memos
- Multi-currency AP
- Three-way PO/receipt/invoice match
- Check printing / bank reconciliation
