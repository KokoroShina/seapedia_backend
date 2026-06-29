# Seapedia API Reference

## Table of Contents

1. [Introduction](#1-introduction)
2. [Authentication](#2-authentication)
3. [Forgot Password (OTP)](#3-forgot-password-otp)
4. [Store & Product](#4-store--product)
5. [Cart](#5-cart)
6. [Checkout](#6-checkout)
7. [Wallet & DuitKu](#7-wallet--duitku)
8. [Review](#8-review)
9. [Seller Module](#9-seller-module)
10. [Driver Module](#10-driver-module)
11. [Admin Module](#11-admin-module)
12. [Business Logic](#12-business-logic)
13. [Error Handling](#13-error-handling)
14. [Form Request Migration (L7)](#14-form-request-migration-l7)

---

## 1. Introduction

### Base URL

```
http://localhost:3000/api
```

### Authentication

Semua endpoint yang memerlukan authentication menggunakan **Laravel Sanctum** dengan Bearer Token.

**Headers yang diperlukan:**

| Header        | Value              | Keterangan                |
| ------------- | ------------------ | ------------------------- |
| Authorization | `Bearer {token}`   | Token dari login/register |
| Content-Type  | `application/json` | Untuk request body        |
| Accept        | `application/json` | Untuk response JSON       |

### Response Structure

**Success Response:**

```json
{
    "success": true,
    "message": "Success message",
    "data": { ... }
}
```

**Error Response (422 Validation):**

```json
{
    "message": "Validation failed",
    "errors": {
        "field": ["Error message"]
    }
}
```

**Error Response (401/403/404):**

```json
{
    "success": false,
    "message": "Error message"
}
```

### HTTP Status Codes

| Code | Description                  |
| ---- | ---------------------------- |
| 200  | Success                      |
| 201  | Created                      |
| 400  | Bad Request                  |
| 401  | Unauthorized (not logged in) |
| 403  | Forbidden (wrong role)       |
| 404  | Not Found                    |
| 422  | Validation Error             |
| 500  | Server Error                 |

### Pagination

List endpoints menggunakan pagination dengan format:

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [...],
        "first_page_url": "...",
        "from": 1,
        "last_page": 5,
        "last_page_url": "...",
        "next_page_url": "...",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

---

## 2. Authentication

### 2.1 Register

**Endpoint:** `POST /api/auth/register`

Registrasi user baru dengan role.

#### Request

**Headers:**

| Header       | Value            |
| ------------ | ---------------- |
| Content-Type | application/json |
| Accept       | application/json |

**Body:**

```json
{
    "username": "johndoe",
    "email": "john@example.com",
    "password": "Password123",
    "password_confirmation": "Password123",
    "role": "buyer"
}
```

#### Validation Rules

| Field                 | Type   | Required | Rules                                         |
| --------------------- | ------ | -------- | --------------------------------------------- |
| username              | string | ✅       | max 100 chars, unique                         |
| email                 | string | ✅       | valid email, max 150 chars, unique            |
| password              | string | ✅       | min 8 chars, must match password_confirmation |
| password_confirmation | string | ✅       | must match password                           |
| role                  | string | ✅       | in: buyer, seller, driver                     |

#### Response

**Success (201 Created):**

```json
{
    "success": true,
    "message": "Registrasi berhasil",
    "data": {
        "user": {
            "id": 1,
            "username": "johndoe",
            "email": "john@example.com",
            "created_at": "2026-06-25T09:00:00+07:00",
            "updated_at": "2026-06-25T09:00:00+07:00"
        },
        "active_role": "buyer",
        "token": "1|abc123..."
    }
}
```

#### Contoh JavaScript

```javascript
const response = await fetch('/api/auth/register', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    body: JSON.stringify({
        username: 'johndoe',
        email: 'john@example.com',
        password: 'Password123',
        password_confirmation: 'Password123',
        role: 'buyer',
    }),
});
const data = await response.json();
```

---

### 2.2 Login

**Endpoint:** `POST /api/auth/login`

Login user dan mendapatkan token.

#### Request

**Headers:**

| Header       | Value            |
| ------------ | ---------------- |
| Content-Type | application/json |
| Accept       | application/json |

**Body:**

```json
{
    "email": "john@example.com",
    "password": "Password123"
}
```

#### Validation Rules

| Field    | Type   | Required | Rules       |
| -------- | ------ | -------- | ----------- |
| email    | string | ✅       | valid email |
| password | string | ✅       | required    |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "user": {
            "id": 1,
            "username": "johndoe",
            "email": "john@example.com",
            "roles": [{ "id": 1, "name": "buyer" }]
        },
        "roles": ["buyer"],
        "active_role": "buyer",
        "token": "2|xyz789..."
    }
}
```

**Error (401 Unauthorized):**

```json
{
    "success": false,
    "message": "Email atau password salah"
}
```

---

### 2.3 Logout

**Endpoint:** `POST /api/auth/logout`

Logout dan invalidate token saat ini.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Content-Type  | application/json |
| Accept        | application/json |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Logout berhasil"
}
```

---

### 2.4 Switch Role

**Endpoint:** `POST /api/auth/switch-role`

Ganti active role (untuk user yang punya multiple roles).

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Content-Type  | application/json |
| Accept        | application/json |

**Body:**

```json
{
    "role": "seller"
}
```

#### Validation Rules

| Field | Type   | Required | Rules                                                       |
| ----- | ------ | -------- | ----------------------------------------------------------- |
| role  | string | ✅       | in: buyer, seller, driver, admin (roles yang dimiliki user) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Role berhasil diganti",
    "data": {
        "active_role": "seller",
        "token": "3|newToken..."
    }
}
```

**Error (403 Forbidden):**

```json
{
    "success": false,
    "message": "Role tidak ditemukan"
}
```

---

## 3. Forgot Password (OTP)

### 3.1 Send OTP

**Endpoint:** `POST /api/auth/forgot-password/send-otp`

Kirim kode OTP ke email user.

#### Request

**Headers:**

| Header       | Value            |
| ------------ | ---------------- |
| Content-Type | application/json |
| Accept       | application/json |

**Body:**

```json
{
    "email": "user@example.com"
}
```

#### Validation Rules

| Field | Type   | Required | Rules                                  |
| ----- | ------ | -------- | -------------------------------------- |
| email | string | ✅       | valid email format, max 255 characters |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Kode OTP telah dikirim ke email Anda.",
    "data": {
        "expires_at": "2026-06-25T09:15:00+07:00",
        "expires_in_minutes": 15
    }
}
```

**Email Tidak Terdaftar (200 OK):**
_Returns success untuk prevent email enumeration_

```json
{
    "success": true,
    "message": "Jika email tersebut terdaftar, kode OTP telah dikirim."
}
```

---

### 3.2 Verify OTP

**Endpoint:** `POST /api/auth/forgot-password/verify-otp`

Verifikasi kode OTP.

#### Request

**Headers:**

| Header       | Value            |
| ------------ | ---------------- |
| Content-Type | application/json |
| Accept       | application/json |

**Body:**

```json
{
    "email": "user@example.com",
    "otp": "847291"
}
```

#### Validation Rules

| Field | Type   | Required | Rules                          |
| ----- | ------ | -------- | ------------------------------ |
| email | string | ✅       | valid email format             |
| otp   | string | ✅       | exactly 6 digits, numbers only |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Kode OTP terverifikasi. Silakan reset password Anda."
}
```

**OTP Invalid (400 Bad Request):**

```json
{
    "success": false,
    "message": "Kode OTP tidak valid atau sudah expired."
}
```

---

### 3.3 Reset Password

**Endpoint:** `POST /api/auth/forgot-password/reset-password`

Reset password dengan OTP yang sudah diverifikasi.

#### Request

**Headers:**

| Header       | Value            |
| ------------ | ---------------- |
| Content-Type | application/json |
| Accept       | application/json |

**Body:**

```json
{
    "email": "user@example.com",
    "otp": "847291",
    "password": "NewPassword123",
    "password_confirmation": "NewPassword123"
}
```

#### Validation Rules

| Field                 | Type   | Required | Rules                          |
| --------------------- | ------ | -------- | ------------------------------ |
| email                 | string | ✅       | valid email format             |
| otp                   | string | ✅       | exactly 6 digits, numbers only |
| password              | string | ✅       | min 8 chars, confirmed         |
| password_confirmation | string | ✅       | must match password            |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Password berhasil direset. Silakan login dengan password baru Anda."
}
```

---

## 4. Store & Product

### 4.1 List Stores

**Endpoint:** `GET /api/stores`

Ambil daftar semua toko (public).

#### Request

**Headers:**

| Header | Value            |
| ------ | ---------------- |
| Accept | application/json |

**Query Parameters (optional):**

| Parameter | Type | Description                  |
| --------- | ---- | ---------------------------- |
| page      | int  | Page number                  |
| per_page  | int  | Items per page (default: 15) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Daftar toko berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "Toko Sejahtera",
                "description": "Toko terpercaya",
                "image": "/storage/stores/logo.png"
            }
        ],
        "per_page": 15,
        "total": 10
    }
}
```

---

### 4.2 Get Store Detail

**Endpoint:** `GET /api/stores/{id}`

Ambil detail toko beserta produknya (public).

#### Request

**Headers:**

| Header | Value            |
| ------ | ---------------- |
| Accept | application/json |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Detail toko berhasil diambil",
    "data": {
        "id": 1,
        "user_id": 1,
        "name": "Toko Sejahtera",
        "description": "Toko terpercaya",
        "image": "/storage/stores/logo.png",
        "created_at": "2026-06-25T09:00:00+07:00",
        "updated_at": "2026-06-25T09:00:00+07:00",
        "products": [
            {
                "id": 1,
                "store_id": 1,
                "name": "Produk A",
                "description": "Deskripsi produk",
                "price": "15000.00",
                "stock": 100,
                "image": "/storage/products/img1.png"
            }
        ]
    }
}
```

**Not Found (404):**

```json
{
    "success": false,
    "message": "Toko tidak ditemukan"
}
```

---

### 4.3 List Products

**Endpoint:** `GET /api/products`

Ambil daftar semua produk (public).

#### Request

**Headers:**

| Header | Value            |
| ------ | ---------------- |
| Accept | application/json |

**Query Parameters (optional):**

| Parameter | Type   | Description                  |
| --------- | ------ | ---------------------------- |
| store_id  | int    | Filter by store              |
| search    | string | Search by product name       |
| page      | int    | Page number                  |
| per_page  | int    | Items per page (default: 15) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Daftar produk berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "store_id": 1,
                "name": "Produk A",
                "description": "Deskripsi produk",
                "price": "15000.00",
                "stock": 100,
                "image": "/storage/products/img1.png"
            }
        ],
        "per_page": 15,
        "total": 50
    }
}
```

---

### 4.4 Get Product Detail

**Endpoint:** `GET /api/products/{id}`

Ambil detail produk (public).

#### Request

**Headers:**

| Header | Value            |
| ------ | ---------------- |
| Accept | application/json |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Detail produk berhasil diambil",
    "data": {
        "id": 1,
        "store_id": 1,
        "name": "Produk A",
        "description": "Deskripsi produk",
        "price": "15000.00",
        "stock": 100,
        "image": "/storage/products/img1.png",
        "store": {
            "id": 1,
            "name": "Toko Sejahtera"
        }
    }
}
```

---

## 4.5 List Categories (Public)

**Endpoint:** `GET /api/categories`

Ambil daftar semua kategori aktif (untuk dropdown).

#### Request

**Headers:**

| Header | Value            |
| ------ | ---------------- |
| Accept | application/json |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Makanan",
            "slug": "makanan",
            "icon": "🍔"
        },
        {
            "id": 2,
            "name": "Minuman",
            "slug": "minuman",
            "icon": "🥤"
        }
    ]
}
```

---

## 5. Cart

### 5.1 Get Cart

**Endpoint:** `GET /api/cart`

Ambil isi cart user yang login.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Accept        | application/json |

#### Response

**Success (200 OK) - Cart dengan items:**

```json
{
    "success": true,
    "message": "Cart berhasil diambil",
    "data": {
        "store": {
            "id": 1,
            "name": "Toko Sejahtera"
        },
        "items": [
            {
                "id": 1,
                "product_id": 1,
                "name": "Produk A",
                "price": "15000.00",
                "quantity": 2,
                "subtotal": 30000
            }
        ],
        "total": 30000
    }
}
```

**Success (200 OK) - Cart kosong:**

```json
{
    "success": true,
    "message": "Cart kosong",
    "data": null
}
```

---

### 5.2 Add Item to Cart

**Endpoint:** `POST /api/cart/items`

Tambah produk ke cart.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Content-Type  | application/json |
| Accept        | application/json |

**Body:**

```json
{
    "product_id": 1,
    "quantity": 2
}
```

#### Validation Rules

| Field      | Type | Required | Rules                    |
| ---------- | ---- | -------- | ------------------------ |
| product_id | int  | ✅       | exists in products table |
| quantity   | int  | ✅       | min 1                    |

#### Response

**Success (201 Created):**

```json
{
    "success": true,
    "message": "Produk berhasil ditambahkan ke cart"
}
```

**Error - Store mismatch (422):**

```json
{
    "success": false,
    "message": "Cart Anda berisi produk dari toko lain. Selesaikan atau kosongkan cart terlebih dahulu."
}
```

---

### 5.3 Update Cart Item

**Endpoint:** `PUT /api/cart/items/{itemId}`

Update quantity item di cart.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Content-Type  | application/json |
| Accept        | application/json |

**Body:**

```json
{
    "quantity": 5
}
```

#### Validation Rules

| Field    | Type | Required | Rules |
| -------- | ---- | -------- | ----- |
| quantity | int  | ✅       | min 1 |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Quantity berhasil diperbarui",
    "data": {
        "id": 1,
        "product_id": 1,
        "quantity": 5
    }
}
```

