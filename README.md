# SEAPEDIA Backend (Laravel API)

> RESTful API Backend untuk platform marketplace hasil laut segar SEAPEDIA

![PHP 8.3+](https://img.shields.io/badge/PHP-8.3-blue.svg)
![Laravel 13](https://img.shields.io/badge/Laravel-13-red.svg)
![Sanctum Auth](https://img.shields.io/badge/Sanctum-Auth-brightgreen.svg)

## 📋 Deskripsi Project

SEAPEDIA adalah platform marketplace untuk produk hasil laut segar. Backend ini menyediakan RESTful API untuk:

- **Multi-role users**: Admin, Seller, Buyer, Driver
- **Single-store checkout**: Cart hanya bisa berisi produk dari satu toko
- **Wallet system**: Topup via DuitKu payment gateway
- **Voucher & Promo**: Kombinasi diskon dengan aturan PPN 12%
- **Delivery management**: Driver earning 80% dari ongkir
- **Time simulation**: Untuk testing SLA/overdue orders

---

## 🛠️ Tech Stack

| Teknologi | Version | Purpose |
|-----------|---------|---------|
| PHP | 8.3+ | Runtime |
| Laravel | 13.x | Framework |
| Laravel Sanctum | 4.x | API Authentication |
| SQLite | - | Local database (dev) |
| MySQL | - | Production database |
| DuitKu | API | Payment Gateway |

---

## 🚀 Cara Setup & Instalasi

### Prerequisites

- PHP 8.3+
- Composer
- SQLite (untuk development) / MySQL (untuk production)
- Node.js 18+ (opsional, untuk frontend asset)

### Steps

```bash
# 1. Clone repository
git clone <repo-url>
cd seapedia_backend

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Buat database SQLite (development)
touch database/database.sqlite
# Atau untuk MySQL, buat database: CREATE DATABASE seapedia;

# 6. Update .env untuk MySQL jika perlu:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=seapedia
# DB_USERNAME=root
# DB_PASSWORD=

# 7. Run migrations
php artisan migrate

# 8. Seed database (roles, categories, demo accounts)
php artisan db:seed

# 9. Start development server
php artisan serve
```

### Setup untuk Railway (Production)

```bash
# 1. Set environment variables di Railway dashboard:
cp .env.railway .env
php artisan key:generate --show

# 2. Railway auto-migrate on deploy (jika configured)
```

---

## 📁 Environment Variables

### Core

```env
APP_NAME=SEAPEDIA
APP_ENV=local
APP_KEY=base64:xxxxx    # Generate via: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost

# Locale
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

### Database

```env
# SQLite (Development)
DB_CONNECTION=sqlite
# DB_HOST, DB_PORT, DB_DATABASE tidak diperlukan

# MySQL (Production)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seapedia
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Authentication (Sanctum)

```env
# Token expiration (null = never expire, atau minutes)
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000
SANCTUM_EXPIRATION=null
```

### Session

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
```

### Queue & Cache

```env
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### DuitKu Payment Gateway

```env
DUITKU_MERCHANT_CODE=your_merchant_code
DUITKU_API_KEY=your_api_key
DUITKU_BASE_URL=https://sandbox.duitku.com/webapi/api/merchant
DUITKU_CALLBACK_URL=https://your-domain.com/api/wallet/topup/callback
DUITKU_RETURN_URL=https://your-domain.com/wallet
```

### Bcrypt

```env
BCRYPT_ROUNDS=12
```

---

## 🔐 Demo Accounts

Seed dengan menjalankan `php artisan db:seed` (sudah termasuk di DatabaseSeeder).

| Role | Email | Password | Endpoint Login |
|------|-------|----------|----------------|
| **Admin** | admin+seapedia@email.com | seapedia123 | POST /api/auth/login |
| **Seller** | seller+seapedia@email.com | seapedia123 | POST /api/auth/login |
| **Buyer** | buyer+seapedia@email.com | seapedia123 | POST /api/auth/login |
| **Driver** | driver+seapedia@email.com | seapedia123 | POST /api/auth/login |

### Login Response

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin+seapedia@email.com",
      "roles": [{"id": 1, "name": "admin"}]
    },
    "roles": ["admin"],
    "active_role": "admin",
    "token": "1|abc123..."
  }
}
```

---

## 📜 Business Rules

### 1. Single-Store Checkout

Cart hanya bisa berisi produk dari **satu toko**. Jika buyer menambah produk dari toko berbeda, akan muncul error.

```php
// CartController.php - Line 68-72
if ($cart->store_id !== null && $cart->store_id !== $product->store_id && $cart->items()->exists()) {
    return response()->json([
        'success' => false,
        'message' => 'Cart Anda berisi produk dari toko lain. Selesaikan atau kosongkan cart terlebih dahulu.',
    ], 422);
}
```

**Flow Checkout:**
1. Buyer menambah produk ke cart (dari satu toko)
2. Checkout memvalidasi: alamat, cart items, voucher, wallet balance
3. Order dibuat dengan status `sedang_dikemas`
4. Stock produk dikurangi
5. Wallet di-debit
6. Cart di-clear

### 2. Kombinasi Voucher & Promo

```
┌─────────────────────────────────────────────────────────┐
│                    DISCOUNT FLOW                        │
├─────────────────────────────────────────────────────────┤
│  Subtotal (sebelum pajak)                              │
│       │                                                │
│       ├── Voucher (%) ──┐                              │
│       │                 │                              │
│       └── Promo (%) ────┼── Combined % Discount        │
│                         │                              │
│                         ▼                              │
│              Discount Amount (Subtotal × Total%)        │
│                         │                              │
│                         ▼                              │
│  + Delivery Fee (fixed)                                │
│  + PPN 12% (dari Subtotal - Discount)                  │
│  - Discount Amount                                      │
│       │                                                │
│       ▼                                                │
│  TOTAL = Subtotal + Delivery + PPN - Discount          │
└─────────────────────────────────────────────────────────┘
```

**Aturan:**
- **Voucher**: Opsional, kode spesifik, memiliki limit penggunaan
- **Promo**: Auto-applied berdasarkan subtotal, promo dengan value tertinggi digunakan
- **Total discount**: Voucher% + Promo% (tidak bisa melebihi 100%)

### 3. Perhitungan PPN 12%

**PPN dihitung dari (Subtotal - Discount Amount):**

| Item | Formula |
|------|---------|
| Subtotal | Σ(Product Price × Quantity) |
| Discount | Subtotal × (Voucher% + Promo%) |
| **PPN** | **(Subtotal - Discount Amount) × 12%** |
| Delivery Fee | Fixed (see below) |
| **Total** | Subtotal + Delivery + PPN - Discount |

**Contoh Perhitungan:**
```
- Subtotal: Rp 100.000
- Voucher 10%: Discount = Rp 10.000
- Subtotal setelah diskon: Rp 90.000
- PPN = 90.000 × 12% = Rp 10.800
- Delivery (Next Day): Rp 12.000
- Total = 100.000 + 12.000 + 10.800 - 10.000 = Rp 112.800
```

**Delivery Fees:**
| Method | Fee |
|--------|-----|
| Instant | Rp 20.000 |
| Next Day | Rp 12.000 |
| Regular | Rp 8.000 |

### 4. Driver Earning (80% dari Ongkir)

```php
// DeliveryController.php - Line 143-144
$earning = round($order->delivery_fee * 0.8);
```

| Delivery Method | Fee | Driver Earning (80%) | Platform (20%) |
|------------------|-----|---------------------|----------------|
| Instant | Rp 20.000 | Rp 16.000 | Rp 4.000 |
| Next Day | Rp 12.000 | Rp 9.600 | Rp 2.400 |
| Regular | Rp 8.000 | Rp 6.400 | Rp 1.600 |

### 5. Overdue SLA per Metode Pengiriman

| Method | SLA (due_at) | Due Calculation |
|--------|--------------|-----------------|
| Instant | 3 jam | `now()->addHours(3)` |
| Next Day | 24 jam | `now()->addDay()` |
| Regular | 72 jam (3 hari) | `now()->addDays(3)` |

**Status Overdue Flow:**
1. Admin simulate time advance: `POST /api/admin/time-simulation/advance`
2. System check deliveries dengan `due_at < simulated_now`
3. Orders dengan status `sedang_dikirim` atau `menunggu_pengirim` → `dikembalikan`
4. Buyer di-refund penuh ke wallet
5. Stock produk dikembalikan

**Simulasi Next Day (Testing Overdue):**

```bash
# 1. Lihat status simulasi
GET /api/admin/time-simulation

