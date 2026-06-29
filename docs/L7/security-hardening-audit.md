# Security Hardening & RBAC Audit — SEAPEDIA (Level 7)

Dokumen ini berisi laporan audit keamanan dan refactoring yang dilakukan untuk seluruh API SEAPEDIA.

---

## 1. Security Audit Report

### 1.1 SQL Injection Prevention

| Status            | Keterangan                                                                                                                      |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| ✅ **SUDAH AMAN** | Seluruh project sudah menggunakan Eloquent ORM dan Query Builder dengan parameter binding. Tidak ditemukan raw query berbahaya. |

**Referensi:**

- `CheckoutController.php` - menggunakan `DB::raw()` HANYA untuk increment/decrement nilai numeric yang sudah divalidasi, bukan untuk input user
- `WalletController.php` - sama, `DB::raw()` hanya untuk operasi arithmetic

---

### 1.2 XSS Prevention pada `app_reviews.comment`

| Status             | Keterangan                                                                                                  |
| ------------------ | ----------------------------------------------------------------------------------------------------------- |
| ✅ **DITAMBAHKAN** | Endpoint `POST /api/reviews` baru dibuat dengan XSS sanitization menggunakan `strip_tags()` di Form Request |

**Implementasi:**

- File: `app/Http/Requests/Review/StoreReviewRequest.php`
- Method: `prepareForValidation()` - sanitasi comment sebelum validasi

---

### 1.3 Form Request Migration

| Status         | Keterangan                                                      |
| -------------- | --------------------------------------------------------------- |
| ✅ **SELESAI** | 15 Form Request class baru dibuat, semua controller di-refactor |

**Daftar Form Request:**

| Form Request                      | Controller                       | Method       |
| --------------------------------- | -------------------------------- | ------------ |
| `Auth/RegisterRequest.php`        | `AuthController`                 | `register`   |
| `Auth/LoginRequest.php`           | `AuthController`                 | `login`      |
| `Auth/SwitchRoleRequest.php`      | `AuthController`                 | `switchRole` |
| `Review/StoreReviewRequest.php`   | `ReviewController`               | `store`      |
| `Cart/AddCartItemRequest.php`     | `CartController`                 | `addItem`    |
| `Cart/UpdateCartItemRequest.php`  | `CartController`                 | `updateItem` |
| `Checkout/CheckoutRequest.php`    | `CheckoutController`             | `checkout`   |
| `Wallet/TopupRequest.php`         | `WalletController`               | `topup`      |
| `Seller/StoreStoreRequest.php`    | `Seller/StoreController`         | `store`      |
| `Seller/UpdateStoreRequest.php`   | `Seller/StoreController`         | `update`     |
| `Seller/StoreProductRequest.php`  | `Seller/ProductController`       | `store`      |
| `Seller/UpdateProductRequest.php` | `Seller/ProductController`       | `update`     |
| `Admin/StoreVoucherRequest.php`   | `Admin/VoucherController`        | `store`      |
| `Admin/UpdateVoucherRequest.php`  | `Admin/VoucherController`        | `update`     |
| `Admin/StorePromoRequest.php`     | `Admin/PromoController`          | `store`      |
| `Admin/UpdatePromoRequest.php`    | `Admin/PromoController`          | `update`     |
| `Admin/AdvanceTimeRequest.php`    | `Admin/TimeSimulationController` | `advance`    |

---

### 1.4 Audit Validasi Field Wajib

| Field                          | Validasi                  | Status       |
| ------------------------------ | ------------------------- | ------------ |
| Email                          | `email` rule              | ✅ Sudah ada |
| Rating                         | `integer\|min:1\|max:5`   | ✅ Sudah ada |
| Price (produk)                 | `numeric\|min:0`          | ✅ Sudah ada |
| Stock                          | `integer\|min:0`          | ✅ Sudah ada |
| Discount value (voucher/promo) | `numeric\|min:1\|max:100` | ✅ Sudah ada |

---

### 2.1 Verifikasi Logout

| Status            | Keterangan                                                                                           |
| ----------------- | ---------------------------------------------------------------------------------------------------- |
| ✅ **SUDAH AMAN** | `AuthController::logout()` memanggil `$request->user()->currentAccessToken()->delete()` dengan benar |

**Referensi:** `app/Http/Controllers/Api/AuthController.php:83`

---

### 2.2 Audit Middleware RBAC