---

### 5.4 Remove Cart Item

**Endpoint:** `DELETE /api/cart/items/{itemId}`

Hapus item dari cart.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Accept        | application/json |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Item berhasil dihapus dari cart"
}
```

---

## 6. Checkout

### 6.1 Checkout

**Endpoint:** `POST /api/checkout`

Proses checkout dari cart.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Content-Type  | application/json |
| Accept        | application/json |

**Body:**

```json
{
    "address_id": 1,
    "delivery_method": "instant",
    "voucher_code": "DISKON10"
}
```

#### Validation Rules

| Field           | Type   | Required | Rules                                      |
| --------------- | ------ | -------- | ------------------------------------------ |
| address_id      | int    | ✅       | exists in addresses table, belongs to user |
| delivery_method | string | ✅       | in: instant, next_day, regular             |
| voucher_code    | string | nullable | max 50 chars                               |

#### Response

**Success (201 Created):**

```json
{
    "success": true,
    "message": "Checkout berhasil",
    "data": {
        "id": 1,
        "buyer_id": 1,
        "store_id": 1,
        "address_id": 1,
        "voucher_id": 1,
        "promo_id": 2,
        "delivery_method": "instant",
        "subtotal": "50000.00",
        "discount_amount": 15000,
        "delivery_fee": 20000,
        "ppn": 6000,
        "total": "61000.00",
        "status": "sedang_dikemas",
        "created_at": "2026-06-25T09:00:00+07:00",
        "items": [...],
        "status_histories": [...],
        "discount_details": {
            "discount_amount": 15000,
            "voucher_id": 1,
            "promo_id": 2
        }
    }
}
```

**Error - Cart kosong (400):**

```json
{
    "success": false,
    "message": "Cart kosong"
}
```

**Error - Stok tidak mencukupi (422):**

```json
{
    "success": false,
    "message": "Stok Produk A tidak mencukupi. Sisa stok: 5"
}
```

**Error - Saldo tidak cukup (422):**

```json
{
    "success": false,
    "message": "Saldo wallet tidak mencukupi"
}
```

---

## 7. Wallet

### 7.1 Get Wallet Balance

**Endpoint:** `GET /api/wallet`

Ambil saldo wallet user.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Accept        | application/json |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Saldo wallet berhasil diambil",
    "data": {
        "id": 1,
        "user_id": 1,
        "balance": "150000.00",
        "updated_at": "2026-06-25T09:00:00+07:00"
    }
}
```

