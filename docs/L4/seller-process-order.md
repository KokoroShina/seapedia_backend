# Dokumentasi: Seller Process Order — SEAPEDIA

## Overview

Fitur Seller Process Order memungkinkan seller untuk melihat dan memproses order yang masuk ke toko mereka. Fitur ini merupakan bagian dari Level 4 yang fokus pada transisi status order dari `sedang_dikemas` ke `menunggu_pengirim`.

## Diagram Alur Status Order

```
┌─────────────────┐
│  sedang_dikemas │  ← Status Awal (dari Checkout)
└────────┬────────┘
         │ Seller proses order
         ▼
┌─────────────────────┐
│  menunggu_pengirim  │  ← LEVEL 4 (SAAT INI DIIMPLEMENTASIKAN)
└────────┬────────────┘
         │ Driver mengambil
         ▼
┌───────────────────┐
│  sedang_dikirim   │  ← LEVEL 5 (BELUM diimplementasikan)
└────────┬──────────┘
         │ Driver konfirmasi selesai
         ▼
┌─────────────────┐
│ pesanan_selesai  │  ← LEVEL 5 (BELUM diimplementasikan)
└─────────────────┘

--- ALUR ALTERNATIF (jika overdue) ---
         ▼
┌───────────────┐
│ dikembalikan  │  ← LEVEL 6 (BELUM diimplementasikan)
└───────────────┘
```

### Legenda:
- ✅ **HITAM** = Sudah diimplementasikan (Level 4)
- ⚫ **abu-abu** = Scope Level 5 (BELUM diimplementasikan)
- ⚪ **putih** = Scope Level 6 (BELUM diimplementasikan)

---

## Endpoint

### 1. List Order Masuk ke Toko

**Endpoint:** `GET /api/seller/orders`

**Middleware:** `auth:sanctum`, `role:seller`

**Query Parameters (opsional):**
| Parameter | Tipe    | Deskripsi                              |
|-----------|---------|----------------------------------------|
| status    | string  | Filter berdasarkan status order       |
| page      | integer | Nomor halaman (default: 1)             |

**Contoh Request:**
```bash
# Semua order
curl -X GET "http://localhost:8000/api/seller/orders" \
  -H "Authorization: Bearer {token}"

# Filter berdasarkan status
curl -X GET "http://localhost:8000/api/seller/orders?status=sedang_dikemas" \
  -H "Authorization: Bearer {token}"
```

**Contoh Response Sukses:**
```json
{
  "success": true,
  "message": "Daftar order berhasil diambil",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "buyer": {
          "id": 2,
          "name": "John Doe",
          "username": "johndoe"
        },
        "status": "sedang_dikemas",
        "total": 150000,
        "delivery_method": "express",
        "created_at": "2026-06-19T10:30:00.000000Z",
        "items_count": 3
      }
    ],
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

**Contoh Response Error (toko tidak ditemukan):**
```json
{
  "success": false,
  "message": "Anda belum memiliki toko"
}
```

---

### 2. Detail Order

**Endpoint:** `GET /api/seller/orders/{id}`

**Middleware:** `auth:sanctum`, `role:seller`

**Path Parameters:**
| Parameter | Tipe    | Deskripsi           |
|-----------|---------|---------------------|
| id        | integer | ID order yang dicari|

**Contoh Request:**
```bash
curl -X GET "http://localhost:8000/api/seller/orders/1" \
  -H "Authorization: Bearer {token}"
```

**Contoh Response Sukses:**
```json
{
  "success": true,
  "message": "Detail order berhasil diambil",
  "data": {
    "id": 1,
    "buyer_id": 2,
    "store_id": 1,
    "address_id": 1,
    "delivery_method": "express",
    "subtotal": 120000,
    "discount_amount": 0,
    "delivery_fee": 25000,
    "ppn": 12000,
    "total": 157000,
    "status": "sedang_dikemas",
    "created_at": "2026-06-19T10:30:00.000000Z",
    "updated_at": "2026-06-19T10:30:00.000000Z",
    "buyer": {
      "id": 2,
      "name": "John Doe",
      "username": "johndoe",
      "email": "john@example.com"
    },
    "items": [
      {
        "id": 1,
        "product_id": 5,
        "product_name": "Kaos Polos Navy",
        "product_price": 60000,
        "quantity": 2,
        "subtotal": 120000
      }
    ],
    "address": {
      "id": 1,
      "recipient_name": "John Doe",
      "phone": "081234567890",
      "address_line": "Jl. Sudirman No. 123",
      "city": "Jakarta Selatan",
      "province": "DKI Jakarta",
      "postal_code": "12345"
    },
    "status_histories": [
      {
        "id": 1,
        "status": "sedang_dikemas",
        "note": "Pesanan berhasil dibuat",
        "created_at": "2026-06-19T10:30:00.000000Z"
      }
    ]
  }
}
```

**Contoh Response Error (order tidak ditemukan/tidak milik toko):**
```json
{
  "success": false,
  "message": "Order tidak ditemukan"
}
```

---

### 3. Proses Order (Update Status)

**Endpoint:** `PUT /api/seller/orders/{id}/process`

**Middleware:** `auth:sanctum`, `role:seller`

**Path Parameters:**
| Parameter | Tipe    | Deskripsi                    |
|-----------|---------|------------------------------|
| id        | integer | ID order yang akan diproses  |

**Deskripsi:**  
Mengubah status order dari `sedang_dikemas` menjadi `menunggu_pengirim`. Tidak memerlukan body request karena transisi status sudah pasti.

**Contoh Request:**
```bash
curl -X PUT "http://localhost:8000/api/seller/orders/1/process" \
  -H "Authorization: Bearer {token}"
