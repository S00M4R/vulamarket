# Vula Market

> South Africa's simplest C2C marketplace — list in minutes, pay safely, ship with ease.

Vula Market is a self-hosted, PHP-based consumer-to-consumer marketplace built for the South African market. It integrates **Yoco** for secure card payments, **The Courier Guy PUDO (TCG Locker)** for Locker-to-Door shipping, and an **escrow system** that holds funds until the buyer confirms delivery - protecting both parties on every transaction.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Order & Payment Flow](#order--payment-flow)
- [Shipping Model](#shipping-model)
- [Admin Panel](#admin-panel)
- [Wallet & Payouts](#wallet--payouts)
- [Security Checklist](#security-checklist)
- [Default Credentials](#default-credentials)
- [Contributing](#contributing)

---

## Features

- **Listings** — Any registered user can create, manage, and delete product listings with image uploads
- **Search & Browse** — Paginated homepage with full-text search across titles and descriptions
- **Yoco Checkout** — Hosted payment page with idempotency support; payment verified server-side before order confirmation
- **Escrow Protection** — Funds held as `paid_in_escrow` until the buyer marks the order as received
- **TCG Locker Shipping** — Locker-to-Door (L2D) model: seller drops at a PUDO locker, TCG delivers to the buyer's address
- **Live Shipping Quotes** — AJAX endpoint fetches real-time shipping rates from the Shiplogic/TCG API before checkout
- **In-Order Chat** — Buyer and seller can communicate directly on the order detail page
- **Waybill Generation** — Printable waybill page per order
- **Seller Wallet** — Completed order earnings (minus platform fee) accumulate in a wallet; sellers request EFT payouts
- **Admin Panel** — Manage users, listings, orders, and shipments; approve/mark payout requests
- **CSRF Protection** — All state-changing forms use token validation
- **Role-Based Access** — Buyer, Seller, and Admin roles with route-level guards

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.1+ |
| Database | SQLite 3 (via PDO) |
| Web Server | Apache 2.4+ with `mod_rewrite` |
| Payments | [Yoco Checkout API](https://developers.yoco.com) |
| Shipping | [TCG PUDO / Shiplogic API](https://sandbox.api-pudo.co.za) |
| Frontend | Vanilla HTML/CSS/JS (no framework) |
| Image Storage | Local filesystem (`/uploads/`) |

---

## Project Structure

```
vulamarket/
├── config/
│   └── config.php          ← API keys, app settings, constants
├── db/
│   └── vulamarket.sqlite   ← SQLite database (auto-created by seed.php)
├── includes/
│   ├── db.php              ← PDO singleton + schema migrations
│   ├── helpers.php         ← Auth, CSRF, flash messages, utilities
│   ├── layout.php          ← Shared HTML header/footer
│   ├── yoco.php            ← Yoco Checkout API wrapper
│   └── shipping.php        ← TCG PUDO (Shiplogic) API wrapper
├── public/
│   ├── css/app.css         ← Global stylesheet
│   └── js/app.js           ← Minimal JS (shipping quote AJAX, etc.)
├── uploads/                ← Product images (must be writable)
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── listings/
│   ├── create.php
│   ├── view.php
│   └── delete.php
├── orders/
│   ├── index.php           ← Order list (buyer/seller views)
│   ├── view.php            ← Order detail, escrow status, chat
│   ├── checkout.php        ← Creates order + initiates Yoco session
│   ├── track.php           ← Shipment tracking
│   └── waybill.php         ← Printable waybill
├── payment/
│   ├── success.php         ← Yoco callback — verifies + confirms order
│   ├── cancel.php
│   └── failure.php
├── api/
│   ├── shipping_quote.php  ← AJAX: returns real-time shipping rates (JSON)
│   └── lockers.php         ← AJAX: returns nearby locker locations (JSON)
├── admin/
│   ├── index.php           ← Admin dashboard
│   └── shipments.php       ← Shipment management
├── public/
│   ├── orders.php          ← Public order tracking
│   └── wallet.php          ← Seller wallet view
├── wallet.php              ← Payout request management
├── index.php               ← Homepage — browse & search listings
├── seed.php                ← Development data seeder
├── setup.php               ← Database schema initialiser
└── .htaccess               ← URL routing + directory protection
```

---

## Requirements

- PHP **8.1+** with extensions: `pdo_sqlite`, `curl`, `gd`, `fileinfo`
- Apache **2.4+** with `mod_rewrite` enabled
- SQLite **3**
- Outbound HTTPS access to Yoco and TCG APIs

---

## Installation

### 1. Copy Files

```bash
cp -r vulamarket/ /var/www/html/
```

### 2. Set Permissions

```bash
chmod -R 755 /var/www/html/vulamarket/
chmod -R 777 /var/www/html/vulamarket/uploads/
chmod -R 777 /var/www/html/vulamarket/db/
```

### 3. Configure API Keys

Edit `config/config.php`:

```php
define('APP_URL',           'https://yourdomain.co.za');
define('YOCO_SECRET_KEY',   'sk_live_YOUR_YOCO_KEY');
define('TCG_API_KEY',       'YOUR_TCG_PUDO_KEY');
define('ADMIN_EMAIL',       'admin@yourdomain.co.za');
define('PLATFORM_FEE_PCT',  5); // Marketplace fee percentage
```

Update `SELLER_LOCKER_TERMINAL` with the default seller pickup locker ID.

### 4. Seed the Database

```bash
php seed.php
```

This creates the schema and inserts:
- Admin user, test buyer, and test seller accounts
- 4 sample product listings

> ⚠️ **Change the admin password immediately after first login.**

### 5. Configure Apache VirtualHost

```apache
<VirtualHost *:80>
    ServerName yourdomain.co.za
    DocumentRoot /var/www/html/vulamarket

    <Directory /var/www/html/vulamarket>
        AllowOverride All
        Require all granted
    </Directory>

    # Protect the database from direct HTTP access
    <Directory /var/www/html/vulamarket/db>
        Require all denied
    </Directory>
</VirtualHost>
```

Enable rewrite and restart:

```bash
a2enmod rewrite && systemctl restart apache2
```

---

## Configuration

All runtime configuration lives in `config/config.php`. Key constants:

| Constant | Description |
|---|---|
| `APP_URL` | Public base URL (auto-detected in dev, set manually in prod) |
| `YOCO_SECRET_KEY` | Yoco secret key (`sk_test_...` or `sk_live_...`) |
| `TCG_API_KEY` | TCG PUDO Bearer token |
| `TCG_API_BASE` | TCG API endpoint (swap sandbox → production URL to go live) |
| `PLATFORM_FEE_PCT` | Percentage deducted from seller earnings on each completed sale |
| `UPLOAD_MAX_SIZE` | Maximum image upload size (default: 5MB) |
| `SESSION_LIFETIME` | Session duration in seconds (default: 86400 / 24h) |

---

## Order & Payment Flow

```
Buyer views listing
  → Enters delivery address
  → Gets live shipping quote (TCG API via AJAX)
  → Clicks "Proceed to Payment"
  → checkout.php creates order (status: pending) + Yoco hosted session
  → Buyer completes payment on Yoco's hosted page
  → payment/success.php verifies payment with Yoco API
  → Order status updated to: paid_in_escrow
  → Seller ships parcel, updates order via in-order chat
  → Buyer clicks "Mark as Received"
  → Order status updated to: completed
  → Seller balance credited (amount minus platform fee %)
  → Seller requests payout via wallet.php
  → Admin EFTs the amount → marks payout as paid
```

---

## Shipping Model

Vula Market uses a **Locker-to-Door (L2D)** model via The Courier Guy's PUDO network:

1. The **seller** drops the parcel at their nearest TCG locker terminal.
2. **TCG** collects from the locker and delivers to the **buyer's door**.
3. A live shipping quote is fetched from the TCG API at checkout based on the buyer's address and the seller's default locker.

The `includes/shipping.php` module handles all API communication with the TCG PUDO sandbox/production endpoints.

---

## Admin Panel

Access at `/admin/index.php` (admin role required).

Capabilities:
- View all users, listings, and orders
- Update order statuses manually
- Manage shipments (`/admin/shipments.php`)
- Approve and mark seller payout requests as paid

---

## Wallet & Payouts

- When an order reaches `completed` status, the seller's wallet is credited with the sale amount minus `PLATFORM_FEE_PCT`.
- Sellers view their balance and request EFT payouts at `/wallet.php`.
- Admins process payouts manually and mark them as paid in the admin panel.

---

## Security Checklist

Before going to production:

- [ ] Replace `YOCO_SECRET_KEY` with your live key (`sk_live_...`)
- [ ] Replace `TCG_API_KEY` with your production TCG credentials and update `TCG_API_BASE`
- [ ] Enable HTTPS; set `secure=true` on session cookies
- [ ] Change all default seed account passwords
- [ ] Set strict file permissions on `db/` and `uploads/`
- [ ] Disable PHP error output: `display_errors = Off` in `php.ini`
- [ ] Add rate limiting to `/auth/login.php` and `/auth/register.php`
- [ ] Schedule regular backups of `db/vulamarket.sqlite`
- [ ] (Optional) Register a Yoco webhook for reliable payment confirmation:
  - URL: `https://yourdomain.co.za/api/yoco_webhook.php`
  - Event: `payment.succeeded`

---

## Default Credentials

> These are **development only**. Remove or rotate before deploying to production.

| Role | Email | Password |
|---|---|---|
| Admin | `admin@vulamarket.co.za` | `admin123` |
| Buyer | `buyer@test.co.za` | `buyer123` |
| Seller | `seller@test.co.za` | `seller123` |

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "Add your feature"`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a pull request

Please keep pull requests focused — one feature or fix per PR.

---

*Built for the South African market. Powered by Yoco & The Courier Guy.*