---

### 7.2 Get Wallet Transactions

**Endpoint:** `GET /api/wallet/transactions`

Ambil riwayat transaksi wallet.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Accept        | application/json |

**Query Parameters (optional):**

| Parameter | Type | Description                  |
| --------- | ---- | ---------------------------- |
| page      | int  | Page number                  |
| per_page  | int  | Items per page (default: 15) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Riwayat transaksi berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "wallet_id": 1,
                "order_id": 1,
                "type": "payment",
                "amount": "-50000.00",
                "status": "success",
                "description": "Pembayaran order #1",
                "created_at": "2026-06-25T09:00:00+07:00"
            },
            {
                "id": 2,
                "wallet_id": 1,
                "type": "topup",
                "amount": "200000.00",
                "status": "success",
                "payment_method": "BCAVA",
                "description": "Top-up via DuitKu - Berhasil",
                "created_at": "2026-06-25T08:00:00+07:00"
            }
        ],
        "per_page": 15,
        "total": 2
    }
}
```

---

### 7.3 Topup Wallet

**Endpoint:** `POST /api/wallet/topup`

Topup wallet via DuitKu payment gateway.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Content-Type  | application/json |
| Accept        | application/json |

**Body:**

```json
{
    "amount": 100000,
    "payment_method": "BCAVA"
}
```

#### Validation Rules

| Field          | Type    | Required | Rules                                                                 |
| -------------- | ------- | -------- | --------------------------------------------------------------------- |
| amount         | numeric | ✅       | min 10000                                                             |
| payment_method | string  | ✅       | (tersedia: BCAVA, BNIVA, BRIVA, MANDIRI, GOPAY, SHOPEEPAY, OVO, DANA) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Transaksi top-up berhasil dibuat",
    "data": {
        "merchantOrderId": "WD-1-1624567890",
        "reference": "REF123456",
        "amount": 100000,
        "status": "pending",
        "paymentUrl": "https://app.midtrans.com/...",
        "vaNumber": "1234567890"
    }
}
```

**Error - Gagal koneksi DuitKu (500):**

```json
{
    "success": false,
    "message": "Gagal menghubungi server DuitKu"
}
```

---

### 7.3.1 Get Payment Methods (Public)

**Endpoint:** `GET /api/payment-methods`

Ambil daftar metode pembayaran dari DuitKu. Tidak membutuhkan autentikasi.

#### Request

**Headers:**

| Header | Value            |
| ------ | ---------------- |
| Accept | application/json |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Payment methods berhasil diambil",
    "data": [
        {
            "code": "VC",
            "name": "BCA Virtual Account",
            "image": "https://images.duitku.com/hotlink-ok/BCA.PNG",
            "fee": 0
        },
        {
            "code": "QJ",
            "name": "QRIS",
            "image": "https://images.duitku.com/hotlink-ok/QRIS.PNG",
            "fee": 0
        }
    ]
}
```

**Kode Payment Method Populer:**

| Kode | Metode Pembayaran |
|------|-------------------|
| VC | BCA Virtual Account |
| BNIVA | BNI Virtual Account |
| BRIVA | BRI Virtual Account |
| MANDIRI | Mandiri Bill Payment |
| QJ | QRIS |
| DA | DANA |
| SP | ShopeePay |
| OV | OVO |

---

### 7.3.2 Check DuitKu Status (Public)

**Endpoint:** `GET /api/wallet/check-status/{merchantOrderId}`

Cek status transaksi langsung dari DuitKu. Tidak membutuhkan autentikasi.

#### Request

**Headers:**

| Header | Value            |
| ------ | ---------------- |
| Accept | application/json |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Status transaksi berhasil diambil",
    "data": {
        "merchantOrderId": "WD-1-1624567890",
        "reference": "REF123456",
        "amount": 100000,
        "fee": 0,
        "statusCode": "00",
        "statusMessage": "SUCCESS"
    }
}
```

