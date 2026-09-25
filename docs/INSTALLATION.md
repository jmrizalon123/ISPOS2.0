# Installation (Phase 1)

## Requirements

- PHP 8.2+ (8.3+ preferred) with extensions: `pdo_mysql` or `sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- Composer 2
- Node.js 20+ and npm
- MySQL 8 (recommended) **or** SQLite for local smoke tests
- Redis optional (queues/cache/Horizon later)

## XAMPP notes

1. Start **Apache** + **MySQL** from the XAMPP control panel.
2. Create database `ispos` (utf8mb4).
3. Point the vhost or use `php artisan serve` from `c:\xampp\htdocs\ispos.2.0\backoffice`.

## Setup

```bash
composer install
copy .env.example .env   # Windows: copy
php artisan key:generate
```

Configure `.env`:

```env
APP_NAME=iSPOS
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ispos
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
# Optional Redis:
# CACHE_STORE=redis
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
```

Then:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
# or for local HMR:
npm run dev
php artisan serve --port=8002
```

Open `http://127.0.0.1:8002/login`.

Demo accounts: see [`DEMO_CREDENTIALS.md`](DEMO_CREDENTIALS.md).

## Queues

Phase 1 does not require workers for core screens. When you enable queued jobs:

```bash
php artisan queue:work
```

## Tests

```bash
php artisan test
```
