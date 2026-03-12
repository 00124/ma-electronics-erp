# MA Electronics ERP - Replit.md

## Overview

MA Electronics ERP is a full-featured inventory/ERP management system built on **Laravel 12 + Vue 3** (Stockifly-based). The active application is the Laravel + Vue.js SPA in the `laravel/` directory.

The `client/`, `server/`, and `shared/` directories contain a legacy minimal React/Node.js prototype that is **no longer the running application**.

---

## User Preferences

Preferred communication style: Simple, everyday language.

---

## Running the App

**Workflow command:**
```
cd /home/runner/workspace/laravel && PHP_CLI_SERVER_WORKERS=4 php -S 0.0.0.0:5000 -t public server.php
```

The PHP built-in server serves the Laravel app on port 5000. Vue.js assets are pre-built into `laravel/public/build/`.

## Admin Login
- Email: `123asaid@gmail.com`
- Password: `Admin@1234`

---

## System Architecture

### Backend (Laravel 12 / PHP 8.2)
- **Framework**: Laravel 12 with PHP 8.2
- **Auth**: JWT via `php-open-source-saver/jwt-auth`
- **API**: RESTful API using `examyou/rest-api` package with `ApiRoute`
- **Entry**: `laravel/public/index.php` → served by `php -S` via `server.php`
- **Routes**: `laravel/routes/web.php`
- **Controllers**: `laravel/app/Http/Controllers/Api/`

### Frontend (Vue 3 + Ant Design Vue)
- **Framework**: Vue 3 SPA
- **UI Library**: Ant Design Vue 4
- **State**: Vuex 4
- **Router**: Vue Router 4
- **Build Tool**: Vite 5
- **Assets**: Pre-built to `laravel/public/build/`
- **Entry**: `laravel/resources/js/app.js`
- **Pages**: `laravel/resources/js/main/views/`
- **Router**: `laravel/resources/js/main/router/`
- **Layouts**: `laravel/resources/js/common/layouts/`

### Database
- **Database**: MySQL (remote Hostinger at `193.203.168.212`)
- **DB Name**: `u931777367_MEMERPDB`
- **ORM**: Laravel Eloquent
- **Connection**: Set via `MYSQL_DATABASE_URL` env var or `laravel/.env`

---

## Key Files
- `laravel/.env` — Laravel environment config (DB, APP_URL, JWT_SECRET)
- `laravel/resources/views/welcome.blade.php` — main blade entry (includes license bypass + asset loading)
- `laravel/public/build/manifest.json` — Vite asset manifest
- `laravel/public/build/assets/` — compiled JS/CSS bundles

---

## Rebuild Workflow (After Source JS Changes)

```bash
cd /home/runner/workspace/laravel
node_modules/.bin/vite build
```

Then apply license bypass patches to the new bundle:
```bash
cd public/build/assets
# Check the variable name first (it changes between builds):
grep -o "verified_name:..,value:.\{1,5\}" app-*.js | head -2
# Apply patches (replace XX with the actual 2-char variable found above):
sed -i 's/verified_name:XX,value:!1}/verified_name:XX,value:!0}/g' app-*.js
sed -i 's/appChecking:!0,em/appChecking:!1,em/g' app-*.js
```

---

## Features

### Implemented
- **Dashboard** — Sales summary, charts, recent orders
- **Products** — CRUD, barcode generation, Excel import
- **Stock Management** — GRN (Goods Receipt Note), stock transfers
- **POS** — Point of Sale with warehouse selector, customer quick-add, salesman selection
- **Gate Pass** — Printable gate pass document
- **Purchases** — Purchase orders
- **Sales** — Sales orders, invoice printing
- **Brands / Categories / Warehouses** — Master data management
- **Users / Roles** — User management with RBAC
- **Reports** — Various ERP reports

### Key Custom Additions (MA Electronics)
- GRN with received quantity tracking
- POS customer quick-add (search by phone, auto-create)
- POS salesman selection
- Gate Pass with warehouse/salesman info
- Invoice showroom-style layout
- Excel import for products and stock
- Company logo on printed documents

---

## Codeifly License Bypass (Critical)
The app requires a license check that is bypassed in two places:
1. **`welcome.blade.php`** — XHR/fetch intercept returns fake verified response for codeifly.com calls
2. **Bundle patch** — Applied after each `vite build` (see Rebuild Workflow above)

---

## External Dependencies
- **MySQL** — Remote Hostinger MySQL at `193.203.168.212`
- **PHP 8.2** — Available in Replit nix environment
- **Composer 2** — PHP dependency manager
- **Node.js 20** — For Vite asset builds