**Status Codes:**

| Code | Description |
|------|-------------|
| 00 | SUCCESS - Pembayaran berhasil |
| 01 | PENDING - Menunggu pembayaran |
| 02 | FAILED - Pembayaran gagal |
| 03 | EXPIRED - Pembayaran kadaluarsa |

---

### 7.4 Check Topup Status

**Endpoint:** `GET /api/wallet/topup/status/{merchantOrderId}`

Cek status transaksi topup.

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Accept        | application/json |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Status transaksi berhasil diambil",
    "data": {
        "merchantOrderId": "WD-1-1624567890",
        "amount": 100000,
        "status": "success",
        "payment_method": "BCAVA",
        "created_at": "2026-06-25T08:00:00+07:00",
        "updated_at": "2026-06-25T08:05:00+07:00"
    }
}
```

**Not Found (404):**

```json
{
    "success": false,
    "message": "Transaksi tidak ditemukan"
}
```

---

### 7.5 DuitKu Callback

**Endpoint:** `POST /api/wallet/topup/callback`

Callback dari DuitKu untuk update status topup (server-to-server).

#### Request

**Headers:**

| Header       | Value            |
| ------------ | ---------------- |
| Content-Type | application/json |

**Body:**

```json
{
    "merchantCode": "D0001",
    "amount": "100000",
    "merchantOrderId": "WD-1-1624567890",
    "signature": "abc123...",
    "resultCode": "00"
}
```

#### Response

```
OK
```

---

## 8. Review

### 8.1 List Reviews

**Endpoint:** `GET /api/reviews`

Ambil daftar review (public).

#### Request

**Headers:**

| Header | Value            |
| ------ | ---------------- |
| Accept | application/json |

**Query Parameters (optional):**

| Parameter | Type | Description                  |
| --------- | ---- | ---------------------------- |
| page      | int  | Page number                  |
| per_page  | int  | Items per page (default: 15) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Daftar review berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "reviewer_name": "John Doe",
                "rating": 5,
                "comment": "Produk sangat bagus!",
                "created_at": "2026-06-25T09:00:00+07:00"
            }
        ],
        "per_page": 15,
        "total": 10
    }
}
```

---

### 8.2 Create Review

**Endpoint:** `POST /api/reviews`

Buat review baru (authenticated users).

#### Request

**Headers:**

| Header        | Value            |
| ------------- | ---------------- |
| Authorization | Bearer {token}   |
| Content-Type  | application/json |
| Accept        | application/json |

**Body:**

```json
{
    "rating": 5,
    "comment": "Produk sangat bagus!"
}
```

#### Validation Rules

| Field   | Type   | Required | Rules    |
| ------- | ------ | -------- | -------- |
| rating  | int    | ✅       | 1-5      |
| comment | string | ✅       | required |

#### Response

**Success (201 Created):**

```json
{
    "success": true,
    "message": "Review berhasil ditambahkan",
    "data": {
        "reviewer_name": "johndoe",
        "rating": 5,
        "comment": "Produk sangat bagus!",
        "created_at": "2026-06-25T09:00:00+07:00"
    }
}
```

---

## 9. Seller Module

### 9.1 Get Seller's Store

**Endpoint:** `GET /api/seller/store`

Ambil detail toko milik seller yang login.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Accept        | application/json              |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Detail toko berhasil diambil",
    "data": {
        "id": 1,
        "user_id": 2,
        "name": "Toko Sejahtera",
        "description": "Toko terpercaya",
        "image": "/storage/stores/logo.png",
        "created_at": "2026-06-25T09:00:00+07:00",
        "updated_at": "2026-06-25T09:00:00+07:00"
    }
}
```

**Not Found (404):**

```json
{
    "success": false,
    "message": "Anda belum memiliki toko"
}
```

---

### 9.2 Create Store

**Endpoint:** `POST /api/seller/store`

Buat toko baru.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Content-Type  | multipart/form-data           |
| Accept        | application/json              |

**Body (multipart/form-data):**

| Field       | Type   | Required | Description    |
| ----------- | ------ | -------- | -------------- |
| name        | string | ✅       | max 150 chars  |
| description | string | nullable |                |
| image       | file   | nullable | image, max 2MB |

#### Response

**Success (201 Created):**

```json
{
    "success": true,
    "message": "Toko berhasil dibuat",
    "data": {
        "id": 1,
        "user_id": 2,
        "name": "Toko Sejahtera",
        "description": "Toko terpercaya",
        "image": "/storage/stores/logo.png",
        "created_at": "2026-06-25T09:00:00+07:00",
        "updated_at": "2026-06-25T09:00:00+07:00"
    }
}
```

**Error - Sudah punya toko (422):**

```json
{
    "success": false,
    "message": "Anda sudah memiliki toko"
}
```

---

### 9.3 Update Store

**Endpoint:** `PUT /api/seller/store`

Update data toko.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Content-Type  | multipart/form-data           |
| Accept        | application/json              |

**Body (multipart/form-data):**

| Field       | Type   | Required | Description    |
| ----------- | ------ | -------- | -------------- |
| name        | string | ✅       | max 150 chars  |
| description | string | nullable |                |
| image       | file   | nullable | image, max 2MB |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Toko berhasil diperbarui",
    "data": { ... }
}
```

---

### 9.4 List Seller's Products

**Endpoint:** `GET /api/seller/products`

Ambil daftar produk milik seller.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Accept        | application/json              |

**Query Parameters (optional):**