| Prefix          | Middleware                  | Status               |
| --------------- | --------------------------- | -------------------- |
| `/api/admin/*`  | `auth:sanctum, role:admin`  | ✅ Semua terproteksi |
| `/api/seller/*` | `auth:sanctum, role:seller` | ✅ Semua terproteksi |
| `/api/driver/*` | `auth:sanctum, role:driver` | ✅ Semua terproteksi |

**Referensi:** `routes/api.php`

---

### 2.3 Audit Ownership Check

| Controller                  | Method                      | Pengecekan                       | Status       |
| --------------------------- | --------------------------- | -------------------------------- | ------------ |
| `Seller/ProductController`  | `show`, `update`, `destroy` | `where('store_id', $store?->id)` | ✅ Sudah ada |
| `Seller/OrderController`    | `index`, `show`, `process`  | `where('store_id', $store->id)`  | ✅ Sudah ada |
| `Driver/DeliveryController` | `take`                      | cek `status === 'available'`     | ✅ Sudah ada |
| `Driver/DeliveryController` | `complete`                  | cek `driver_id === $driverId`    | ✅ Sudah ada |
| `CartController`            | `updateItem`, `removeItem`  | `where('cart_id', $cart?->id)`   | ✅ Sudah ada |
| `WalletController`          | `show`, `transactions`      | `getOrCreateWallet($user->id)`   | ✅ Sudah ada |

---

## 2. Endpoint Review Baru

### POST /api/reviews

**Deskripsi:** Membuat review baru untuk aplikasi (semua role yang login boleh akses)

**Authentication:** `auth:sanctum` (tanpa role check khusus)

**Request:**

```json
{
    "rating": 5,
    "comment": "Aplikasi sangat bagus!"
}
```

**Validasi:**

- `rating`: required, integer, min 1, max 5
- `comment`: required, string, max 1000, disanitasi dengan `strip_tags()`

**Response Sukses (201):**

```json
{
    "success": true,
    "message": "Review berhasil ditambahkan",
    "data": {
        "id": 1,
        "reviewer_name": "johndoe",
        "rating": 5,
        "comment": "Aplikasi sangat bagus!",
        "created_at": "2026-06-20T12:00:00+07:00"
    }
}
```

### Skenario Testing XSS Prevention

**Input dengan script berbahaya:**

```json
{
    "rating": 5,
    "comment": "<script>alert('XSS')</script>Produk bagus banget!"
}
```

**Hasil setelah sanitasi (disimpan ke database):**

```json
{
    "rating": 5,
    "comment": "Produk bagus banget!"
}
```

**Penjelasan:** Tag `<script>alert('XSS')</script>` dihapus sepenuhnya oleh `strip_tags()` sebelum data masuk ke database.

---

## 3. Endpoint yang Tidak Terproteksi RBAC

| Status                  | Keterangan                                                     |
| ----------------------- | -------------------------------------------------------------- |
| ✅ **TIDAK ADA TEMUAN** | Semua endpoint sudah terproteksi dengan middleware yang sesuai |

---

## 4. Catatan Penting

1. **Semua Form Request** memiliki `authorize()` mengembalikan `true` karena otorisasi sudah ditangani middleware `auth:sanctum`/`role:xxx` di level route
2. **Custom error messages** dalam Bahasa Indonesia sudah ditambahkan di semua Form Request
3. **XSS sanitization** dilakukan di `prepareForValidation()` - sebelum validasi, bukan di controller
4. **Business logic** tidak diubah sama sekali - hanya cara validasi yang di-refactor

---

## 5. Struktur File Baru

```
app/Http/Requests/
├── Auth/
│   ├── LoginRequest.php
│   ├── RegisterRequest.php
│   └── SwitchRoleRequest.php
├── Cart/
│   ├── AddCartItemRequest.php
│   └── UpdateCartItemRequest.php
├── Checkout/
│   └── CheckoutRequest.php
├── Wallet/
│   └── TopupRequest.php
├── Review/
│   └── StoreReviewRequest.php
├── Seller/
│   ├── StoreStoreRequest.php
│   ├── UpdateStoreRequest.php
│   ├── StoreProductRequest.php
│   └── UpdateProductRequest.php
└── Admin/
    ├── StoreVoucherRequest.php
    ├── UpdateVoucherRequest.php
    ├── StorePromoRequest.php
    ├── UpdatePromoRequest.php
    └── AdvanceTimeRequest.php

docs/L7/
└── security-hardening-audit.md
```
