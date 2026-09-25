# Phase 1 — Completion Checklist

Enterprise POS foundation. All items below are implemented unless noted.

## Stack & bootstrap
- [x] Laravel 12 + PHP 8.2+
- [x] Vue 3 + TypeScript + Inertia.js + Tailwind CSS + Vite
- [x] Laravel Sanctum (session + token-ready)
- [x] Spatie Laravel Permission (RBAC)
- [x] MySQL 8 (recommended; SQLite optional for local smoke tests)

## Architecture folders
- [x] `app/Domains/Organization/` — Company, Store, Register services
- [x] `app/Domains/Identity/` — User service
- [x] `app/Actions/` — e.g. `RecordLoginActivity`
- [x] `app/Services/` — `SettingService`, `AuditLogger`
- [x] `app/Support/` — `ApiResponse`, `ErrorReference`, `Ulid`
- [x] `packages/ispos/pos/desktop/` — WebView2 README + host stub (Phase 11 installer deferred)

## Database
- [x] ULID primary keys on domain tables
- [x] `companies`, `stores`, `registers`, extended `users`, `store_user`
- [x] `settings` (system → company → store → register hierarchy)
- [x] `audit_logs`, `login_activities`
- [x] Spatie roles/permissions with ULID morph keys for users

## Auth & RBAC
- [x] Login / logout / password reset (Breeze, restyled)
- [x] Registration disabled (admin-created users only)
- [x] 12 default roles seeded
- [x] Phase 1 permissions + reserved names for later phases
- [x] Policies for Company, Store, Register, User, Role, Setting, AuditLog
- [x] Store/register scoped access via `canAccessStore()`

## Back office UI
- [x] Login (split-screen brand + form)
- [x] Dashboard (placeholder KPIs + chart shells)
- [x] Companies, Stores, Registers, Users, Roles CRUD
- [x] Settings (company / store / register tabs)
- [x] Audit Logs (searchable, paginated)
- [x] Design system: Button, Input, Select, Modal, Badge, Card, DataTable, Toast, EmptyState, Skeleton, Alert, Dropdown, Tabs, StatCard
- [x] AppLayout: sidebar nav, theme (light/dark/system), Ctrl+K command palette

## API & POS stub
- [x] `GET /api/v1/health`
- [x] `GET /api/v1/me` (Sanctum)
- [x] `/pos` placeholder (“Phase 3”)

## Docs & demo data
- [x] `docs/INSTALLATION.md`
- [x] `docs/ARCHITECTURE.md`
- [x] `docs/DEMO_CREDENTIALS.md` (13 roles)
- [x] Demo company `DEMO`, 2 stores, 3 registers

## Tests
- [x] Login success/failure + login_activities + audit on login
- [x] Unauthorized store/register access blocked
- [x] Company/store/register CRUD policies
- [x] Role assignment
- [x] `SettingService` hierarchy unit test

## Explicitly out of scope (later phases)
Products, POS cart/payments, inventory, purchasing, CRM, accounting, offline sync, KDS, reporting, Windows installer, hardware drivers.

Run verification:

```bash
php artisan migrate:fresh --seed
php artisan test
npm run build
```