| Parameter | Type | Description                  |
| --------- | ---- | ---------------------------- |
| page      | int  | Page number                  |
| per_page  | int  | Items per page (default: 15) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Daftar produk berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "store_id": 1,
                "name": "Produk A",
                "description": "Deskripsi produk",
                "price": "15000.00",
                "stock": 100,
                "image": "/storage/products/img1.png",
                "created_at": "2026-06-25T09:00:00+07:00",
                "updated_at": "2026-06-25T09:00:00+07:00"
            }
        ],
        "per_page": 15,
        "total": 10
    }
}
```

---

### 9.5 Create Product

**Endpoint:** `POST /api/seller/products`

Tambah produk baru.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Content-Type  | multipart/form-data           |
| Accept        | application/json              |

**Body (multipart/form-data):**

| Field       | Type    | Required | Description    |
| ----------- | ------- | -------- | -------------- |
| category_id | int     | ✅       | exists in categories |
| name        | string  | ✅       | max 150 chars  |
| description | string  | nullable |                |
| price       | numeric | ✅       | min 0          |
| stock       | int     | ✅       | min 0          |
| image       | file    | nullable | image, max 2MB |

#### Response

**Success (201 Created):**

```json
{
    "success": true,
    "message": "Produk berhasil ditambahkan",
    "data": {
        "id": 1,
        "store_id": 1,
        "name": "Produk A",
        "description": "Deskripsi produk",
        "price": "15000.00",
        "stock": 100,
        "image": "/storage/products/img1.png",
        "created_at": "2026-06-25T09:00:00+07:00",
        "updated_at": "2026-06-25T09:00:00+07:00"
    }
}
```

---

### 9.6 Update Product

**Endpoint:** `PUT /api/seller/products/{id}`

Update produk.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Content-Type  | multipart/form-data           |
| Accept        | application/json              |

**Body (multipart/form-data):**

| Field       | Type    | Required | Description    |
| ----------- | ------- | -------- | -------------- |
| name        | string  | ✅       | max 150 chars  |
| description | string  | nullable |                |
| price       | numeric | ✅       | min 0          |
| stock       | int     | ✅       | min 0          |
| image       | file    | nullable | image, max 2MB |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Produk berhasil diperbarui",
    "data": { ... }
}
```

---

### 9.7 Delete Product

**Endpoint:** `DELETE /api/seller/products/{id}`

Hapus produk.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Accept        | application/json              |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Produk berhasil dihapus"
}
```

---

### 9.8 List Seller Orders

**Endpoint:** `GET /api/seller/orders`

Ambil daftar order masuk ke toko seller.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Accept        | application/json              |

**Query Parameters (optional):**

| Parameter | Type   | Description                  |
| --------- | ------ | ---------------------------- |
| status    | string | Filter by status             |
| page      | int    | Page number                  |
| per_page  | int    | Items per page (default: 15) |

**Status values:** `sedang_dikemas`, `menunggu_pengirim`, `sedang_dikirim`, `pesanan_selesai`, `dikembalikan`

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Daftar order berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "buyer_id": 1,
                "store_id": 1,
                "delivery_method": "instant",
                "subtotal": "50000.00",
                "discount_amount": "5000.00",
                "delivery_fee": "20000.00",
                "ppn": "6000.00",
                "total": "71000.00",
                "status": "sedang_dikemas",
                "created_at": "2026-06-25T09:00:00+07:00",
                "buyer": {
                    "id": 1,
                    "name": "John Doe",
                    "username": "johndoe"
                },
                "items_count": 3
            }
        ],
        "per_page": 15,
        "total": 5
    }
}
```

---

### 9.9 Get Order Detail

**Endpoint:** `GET /api/seller/orders/{id}`

Ambil detail order.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Accept        | application/json              |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Detail order berhasil diambil",
    "data": {
        "id": 1,
        "buyer_id": 1,
        "store_id": 1,
        "address_id": 1,
        "delivery_method": "instant",
        "subtotal": "50000.00",
        "discount_amount": "5000.00",
        "delivery_fee": "20000.00",
        "ppn": "6000.00",
        "total": "71000.00",
        "status": "sedang_dikemas",
        "created_at": "2026-06-25T09:00:00+07:00",
        "buyer": {
            "id": 1,
            "name": "John Doe",
            "username": "johndoe",
            "email": "john@example.com"
        },
        "items": [...],
        "address": {...},
        "status_histories": [
            {
                "id": 1,
                "status": "sedang_dikemas",
                "note": "Pesanan berhasil dibuat",
                "created_at": "2026-06-25T09:00:00+07:00"
            }
        ]
    }
}
```

---

### 9.10 Process Order

**Endpoint:** `PUT /api/seller/orders/{id}/process`

Proses order (kirim ke driver) - ubah status dari `sedang_dikemas` ke `menunggu_pengirim`.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: seller) |
| Accept        | application/json              |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Order berhasil diproses dan menunggu pengirim",
    "data": {
        "id": 1,
        "status": "menunggu_pengirim",
        "delivery": {
            "id": 1,
            "order_id": 1,
            "status": "available",
            "due_at": "2026-06-25T12:00:00+07:00"
        },
        "status_histories": [...]
    }
}
```

**Error - Status tidak valid (422):**

```json
{
    "success": false,
    "message": "Order ini sudah tidak bisa diproses ulang oleh penjual"
}
```

---

## 10. Driver Module

### 10.1 List Available Jobs

**Endpoint:** `GET /api/driver/jobs`

Ambil daftar job delivery yang tersedia.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: driver) |
| Accept        | application/json              |

**Query Parameters (optional):**

| Parameter | Type | Description                  |
| --------- | ---- | ---------------------------- |
| page      | int  | Page number                  |
| per_page  | int  | Items per page (default: 15) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Daftar job berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "order_id": 1,
                "status": "available",
                "due_at": "2026-06-25T12:00:00+07:00",
                "order": {
                    "id": 1,
                    "buyer_id": 1,
                    "store_id": 1,
                    "delivery_method": "instant",
                    "total": "71000.00",
                    "status": "menunggu_pengirim"
                },
                "address": {
                    "id": 1,
                    "address": "Jl. Sudirman No. 123",
                    "recipient_name": "John Doe",
                    "phone": "081234567890"
                }
            }
        ],
        "per_page": 15,
        "total": 5
    }
}
```

---

### 10.2 Get Active Delivery

**Endpoint:** `GET /api/driver/jobs/active`

Ambil delivery aktif driver saat ini.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: driver) |
| Accept        | application/json              |

#### Response

**Success (200 OK) - Ada delivery aktif:**

```json
{
    "success": true,
    "message": "Delivery aktif ditemukan",
    "data": {
        "id": 1,
        "order_id": 1,
        "driver_id": 3,
        "status": "taken",
        "taken_at": "2026-06-25T09:00:00+07:00",
        "due_at": "2026-06-25T12:00:00+07:00",
        "order": {
            "id": 1,
            "status": "sedang_dikirim",
            "buyer": {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "phone": "081234567890"
            }
        },
        "address": {...}
    }
}
```

**Success (200 OK) - Tidak ada delivery aktif:**

```json
{
    "success": true,
    "message": "Tidak ada delivery aktif",
    "data": null
}
```

---

### 10.3 Take Job

**Endpoint:** `POST /api/driver/jobs/{deliveryId}/take`

Ambil job delivery.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: driver) |
| Accept        | application/json              |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Job berhasil diambil",
    "data": {
        "id": 1,
        "order_id": 1,
        "driver_id": 3,
        "status": "taken",
        "taken_at": "2026-06-25T09:00:00+07:00",
        "due_at": "2026-06-25T12:00:00+07:00"
    }
}
```

**Error - Masih ada delivery aktif (422):**

