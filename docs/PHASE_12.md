# Phase 12 — Kitchen Display System (KDS)

Live kitchen order queue for menu-item sales from POS, with ticket workflow and store-scoped kitchen board.

**Status: Complete** — September 2026.

## Stack additions
- [x] `kitchen_tickets` table linked to `sales` (one ticket per sale with menu items)
- [x] KDS domain services under `app/Domains/Kds/Services/`
- [x] `KitchenTicketPolicy` — store-scoped access via `kds.view`

## Ticket workflow
- [x] Auto-create on POS checkout when sale includes `menu_item` products
- [x] Auto-create on offline sync push (`SyncPushService`)
- [x] Cancel active ticket when sale is voided
- [x] Status flow: `pending` → `preparing` → `ready` → `completed`

## HTTP routes (`auth`, `verified`, `permission:kds.view`)
- [x] `GET /kds` — store setup or kitchen board
- [x] `POST /kds/session` — persist selected store in session
- [x] `GET /kds/tickets` — JSON poll endpoint for active tickets
- [x] `PATCH /kds/tickets/{kitchen_ticket}` — advance ticket status

## Frontend (Inertia)
- [x] `KdsLayout` — full-screen kitchen shell
- [x] `KDS/Setup` — select store
- [x] `KDS/Board` — three-column queue (New / Preparing / Ready) with auto-refresh
- [x] Sidebar nav: **Kitchen Display** (`kds.view`)

## RBAC
- [x] `kds.view` permission (seeded Phase 1)
- [x] **Kitchen Staff** role uses KDS; cashiers use POS only

## Tests
- [x] `KdsAccessTest` — guest/cashier blocked; kitchen staff allowed
- [x] `KitchenTicketOnCheckoutTest` — menu item creates ticket; retail-only skipped; void cancels
- [x] `KitchenTicketStatusTest` — status advance + cross-store forbidden

## Out of scope (future)
- Expo / runner display for front-of-house pickup
- Per-line ticket routing (grill vs drinks stations)
- WebSocket / SSE realtime (polling used for now)
- KDS bump bar hardware integration
- Order firing before payment (tab / send-to-kitchen before checkout)

## Verification

```bash
php artisan migrate
php artisan test --filter=Kds
php artisan test --filter=KitchenTicket
npm run build
```

Manual: sell a **Classic Burger** at `/pos`, then login as `kitchen@demo.ispos.local` → **Kitchen Display** → select MAIN store → ticket appears in **New** column.
