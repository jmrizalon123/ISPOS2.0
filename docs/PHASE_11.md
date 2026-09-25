# Phase 11 — Offline Sync & Windows POS Shell

Device registration, offline catalog bootstrap, idempotent sale upload sync, admin device management, and a WebView2 desktop host stub.

**Status: Complete** — September 2026.

## Stack additions
- [x] `pos_devices`, `sync_logs` tables; `sales.pos_device_id`, `sales.sync_source`
- [x] Sync domain services under `app/Domains/Sync/Services/`
- [x] Sanctum device tokens (`pos-device:{id}`) with `sync:bootstrap`, `sync:push`, `sync:pull` abilities
- [x] `ResolvePosDevice` middleware for device-scoped sync routes

## API (`/api/v1`)
- [x] `POST /devices/register` — register terminal (session or bearer user auth + `pos.access`)
- [x] `GET /sync/bootstrap` — full offline catalog snapshot (products, categories, taxes, barcodes)
- [x] `GET /sync/catalog?since=` — incremental product changes
- [x] `POST /sync/push` — upload offline sales; idempotent on `client_uuid` → `sales.uuid`

## Admin UI
- [x] **POS Devices** — list registered terminals, last sync, revoke access (`sync.manage`)

## POS client groundwork
- [x] `useOfflineSync` composable — device registration, local catalog cache, outbox queue, push flush
- [x] `PosSyncStatus` — online/offline indicator on POS header; auto-register on setup

## Windows shell
- [x] `packages/ispos/pos/desktop/IsposPosHost/` — minimal .NET 8 WinForms + WebView2 launcher
- [x] Config-driven start URL (`config.json`)

## Tests
- [x] `PosDeviceRegisterTest`
- [x] `SyncBootstrapTest`
- [x] `SyncPushTest` — accept + duplicate client UUID idempotency

## Out of scope (future)
- Full local SQLite store database on device
- Bi-directional conflict resolution beyond sale UUID dedup
- MSIX/WiX installer and auto-updater feed
- Background sync service worker / true offline POS checkout UI
- Dedicated `/api/v1/pos` REST terminal (Inertia POS remains primary UI)

## Verification

```bash
php artisan migrate
php artisan test --filter=PosDeviceRegisterTest
php artisan test --filter=SyncBootstrapTest
php artisan test --filter=SyncPushTest
npm run build
```

Windows host (requires [.NET 8 SDK](https://dotnet.microsoft.com/download) + WebView2 runtime):

```bash
cd packages/ispos/pos/desktop/IsposPosHost
dotnet run
```

Manual: login as Company Admin → **System → POS Devices**. Open `/pos`, select store/register — device auto-registers and bootstraps catalog when online.