```json
{
    "success": false,
    "message": "Anda masih memiliki pengiriman aktif yang belum selesai"
}
```

**Error - Job sudah diambil (422):**

```json
{
    "success": false,
    "message": "Job ini sudah diambil driver lain"
}
```

---

### 10.4 Complete Job

**Endpoint:** `PUT /api/driver/jobs/{deliveryId}/complete`

Selesaikan job delivery. Driver mendapatkan earning (80% dari delivery_fee).

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: driver) |
| Accept        | application/json              |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Pengiriman berhasil diselesaikan. Earning: Rp 16.000",
    "data": {
        "id": 1,
        "order_id": 1,
        "driver_id": 3,
        "status": "completed",
        "completed_at": "2026-06-25T09:30:00+07:00"
    }
}
```

**Error - Bukan milik driver (403):**

```json
{
    "success": false,
    "message": "Anda tidak memiliki akses untuk pengiriman ini"
}
```

---

### 10.5 Get Delivery History

**Endpoint:** `GET /api/driver/jobs/history`

Ambil riwayat delivery driver.

#### Request

**Headers:**

| Header        | Value                         |
| ------------- | ----------------------------- |
| Authorization | Bearer {token} (role: driver) |
| Accept        | application/json              |

**Query Parameters (optional):**

| Parameter | Type | Description                  |
| --------- | ---- | ---------------------------- |
| page      | int  | Page number                  |
| per_page  | int  | Items per page (default: 15) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Riwayat delivery berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "order_id": 1,
                "status": "completed",
                "taken_at": "2026-06-25T09:00:00+07:00",
                "completed_at": "2026-06-25T09:30:00+07:00",
                "order": {
                    "id": 1,
                    "total": "71000.00",
                    "delivery_method": "instant",
                    "status": "pesanan_selesai"
                },
                "address": {
                    "recipient_name": "John Doe",
                    "address": "Jl. Sudirman No. 123"
                }
            }
        ],
        "per_page": 15,
        "total": 10
    }
}
```

---

## 11. Admin Module

### 11.0 Category Management

#### List Categories

**Endpoint:** `GET /api/admin/categories`

Ambil daftar semua kategori.

**Headers:** `Authorization: Bearer {token} (role: admin)`

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Makanan",
            "slug": "makanan",
            "icon": "🍔",
            "is_active": true
        }
    ]
}
```

#### Create Category

**Endpoint:** `POST /api/admin/categories`

Buat kategori baru.

**Headers:** `Authorization: Bearer {token} (role: admin)`

**Request:**
```json
{
    "name": "Minuman",
    "icon": "🥤",
    "is_active": true
}
```

**Response 201:**
```json
{
    "success": true,
    "message": "Kategori berhasil dibuat",
    "data": { ... }
}
```

#### Update Category

**Endpoint:** `PUT /api/admin/categories/{id}`

Update kategori.

**Request:**
```json
{
    "name": "Makanan Laut",
    "icon": "🐟",
    "is_active": true
}
```

#### Delete Category

**Endpoint:** `DELETE /api/admin/categories/{id}`

Hapus kategori. Kategori tidak bisa dihapus jika masih memiliki produk.

**Response 400:**
```json
{
    "success": false,
    "message": "Kategori tidak dapat dihapus karena masih memiliki produk"
}
```

---

### 11.1 List Vouchers

**Endpoint:** `GET /api/admin/vouchers`

Ambil daftar voucher.

#### Request

**Headers:**

| Header        | Value                        |
| ------------- | ---------------------------- |
| Authorization | Bearer {token} (role: admin) |
| Accept        | application/json             |

**Query Parameters (optional):**

| Parameter | Type | Description                  |
| --------- | ---- | ---------------------------- |
| page      | int  | Page number                  |
| per_page  | int  | Items per page (default: 15) |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Daftar voucher berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "code": "DISKON10",
                "type": "percentage",
                "value": "10.00",
                "expired_at": "2026-07-25T23:59:59+07:00",
                "max_usage": 100,
                "used_count": 5,
                "created_at": "2026-06-25T09:00:00+07:00"
            }
        ],
        "per_page": 15,
        "total": 3
    }
}
```

---

### 11.2 Create Voucher

**Endpoint:** `POST /api/admin/vouchers`

Buat voucher baru.

#### Request

**Headers:**

| Header        | Value                        |
| ------------- | ---------------------------- |
| Authorization | Bearer {token} (role: admin) |
| Content-Type  | application/json             |
| Accept        | application/json             |

**Body:**

```json
{
    "code": "DISKON10",
    "value": 10,
    "expired_at": "2026-07-25T23:59:59",
    "max_usage": 100
}
```

#### Validation Rules

| Field      | Type    | Required | Rules                |
| ---------- | ------- | -------- | -------------------- |
| code       | string  | ✅       | max 50 chars, unique |
| value      | numeric | ✅       | 1-100 (percentage)   |
| expired_at | date    | ✅       | after now            |
| max_usage  | int     | ✅       | min 1                |

#### Response

**Success (201 Created):**

```json
{
    "success": true,
    "message": "Voucher berhasil dibuat",
    "data": {
        "id": 1,
        "code": "DISKON10",
        "type": "percentage",
        "value": "10.00",
        "expired_at": "2026-07-25T23:59:59+07:00",
        "max_usage": 100,
        "used_count": 0,
        "created_at": "2026-06-25T09:00:00+07:00"
    }
}
```

---

### 11.3 Get Voucher Detail

**Endpoint:** `GET /api/admin/vouchers/{id}`

Ambil detail voucher.

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Detail voucher berhasil diambil",
    "data": { ... }
}
```

---

### 11.4 Update Voucher

**Endpoint:** `PUT /api/admin/vouchers/{id}`

Update voucher.

#### Request

**Headers:**

| Header        | Value                        |
| ------------- | ---------------------------- |
| Authorization | Bearer {token} (role: admin) |
| Content-Type  | application/json             |
| Accept        | application/json             |

**Body:**

```json
{
    "code": "DISKON15",
    "value": 15,
    "expired_at": "2026-08-25T23:59:59",
    "max_usage": 200
}
```

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Voucher berhasil diperbarui",
    "data": { ... }
}
```

---

### 11.5 Delete Voucher

**Endpoint:** `DELETE /api/admin/vouchers/{id}`

Hapus voucher.

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Voucher berhasil dihapus"
}
```

---

### 11.6 List Promos

**Endpoint:** `GET /api/admin/promos`

Ambil daftar promo.

#### Request

**Headers:**

| Header        | Value                        |
| ------------- | ---------------------------- |
| Authorization | Bearer {token} (role: admin) |
| Accept        | application/json             |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Daftar promo berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "code": null,
                "type": "percentage",
                "value": "5.00",
                "min_purchase": "50000.00",
                "expired_at": "2026-07-25T23:59:59+07:00",
                "created_at": "2026-06-25T09:00:00+07:00"
            }
        ],
        "per_page": 15,
        "total": 2
    }
}
```