# 2. Advance waktu 25 jam (melewati SLA next_day)
POST /api/admin/time-simulation/advance
{
  "hours": 25
}

# 3. Check overdue orders
GET /api/admin/orders/overdue

# 4. Reset waktu (restore semua state)
POST /api/admin/time-simulation/reset
```

---

## 🔒 Security Notes

### SQL Injection Prevention

- ✅ Semua user input di-escape via Laravel Eloquent ORM
- ✅ Prepared statements digunakan di semua raw queries
- ✅ Query Builder dengan parameter binding

### XSS Prevention

- ✅ API responses menggunakan JSON (天然 resisten XSS)
- ✅ Frontend bertanggung jawab untuk sanitasi output
- ✅ Laravel Blade auto-escapes `{{ $var }}`

### Input Validation

- ✅ Semua request menggunakan Form Request classes
- ✅ Strong typing dengan type hints
- ✅ Enum validation untuk status fields

```php
// app/Http/Requests/Auth/LoginRequest.php
public function rules(): array
{
    return [
        'email' => ['required', 'email', 'max:255'],
        'password' => ['required', 'string', 'min:6'],
    ];
}
```

### Session & Token Security

```env
# Token expiration
SANCTUM_EXPIRATION=null  # Tokens tidak expire

# Session lifetime
SESSION_LIFETIME=120  # 2 hours
```

**Token Management:**
- Sanctum tokens dengan role-specific abilities
- User bisa switch role via `POST /api/auth/switch-role`
- Tokens bisa di-revoke via logout

### RBAC (Role-Based Access Control)

```php
// Middleware: app/Http/Middleware/EnsureRole.php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin only routes
});

