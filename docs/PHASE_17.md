# Phase 17 — POS Receipt Printing

Browser / thermal-style sale receipts after checkout, using store/company branding already on Companies and Stores.

**Status: Complete** — September 2026.

Deferred from Phase 3 (“receipt printing hardware”).

## Stack additions
- [x] `SaleReceiptService` — builds printable receipt payload (header/footer, lines, payments)
- [x] `PosReceiptController` — Inertia receipt page (`pos.access` + `SalePolicy::view`)
- [x] Flash `print_sale_id` / `print_change` after checkout → POS opens print window

## Branding resolution
- [x] Header: store `receipt_header` → company `receipt_header`
- [x] Footer: store `receipt_footer` → company `receipt_footer` → setting `company.receipt_footer`
- [x] Store address / phone / TIN on receipt body

## HTTP
- [x] `GET /pos/sales/{sale}/receipt` — preview receipt (Inertia)
- [x] `GET /pos/sales/{sale}/receipt/print` — standalone HTML for silent print
- [x] Checkout redirect flashes `print_sale_id` + `print_change`

## Frontend
- [x] Silent print after checkout — **no popup window**
  - Fetches `/pos/sales/{sale}/receipt/print` with session cookies, then:
  - **WebView2 host:** `{ type: "silent-print-html", html }` → `NavigateToString` + `PrintAsync` (no dialog)
  - **Browser:** hidden iframe `srcdoc` print (no popup; OS print dialog only — browsers block fully silent print)
- [x] `POS/Receipt.vue` preview + Print button uses the same silent path
- [x] Voided / refunded sales show status banner when reprinted
- [x] Host writes `print.log` next to the exe for diagnostics

## Tests
- [x] `PosReceiptTest` — checkout flash + receipt Inertia + print HTML; outsider forbidden

## Out of scope (future)
- Dedicated ESC/POS / Bluetooth thermal driver beyond WebView2 PrintAsync
- Automatic cash drawer kick
- Dedicated refund/Z-report print templates
- Email / SMS digital receipt
- Multi-copy kitchen chit printing (KDS covers kitchen display)

## Verification

```bash
php artisan test --filter=PosReceiptTest
php artisan test --filter=Sales
npm run build
```

Manual: login as `cashier@demo.ispos.local` → `/pos` → complete a sale → allow popup → Print. Or open **Companies/Stores** branding fields and reprint via `/pos/sales/{id}/receipt`.