---

### 11.7 Create Promo

**Endpoint:** `POST /api/admin/promos`

Buat promo baru.

#### Request

**Headers:**

| Header        | Value                        |
| ------------- | ---------------------------- |
| Authorization | Bearer {token} (role: admin) |
| Content-Type  | application/json             |
| Accept        | application/json             |

**Body:**

```json
{
    "value": 5,
    "min_purchase": 50000,
    "expired_at": "2026-07-25T23:59:59"
}
```

#### Validation Rules

| Field        | Type    | Required | Rules              |
| ------------ | ------- | -------- | ------------------ |
| value        | numeric | ✅       | 1-100 (percentage) |
| min_purchase | numeric | ✅       | min 0              |
| expired_at   | date    | ✅       | after now          |

#### Response

**Success (201 Created):**

```json
{
    "success": true,
    "message": "Promo berhasil dibuat",
    "data": {
        "id": 1,
        "code": null,
        "type": "percentage",
        "value": "5.00",
        "min_purchase": "50000.00",
        "expired_at": "2026-07-25T23:59:59+07:00",
        "created_at": "2026-06-25T09:00:00+07:00"
    }
}
```

---

### 11.8 Update Promo

**Endpoint:** `PUT /api/admin/promos/{id}`

Update promo.

#### Request

**Headers:**

| Header        | Value                        |
| ------------- | ---------------------------- |
| Authorization | Bearer {token} (role: admin) |
| Content-Type  | application/json             |
| Accept        | application/json             |

**Body:**

```json
{
    "value": 10,
    "min_purchase": 100000,
    "expired_at": "2026-08-25T23:59:59"
}
```

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Promo berhasil diperbarui",
    "data": { ... }
}
```

---

### 11.9 Delete Promo

**Endpoint:** `DELETE /api/admin/promos/{id}`

Hapus promo.

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Promo berhasil dihapus"
}
```

---

### 11.10 Get Time Simulation Status

**Endpoint:** `GET /api/admin/time-simulation`

Ambil status waktu simulasi.

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Status waktu simulasi berhasil diambil",
    "data": {
        "offset_hours": 0,
        "real_time": "2026-06-25T09:00:00+07:00",
        "simulated_time": "2026-06-25T09:00:00+07:00",
        "is_simulating": false
    }
}
```

---

### 11.11 Advance Time

**Endpoint:** `POST /api/admin/time-simulation/advance`

Majukan waktu simulasi.

#### Request

**Headers:**

| Header        | Value                        |
| ------------- | ---------------------------- |
| Authorization | Bearer {token} (role: admin) |
| Content-Type  | application/json             |
| Accept        | application/json             |

**Body:**

```json
{
    "hours": 24
}
```

#### Validation Rules

| Field | Type | Required | Rules |
| ----- | ---- | -------- | ----- |
| hours | int  | ✅       | min 1 |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Waktu simulasi berhasil dimajukan",
    "data": {
        "added_hours": 24,
        "new_offset_hours": 24,
        "simulated_time": "2026-06-26T09:00:00+07:00"
    }
}
```

---

### 11.12 Reset Time Simulation

**Endpoint:** `POST /api/admin/time-simulation/reset`

Reset waktu simulasi ke waktu real.

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Waktu simulasi berhasil direset",
    "data": {
        "offset_hours": 0,
        "real_time": "2026-06-25T09:00:00+07:00"
    }
}
```

---

### 11.13 Check Overdue Orders

**Endpoint:** `POST /api/admin/orders/check-overdue`

Cek dan proses order yang overdue (lewat due_at).

#### Request

**Headers:**

| Header        | Value                        |
| ------------- | ---------------------------- |
| Authorization | Bearer {token} (role: admin) |
| Accept        | application/json             |

#### Response

**Success (200 OK):**

```json
{
    "success": true,
    "message": "Pengecekan overdue order berhasil",
    "data": {
        "checked": 5,
        "overdue": 2,
        "orders": [
            {
                "id": 1,
                "status": "dikembalikan",
                "due_at": "2026-06-25T08:00:00+07:00"
            }
        ]
    }
}
```

---

## 12. Business Logic

### 12.1 Order Status Flow

```
sedang_dikemas → menunggu_pengirim → sedang_dikirim → pesanan_selesai
                    (seller process)      (driver take)     (driver complete)

                                                    ↓
                                              dikembalikan
                                            (jika overdue)
```

### 12.2 Delivery Fee

| Method   | Fee       |
| -------- | --------- |
| instant  | Rp 20.000 |
| next_day | Rp 12.000 |
| regular  | Rp 8.000  |

### 12.3 Due Date Calculation (after seller process)

| Method   | Due At   |
| -------- | -------- |
| instant  | +3 hours |
| next_day | +1 day   |
| regular  | +3 days  |

### 12.4 Discount Calculation (Checkout)

**Voucher:**

- User input voucher_code saat checkout
- Validasi: exists, not expired, not max usage
- Discount = voucher.value % dari subtotal

**Promo:**

- Auto-apply jika subtotal >= min_purchase
- Validasi: not expired, subtotal >= min_purchase
- Ambil promo dengan value tertinggi
- Discount = promo.value % dari subtotal

**Total Discount:**

- Jika ada voucher + promo, total percentage dijumlahkan
- `discountAmount = round(subtotal * (voucherValue + promoValue) / 100)`
- Maksimum discount = subtotal

### 12.5 PPN Calculation

PPN = 12% dari subtotal

```
total = subtotal + delivery_fee + ppn - discount_amount
```

### 12.6 Driver Earning

Driver earning = 80% dari delivery_fee

Contoh: delivery_fee = Rp 20.000 → earning = Rp 16.000

### 12.7 Wallet Transaction Types

| Type    | Description                  |
| ------- | ---------------------------- |
| topup   | User topup wallet            |
| payment | Payment untuk order          |
| refund  | Refund (future use)          |
| earning | Driver earning dari delivery |

### 12.8 Stock Validation

Saat checkout:

1. Cek apakah semua items stock >= quantity
2. Jika tidak cukup, return error dengan nama produk dan sisa stok

---

## 13. Error Handling

### 13.1 Generic Error Handler

```javascript
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${getToken()}`,
                ...options.headers,
            },
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                // Unauthorized - redirect to login
                logout();
                window.location.href = '/login';
                throw new Error('Session expired');
            }

            if (response.status === 422) {
                // Validation error
                const errors = data.errors;
                const message = Object.values(errors)[0][0];
                throw new Error(message);
            }

            throw new Error(data.message || 'Terjadi kesalahan');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}
```