Route::middleware(['auth:sanctum', 'role:seller'])->group(function () {
    // Seller only routes
});
```

**Role Permissions:**

| Endpoint Group | Admin | Seller | Buyer | Driver |
|---------------|-------|--------|-------|--------|
| Auth (login/logout) | ✅ | ✅ | ✅ | ✅ |
| Products (view) | ✅ | ✅ | ✅ | ✅ |
| Admin Dashboard | ✅ | ❌ | ❌ | ❌ |
| Seller Store/Products | ❌ | ✅ | ❌ | ❌ |
| Cart & Checkout | ❌ | ❌ | ✅ | ❌ |
| Driver Jobs | ❌ | ❌ | ❌ | ✅ |

---

## 📚 API Documentation

### Swagger/OpenAPI

Dokumentasi interaktif tersedia di:
```
GET /api/documentation
```

### Base URL

```
Development: http://localhost:8000/api
Production: https://your-domain.com/api
```

### Authentication

**Login:**
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin+seapedia@email.com",
  "password": "seapedia123"
}
```

**Authenticated Request:**
```
Authorization: {token}
Content-Type: application/json
```

### Key Endpoints

| Method | Endpoint | Auth | Role | Description |
|--------|----------|------|------|-------------|
| POST | /auth/register | ❌ | - | Register new user |
| POST | /auth/login | ❌ | - | Login |
| POST | /auth/logout | ✅ | All | Logout |
| POST | /auth/switch-role | ✅ | All | Switch active role |
| GET | /products | ❌ | - | List products |
| GET | /stores | ❌ | - | List stores |
| GET | /categories | ❌ | - | List categories |
| GET | /cart | ✅ | Buyer | View cart |
| POST | /cart/items | ✅ | Buyer | Add to cart |
| POST | /checkout | ✅ | Buyer | Checkout |
| GET | /orders | ✅ | Buyer | List orders |
| GET | /wallet | ✅ | All | View wallet |
| POST | /wallet/topup | ✅ | All | Topup via DuitKu |
| POST | /admin/vouchers | ✅ | Admin | Create voucher |
| POST | /admin/promos | ✅ | Admin | Create promo |
| POST | /admin/time-simulation/advance | ✅ | Admin | Advance time |
| GET | /driver/jobs | ✅ | Driver | Available jobs |
| POST | /driver/jobs/{id}/take | ✅ | Driver | Take delivery |
| POST | /driver/jobs/{id}/complete | ✅ | Driver | Complete delivery |

---

## 📂 Struktur Folder / Architecture