```

**Contoh Response Sukses:**
```json
{
  "success": true,
  "message": "Order berhasil diproses dan menunggu pengirim",
  "data": {
    "id": 1,
    "buyer_id": 2,
    "store_id": 1,
    "status": "menunggu_pengirim",
    "created_at": "2026-06-19T10:30:00.000000Z",
    "updated_at": "2026-06-19T11:00:00.000000Z",
    "buyer": {
      "id": 2,
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
        "created_at": "2026-06-19T10:30:00.000000Z"
      },
      {
        "id": 2,
        "status": "menunggu_pengirim",
        "note": "Pesanan telah dikemas dan menunggu driver",
        "created_at": "2026-06-19T11:00:00.000000Z"
      }
    ]
  }
}
```

**Contoh Response Error (order sudah diproses sebelumnya):**
```json
{
  "success": false,
  "message": "Order ini sudah tidak bisa diproses ulang oleh penjual"
}
```
**Status Code:** 422

---

## Skenario Error

### 1. Seller belum memiliki toko
```json
{
  "success": false,
  "message": "Anda belum memiliki toko"
}
```
**Status Code:** 404

### 2. Order tidak ditemukan / bukan milik toko sendiri
```json
{
  "success": false,
  "message": "Order tidak ditemukan"
}
```
**Status Code:** 404

### 3. Order sudah diproses sebelumnya
```json
{
  "success": false,
  "message": "Order ini sudah tidak bisa diproses ulang oleh penjual"
}
```
**Status Code:** 422

### 4. Unauthorized (tidak login)
```json
{
  "message": "Unauthenticated."
}
```
**Status Code:** 401

### 5. Forbidden (bukan role seller)
```json
{
  "message": "Forbidden."
}
```
**Status Code:** 403

---

## Struktur File

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── Seller/
│               └── OrderController.php   ← BARU

routes/
└── api.php                                ← UPDATED

app/Models/
├── Store.php                               ← UPDATED (relasi orders)
└── Order.php                               ← UPDATED (relasi buyer)
```

---

## Relasi Database

### Store.php
```php
public function orders()
{
    return $this->hasMany(Order::class);
}
```

### Order.php
```php
public function buyer()
{
    return $this->belongsTo(User::class, 'buyer_id');
}
```

---

## Batasan Fitur (Level 4)

- ✅ Seller dapat melihat list order masuk
- ✅ Seller dapat melihat detail order lengkap
- ✅ Seller dapat memproses order (`sedang_dikemas` → `menunggu_pengirim`)
- ❌ Seller TIDAK dapat mengubah ke `sedang_dikirim` (scope Level 5)
- ❌ Seller TIDAK dapat mengubah ke `pesanan_selesai` (scope Level 5)
- ❌ Seller TIDAK dapat mengubah ke `dikembalikan` (scope Level 6)

---

## Implementasi di Masa Mendatang

### Level 5: Driver & Delivery
- Transisi status `menunggu_pengirim` → `sedang_dikirim`
- Transisi status `sedang_dikirim` → `pesanan_selesai`
- Endpoint untuk driver mengambil order
- Endpoint untuk driver konfirmasi pesanan selesai

### Level 6: Return & Refund
- Transisi status ke `dikembalikan`
- Alur pengembalian barang
- Proses refund ke wallet

---

## Catatan Pengembangan

1. Setiap perubahan status **WAJIB** menambahkan record baru di `order_status_histories`
2. Validasi ownership dilakukan berdasarkan `store.user_id = auth user id`
3. Semua pesan response menggunakan Bahasa Indonesia
4. Format response standar JSON dengan struktur `success`, `message`, dan `data`
