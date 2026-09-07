# Unique Solution — Mobile (Expo SDK 54)

Electronics ecommerce app for Unique Solution (Kurud).

## Run

```bash
# API
cd .. && php artisan serve --host=0.0.0.0 --port=8000

# App
npm start
```

Set `app.json` → `extra.apiUrl` to your LAN IP for a physical device.

## Features

- Onboarding intro
- Home: banners, categories, featured, deals, recently viewed
- Categories / Brands / Search / Premium filters (category, brand, attributes, price, sort)
- Product detail + variants, wishlist, add to cart
- Cart → COD checkout (login required)
- Auth: register / login / logout (Sanctum)
- Account dashboard: order stats, recent orders
- Order history + tracking timeline
- Saved addresses
- Deals & coupons
- Announcements

## Test customer API

```bash
POST /api/v1/auth/register
POST /api/v1/auth/login
GET  /api/v1/dashboard   (Bearer token)
POST /api/v1/checkout
```
