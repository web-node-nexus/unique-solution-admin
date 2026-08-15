# Unique Solution — Electronics Shop Admin Panel

Production-ready Laravel 12 admin dashboard for **Unique Solution** (Kargil Chowk, Megha Road, Kurud - 493663).

Includes a flexible EAV product variant system (category-specific attributes → cartesian SKU generation), Spatie roles/permissions, DomPDF invoices, Excel import/export, and Yajra DataTables.

## Requirements

- PHP 8.2+ (8.4 recommended) with extensions: `pdo_sqlite` or `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`
- Composer 2.x
- Node.js is **not required** (admin UI uses CDN assets)

## Quick start

```bash
composer install
cp .env.example .env   # if .env missing
php artisan key:generate

# Default uses SQLite (database/database.sqlite). For MySQL, set DB_* in .env.

touch database/database.sqlite   # SQLite only
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Open: [http://127.0.0.1:8000/admin/login](http://127.0.0.1:8000/admin/login)

## Default admin logins

| Role         | Email                         | Password   |
|--------------|-------------------------------|------------|
| Super Admin  | `admin@uniquesolution.com`    | `password` |
| Manager      | `manager@uniquesolution.com`  | `password` |
| Staff        | `staff@uniquesolution.com`    | `password` |
| Support      | `support@uniquesolution.com`  | `password` |

## Shop settings (seeded)

- **Shop name:** Unique Solution  
- **Tagline:** आपकी अपनी दुकान  
- **Address:** Kargil Chowk, Megha Road, Kurud - 493663  

Shown in the admin navbar/sidebar, PDF invoices, and packing slips.

## Key features

- Dynamic category attributes + auto variant combinations (product wizard)
- **Category reorder anytime** (drag-drop + ↑↓) — app reads `sort_order` from API
- **App carousel banners** — admin uploads slides, sets order/links; app uses `/api/v1/banners`
- Inventory adjustments with logs & low-stock alerts
- Orders with status timeline, invoice PDF, packing slip
- Payments, refunds workflow, coupons, reviews
- Reports (sales / category / brand / best-selling / refunds / stock valuation)
- Spatie RBAC with permission-gated sidebar

## App APIs (for future mobile/web app)

Base: `/api/v1`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/shop` | Shop name, address, tax, shipping |
| GET | `/home` | Banners + root categories + featured products |
| GET | `/banners` | Active carousel slides (ordered) |
| GET | `/categories` | Categories by `sort_order` (`?roots_only=1`, `?parent_id=`) |
| GET | `/brands` | Active brands |

Admin changes to category order or banners appear on these APIs immediately.

## Optional: MySQL

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=unique_solution
DB_USERNAME=root
DB_PASSWORD=
```

Then run `php artisan migrate:fresh --seed` again.