### 13.2 Cart Operations

```javascript
async function addToCart(productId, quantity) {
    const data = await apiRequest('/api/cart/items', {
        method: 'POST',
        body: JSON.stringify({ product_id: productId, quantity }),
    });

    showToast(data.message, 'success');
    return data;
}

async function updateCartItem(itemId, quantity) {
    const data = await apiRequest(`/api/cart/items/${itemId}`, {
        method: 'PUT',
        body: JSON.stringify({ quantity }),
    });

    showToast(data.message, 'success');
    return data;
}

async function removeCartItem(itemId) {
    const data = await apiRequest(`/api/cart/items/${itemId}`, {
        method: 'DELETE',
    });

    showToast(data.message, 'success');
    return data;
}
```

### 13.3 Checkout Flow

```javascript
async function checkout(addressId, deliveryMethod, voucherCode = null) {
    try {
        const body = {
            address_id: addressId,
            delivery_method: deliveryMethod,
        };

        if (voucherCode) {
            body.voucher_code = voucherCode;
        }

        const data = await apiRequest('/api/checkout', {
            method: 'POST',
            body: JSON.stringify(body),
        });

        showToast(data.message, 'success');
        return data;
    } catch (error) {
        showToast(error.message, 'error');
        throw error;
    }
}
```

### 13.4 Wallet Topup Flow

```javascript
async function topupWallet(amount, paymentMethod) {
    try {
        const data = await apiRequest('/api/wallet/topup', {
            method: 'POST',
            body: JSON.stringify({
                amount,
                payment_method: paymentMethod,
            }),
        });

        // data.data contains:
        // - merchantOrderId
        // - paymentUrl (for redirect)
        // - vaNumber (for VA)
        // - qrString (for QRIS)

        if (data.data.paymentUrl) {
            window.location.href = data.data.paymentUrl;
        } else {
            // Show VA number or QR code
            showPaymentInfo(data.data);
        }

        return data;
    } catch (error) {
        showToast(error.message, 'error');
        throw error;
    }
}
```

### 13.5 Driver Job Flow

```javascript
async function takeJob(deliveryId) {
    try {
        const data = await apiRequest(`/api/driver/jobs/${deliveryId}/take`, {
            method: 'POST',
        });

        showToast(data.message, 'success');
        return data;
    } catch (error) {
        if (error.message.includes('aktif')) {
            // Show modal that driver already has active delivery
            showActiveDeliveryModal();
        }
        showToast(error.message, 'error');
    }
}

async function completeJob(deliveryId) {
    try {
        const data = await apiRequest(
            `/api/driver/jobs/${deliveryId}/complete`,
            {
                method: 'PUT',
            },
        );

        showToast(data.message, 'success'); 
        return data;
    } catch (error) {
        showToast(error.message, 'error');
        throw error;
    }
}
```

---

## 14. Form Request Migration (L7)

### Overview

Semua controller telah di-refactor untuk menggunakan **Form Request classes** untuk validasi input. Ini meningkatkan:
- **Keamanan**: Validasi terpusat di satu tempat
- **Kode bersih**: Controller fokus pada business logic
- **Reusable**: Validasi bisa dipakai ulang
- **Testable**: Form Request bisa di-test terpisah

### Daftar Form Request Classes

| Form Request | Controller | Method |
|--------------|-------------|--------|
| `Auth\LoginRequest` | `AuthController` | `login` |
| `Auth\RegisterRequest` | `AuthController` | `register` |
| `Auth\SwitchRoleRequest` | `AuthController` | `switchRole` |
| `Review\StoreReviewRequest` | `ReviewController` | `store` |
| `Cart\AddCartItemRequest` | `CartController` | `addItem` |
| `Cart\UpdateCartItemRequest` | `CartController` | `updateItem` |
| `Checkout\CheckoutRequest` | `CheckoutController` | `checkout` |
| `Wallet\TopupRequest` | `WalletController` | `topup` |
| `Seller\StoreStoreRequest` | `Seller\StoreController` | `store` |
| `Seller\UpdateStoreRequest` | `Seller\StoreController` | `update` |
| `Seller\StoreProductRequest` | `Seller\ProductController` | `store` |
| `Seller\UpdateProductRequest` | `Seller\ProductController` | `update` |
| `Admin\StoreVoucherRequest` | `Admin\VoucherController` | `store` |
| `Admin\UpdateVoucherRequest` | `Admin\VoucherController` | `update` |
| `Admin\StorePromoRequest` | `Admin\PromoController` | `store` |
| `Admin\UpdatePromoRequest` | `Admin\PromoController` | `update` |
| `Admin\AdvanceTimeRequest` | `Admin\TimeSimulationController` | `advance` |
| `ForgotPassword\SendOtpRequest` | `ForgotPasswordController` | `sendOtp` |
| `ForgotPassword\VerifyOtpRequest` | `ForgotPasswordController` | `verifyOtp` |
| `ForgotPassword\ResetPasswordRequest` | `ForgotPasswordController` | `resetPassword` |

### Struktur Folder Form Requests

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
├── Admin/
│   ├── StoreVoucherRequest.php
│   ├── UpdateVoucherRequest.php
│   ├── StorePromoRequest.php
│   ├── UpdatePromoRequest.php
│   └── AdvanceTimeRequest.php
└── ForgotPassword/
    ├── SendOtpRequest.php
    ├── VerifyOtpRequest.php
    └── ResetPasswordRequest.php
```

### XSS Prevention

Form Request `StoreReviewRequest.php` menggunakan `prepareForValidation()` untuk sanitasi input:

```php
protected function prepareForValidation(): void
{
    if ($this->has('comment')) {
        $this->merge([
            'comment' => strip_tags($this->comment),
        ]);
    }
}
```

Ini mencegah XSS attack dengan menghapus tag HTML/script dari comment sebelum validasi.

### Custom Error Messages

Semua Form Request memiliki custom error messages dalam Bahasa Indonesia:

```php
public function messages(): array
{
    return [
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'password.required' => 'Password wajib diisi.',
        // ...
    ];
}
```

---

## Quick Reference

### Role Middleware

| Role   | Endpoints                       |
| ------ | ------------------------------- |
| buyer  | cart, checkout, wallet, reviews |
| seller | seller/\*, reviews              |
| driver | driver/\*                       |
| admin  | admin/\*                        |

### Token Refresh

Setiap switch-role akan men-generate token baru. Token lama di-invalidate.

### Required Headers

```javascript
const defaultHeaders = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    Authorization: `Bearer ${token}`,
};
```

---

_Document generated: June 2026_
_Backend Version: Seapedia API v1.0_