```
seapedia_backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php          # Login, Register, Logout, SwitchRole
│   │   │       ├── CartController.php           # Cart management
│   │   │       ├── CheckoutController.php       # Checkout & order creation
│   │   │       ├── ProductController.php        # Public product listing
│   │   │       ├── OrderController.php          # Buyer orders
│   │   │       ├── WalletController.php         # Wallet & DuitKu integration
│   │   │       ├── Admin/
│   │   │       │   ├── DashboardController.php  # Admin stats
│   │   │       │   ├── VoucherController.php    # Voucher CRUD
│   │   │       │   ├── PromoController.php      # Promo CRUD
│   │   │       │   ├── TimeSimulationController.php # Time simulation
│   │   │       │   └── OrderOverdueController.php
│   │   │       ├── Seller/
│   │   │       │   ├── StoreController.php      # Seller store management
│   │   │       │   ├── ProductController.php    # Seller product CRUD
│   │   │       │   ├── OrderController.php      # Seller orders
│   │   │       │   └── DashboardController.php
│   │   │       └── Driver/
│   │   │           ├── DeliveryController.php    # Driver job management
│   │   │           └── DashboardController.php
│   │   ├── Middleware/
│   │   │   └── EnsureRole.php                   # Role-based access
│   │   └── Requests/
│   │       ├── Auth/
│   │       ├── Cart/
│   │       ├── Checkout/
│   │       ├── Seller/
│   │       ├── Admin/
│   │       └── Wallet/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Product.php
│   │   ├── Store.php
│   │   ├── Cart.php / CartItem.php
│   │   ├── Order.php / OrderItem.php / OrderStatusHistory.php
│   │   ├── Voucher.php
│   │   ├── Promo.php
│   │   ├── Delivery.php
│   │   ├── Wallet.php / WalletTransaction.php
│   │   ├── Address.php
│   │   ├── Category.php
│   │   └── SystemSetting.php
│   └── Services/
│       ├── DuitkuService.php        # DuitKu payment integration
│       └── SystemTimeService.php    # Time simulation logic
├── config/
│   ├── app.php
│   ├── database.php
│   ├── sanctum.php
│   ├── l5-swagger.php
│   └── duitku.php
├── database/
│   ├── migrations/                  # Database schema
│   └── seeders/                    # Database seeding
│       ├── RoleSeeder.php
│       ├── CategorySeeder.php
│       ├── AdminUserSeeder.php
│       └── DemoAccountsSeeder.php
├── routes/
│   └── api.php                      # API routes
├── storage/
│   └── api-docs/                    # Generated Swagger docs
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── composer.json
└── README.md
```

### Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT (Frontend)                        │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼ HTTP/REST
┌─────────────────────────────────────────────────────────────────┐
│                      LARAVEL SANCTUM                            │
│                         Middleware                               │
│    ┌─────────────────────────────────────────────────────────┐  │
│    │  auth:sanctum  │  role:admin  │  role:seller  │ etc.   │  │
│    └─────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                       CONTROLLERS                                │
│  ┌──────────┐ ┌───────────┐ ┌──────────┐ ┌──────────────────┐  │
│  │   Auth   │ │  Checkout │ │  Seller  │ │      Driver      │  │
│  └──────────┘ └───────────┘ └──────────┘ └──────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                        SERVICES                                  │
│  ┌──────────────┐  ┌─────────────────┐  ┌───────────────────┐   │
│  │  DuitKu API  │  │ SystemTimeService│  │  WalletService   │   │
│  └──────────────┘  └─────────────────┘  └───────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    LARAVEL MODELS (Eloquent)                     │
│  ┌───────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌─────────────┐    │
│  │  User │ │ Order  │ │Product │ │ Wallet │ │  Delivery   │    │
│  └───────┘ └────────┘ └────────┘ └────────┘ └─────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                      DATABASE (MySQL/SQLite)                     │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📝 Changelog

### v1.0.0 (Latest)
- Multi-role authentication (Admin, Seller, Buyer, Driver)
- Single-store cart with checkout
- Voucher & Promo system with combination rules
- PPN 12% calculation from (subtotal - discount)
- Driver earning 80% from delivery fee
- DuitKu payment gateway integration
- Time simulation for SLA/overdue testing

---

## 📧 Contact

- **Email**: dev@seapedia.com
- **Issues**: https://github.com/seapedia/backend/issues

---

## 📄 License

MIT License - See [LICENSE](LICENSE) file for details.
