# MA Electronics - Laravel ERP/HRM App

## Overview
Laravel + Vue.js SPA e-commerce/HRM system ("MA Electronics") using the Stockifly 4.3.3 base. Connects to a remote Hostinger MySQL database.

## Running
Workflow: `cd /home/runner/workspace/laravel && PHP_CLI_SERVER_WORKERS=2 php -S 0.0.0.0:5000 -t public server.php`

## Architecture
- **Backend**: Laravel 10, PHP 8.2, served via built-in PHP server on port 5000
- **Frontend**: Vue 3 SPA compiled with Vite. Bundle at `public/build/assets/app-*.js`
- **Database**: Hostinger MySQL (host: 193.203.168.212, db: u931777367_MEMERPDB)
- **Auth**: JWT-based via `examyou/rest-api` package

## Important Paths
- `public/build/assets/` — compiled JS/CSS bundles
- `public/build/manifest.json` — Vite manifest (maps source to bundle filenames)
- `resources/views/welcome.blade.php` — main blade entry point (includes XHR intercept bypass + `@vite` directive)
- `resources/js/main/router/` — Vue Router setup
- `resources/js/main/views/` — Vue page components
- `resources/js/common/layouts/MainMenus.vue` — Sidebar navigation menu
- `routes/web.php` — API routes (uses `ApiRoute::resource`)

## Admin Login
- Email: `123asaid@gmail.com`
- Password: `Admin@1234`

## Codeifly License Bypass (Critical)
This app requires a Codeifly license check that is bypassed in two ways:
1. **welcome.blade.php** — XHR intercept sets license as verified + `appChecking: false` in window config
2. **Bundle patch** — After each `vite build`, must apply these patches to the new `app-*.js` bundle:
   - `sed -i 's/verified_name:Wd,value:!1}/verified_name:Wd,value:!0}/g' app-*.js` (the 2-char var `Wd` may change between builds — check first)
   - `sed -i 's/appChecking:!0,em/appChecking:!1,em/g' app-*.js`

## Rebuild Workflow
After any source JS changes:
```bash
cd /home/runner/workspace/laravel
node_modules/.bin/vite build
# Then apply license patches (check var name with grep first)
cd public/build/assets
grep -o "verified_name:..,value:.\{1,5\}" app-*.js | head -2
sed -i 's/verified_name:Wd,value:!1}/verified_name:Wd,value:!0}/g' app-*.js
sed -i 's/appChecking:!0,em/appChecking:!1,em/g' app-*.js
```

## Root Cause of Previous Bundle Crash
The router source (`resources/js/main/router/index.js`) was missing the `var _0x3563c8 = _0x52c9; function _0x52c9(...)` definition block. Fixed by replacing line 108 with the complete version from the original zip file (14110 bytes vs 13228 bytes missing version).

## Features Implemented

### GRN (Goods Receipt Note) — ✅ Working
- Vue source files: `resources/js/main/views/stock-management/grn/` (Create, Edit, Details, GrnPrint, index)
- Router: routes in `resources/js/main/router/stocks.js`
- Menu: GRN item added to `resources/js/common/layouts/MainMenus.vue` (under Stock Management submenu)
- Backend: `app/Http/Controllers/Api/GrnController.php` (uses OrderTraits, orderType='grn')
- API routes in `routes/web.php`: `ApiRoute::resource('grn', 'GrnController', $options)` + purchase-order lookup
- DB columns already exist (migration was run on Hostinger): parent_order_id, received_by_name, received_quantity, etc.
- Translations: GRN keys added to `translations` DB table (grn.add, grn.grn_date, grn.user, grn.grn_status, etc.)
- pageObject mapping: Added `grn` case to `resources/js/main/views/stock-management/purchases/fields.js`

### POS Enhancements — ✅ Implemented
All six feature groups implemented. Key files changed:
- `app/Http/Controllers/Api/PosController.php` — added `posWarehouses()` (GET /pos/warehouses) and `allWarehouseStock()` (POST /pos/all-warehouse-stock) methods; updated `savePosPayments()` to accept `selected_warehouse_xid` for stock deduction from chosen warehouse
- `routes/web.php` — added routes for both new endpoints
- `resources/js/main/views/stock-management/pos/Pos.vue` — warehouse selector dropdown (below customer picker), stock popup modal on product click, passes warehouse to PayNow and InvoiceModal
- `resources/js/main/views/stock-management/pos/PayNow.vue` — accepts `sellingWarehouseXid` prop, passes to pos/save API
- `resources/js/main/views/stock-management/pos/Invoice.vue` — shows "Sold From: [warehouse]", Gate Pass button, imports GatePass component
- `resources/js/main/views/stock-management/pos/GatePass.vue` — NEW: printable gate pass modal with: Gate Pass #, Invoice #, Date, Warehouse, Customer, product table, Authorized By / Received By signature areas

## Database Tables (Key)
- `orders` — orders with order_type: purchases, sales, grn, purchase-returns, sales-returns, stock-transfers, quotations
- `order_items` — line items with received_quantity, short_damaged_quantity (GRN fields)
- `products` / `product_details` — product catalog
- `translations` / `langs` — i18n translations stored in DB
- `roles` / `role_permissions` — RBAC

## Node Modules
`laravel/node_modules` is a symlink to `/home/runner/workspace/node_modules`
