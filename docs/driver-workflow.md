# Dokumentasi: Driver Workflow — SEAPEDIA

## Overview

Fitur Driver Workflow memungkinkan driver untuk mengambil dan menyelesaikan job delivery. Fitur ini merupakan bagian dari Level 5 yang mengelola alur pengiriman dari driver mengambil order hingga menyelesaikan delivery dan mendapatkan earning.

## Diagram Alur Delivery

```
┌─────────────────────┐
│  sedang_dikemas     │  ← Status Awal (dari Checkout)
└────────┬────────────┘
         │ Seller proses order
         ▼
┌─────────────────────┐
│  menunggu_pengirim  │  ← Seller OrderController::process()
└────────┬────────────┘
         │ Delivery record dibuat (status: available)
         ▼
┌───────────────────┐
│  available        │  ← Delivery status
└────────┬──────────┘
         │ Driver take job
         ▼
┌───────────────┐
│  taken        │  ← Driver mengambil job
└───────┬───────┘
        │ Order status: sedang_dikirim
        ▼
┌───────────────────┐
│  sedang_dikirim   │  ← Order status
└────────┬──────────┘
         │ Driver complete job
         ▼
┌─────────────────┐
│  completed      │  ← Delivery status
└────────┬────────┘
        │ Order status: pesanan_selesai + Earning 80%
        ▼
┌─────────────────┐
│ pesanan_selesai  │  ← Order status (FINAL)
└─────────────────┘
```

## Durasi due_at Berdasarkan delivery_method

| delivery_method | due_at         | Keterangan         |
| --------------- | -------------- | ------------------ |
| instant         | now() + 3 jam  | Pengiriman segera  |
| next_day        | now() + 1 hari | Pengiriman besok   |
| regular         | now() + 3 hari | Pengiriman reguler |

## Alur Lengkap: Seller → Driver → Selesai

### Langkah 1: Seller Memproses Order

1. Buyer checkout → Order status: `sedang_dikemas`
2. Seller memanggil `PUT /api/seller/orders/{id}/process`
3. System membuat Delivery record (status: `available`)
4. Order status berubah: `sedang_dikemas` → `menunggu_pengirim`

### Langkah 2: Driver Mengambil Job

1. Driver melihat job available: `GET /api/driver/jobs`
2. Driver mengambil job: `POST /api/driver/jobs/{deliveryId}/take`
3. Delivery status berubah: `available` → `taken`
4. Order status berubah: `menunggu_pengirim` → `sedang_dikirim`
5. Driver hanya boleh punya 1 job aktif (`status = 'taken'`)

### Langkah 3: Driver Menyelesaikan Job

1. Driver memanggil: `PUT /api/driver/jobs/{deliveryId}/complete`
2. Delivery status berubah: `taken` → `completed`
3. Order status berubah: `sedang_dikirim` → `pesanan_selesai`
4. **Earning dihitung**: `delivery_fee × 80%`
5. Wallet driver di-topup otomatis

---

## Endpoint

### 1. List Job Available

**Endpoint:** `GET /api/driver/jobs`

**Middleware:** `auth:sanctum`, `role:driver`

**Contoh Request:**

```bash
curl -X GET "http://localhost:8000/api/driver/jobs" \
  -H "Authorization: Bearer {token}"
```

**Contoh Response Sukses:**

```json
{
    "success": true,
    "message": "Daftar job berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "order_id": 5,
                "driver_id": null,
                "status": "available",
                "due_at": "2026-06-22T12:30:00.000000Z",
                "created_at": "2026-06-19T10:30:00.000000Z",
                "order": {
                    "id": 5,
                    "delivery_method": "instant",
                    "total": 150000,
                    "status": "menunggu_pengirim",
                    "address": {
                        "id": 1,
                        "address": "Jl. Sudirman No. 123",
                        "recipient_name": "John Doe",
                        "phone": "081234567890"
                    }
                }
            }
        ],
        "per_page": 15,
        "total": 1
    }
}
```

---

### 2. Ambil Job (Take)

**Endpoint:** `POST /api/driver/jobs/{deliveryId}/take`

**Middleware:** `auth:sanctum`, `role:driver`

**Contoh Request:**

```bash
curl -X POST "http://localhost:8000/api/driver/jobs/1/take" \
  -H "Authorization: Bearer {token}"
```

**Contoh Response Sukses:**

```json
{
  "success": true,
  "message": "Job berhasil diambil",
  "data": {
    "id": 1,
    "order_id": 5,
    "driver_id": 3,
    "status": "taken",
    "taken_at": "2026-06-19T12:30:00.000000Z",
    "due_at": "2026-06-22T12:30:00.000000Z",
    "order": {...},
    "driver": {
      "id": 3,
      "name": "Driver One",
      "username": "driver1"
    }
  }
}
```

**Contoh Response Error (driver sudah punya job aktif):**

```json
{
    "success": false,
    "message": "Anda masih memiliki pengiriman aktif yang belum selesai"
}
```

**Status Code:** 422

**Contoh Response Error (job sudah diambil driver lain):**

```json
{
    "success": false,
    "message": "Job ini sudah diambil driver lain"
}
```

**Status Code:** 422

---

### 3. Selesaikan Job (Complete)

**Endpoint:** `PUT /api/driver/jobs/{deliveryId}/complete`

**Middleware:** `auth:sanctum`, `role:driver`

**Contoh Request:**

```bash
curl -X PUT "http://localhost:8000/api/driver/jobs/1/complete" \
  -H "Authorization: Bearer {token}"
```

**Contoh Response Sukses:**

```json
{
  "success": true,
  "message": "Pengiriman berhasil diselesaikan. Earning: Rp 20.000",
  "data": {
    "id": 1,
    "order_id": 5,
    "driver_id": 3,
    "status": "completed",
    "completed_at": "2026-06-19T14:30:00.000000Z",
    "order": {...},
    "driver": {...}
  }
}
```

**Contoh Response Error (bukan milik driver):**

```json
{
    "success": false,
    "message": "Anda tidak memiliki akses untuk pengiriman ini"
}
```

**Status Code:** 403

**Contoh Response Error (status bukan taken):**

```json
{
    "success": false,
    "message": "Pengiriman ini belum dalam status diambil"
}
```

**Status Code:** 422

---

### 4. Riwayat Delivery

**Endpoint:** `GET /api/driver/jobs/history`

**Middleware:** `auth:sanctum`, `role:driver`

**Contoh Request:**

```bash
curl -X GET "http://localhost:8000/api/driver/jobs/history" \
  -H "Authorization: Bearer {token}"
```

**Contoh Response Sukses:**

```json
{
    "success": true,
    "message": "Riwayat delivery berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "order_id": 5,
                "status": "completed",
                "taken_at": "2026-06-19T12:30:00.000000Z",
                "completed_at": "2026-06-19T14:30:00.000000Z",
                "order": {
                    "id": 5,
                    "total": 150000,
                    "delivery_method": "instant",
                    "status": "pesanan_selesai",
                    "address": {
                        "recipient_name": "John Doe",
                        "address": "Jl. Sudirman No. 123"
                    }
                }
            }
        ],
        "per_page": 15,
        "total": 1
    }
}
```

---

### 5. Delivery Aktif

**Endpoint:** `GET /api/driver/jobs/active`

**Middleware:** `auth:sanctum`, `role:driver`

**Contoh Request:**

```bash
curl -X GET "http://localhost:8000/api/driver/jobs/active" \
  -H "Authorization: Bearer {token}"
```

**Contoh Response (ada job aktif):**

```json
{
  "success": true,
  "message": "Delivery aktif ditemukan",
  "data": {
    "id": 2,
    "order_id": 6,
    "driver_id": 3,
    "status": "taken",
    "taken_at": "2026-06-19T13:00:00.000000Z",
    "due_at": "2026-06-22T13:00:00.000000Z",
    "order": {
      "address": {...},
      "buyer": {
        "id": 2,
        "name": "Jane Doe",
        "username": "janedoe",
        "phone": "089876543210"
      }
    }
  }
}
```

**Contoh Response (tidak ada job aktif):**

```json
{
    "success": true,
    "message": "Tidak ada delivery aktif",
    "data": null
}
```

---

## Skenario Error

### 1. Unauthorized (tidak login)

```json
{
    "message": "Unauthenticated."
}
```

**Status Code:** 401

### 2. Forbidden (bukan role driver)

```json
{
    "message": "Forbidden."
}
```

**Status Code:** 403

### 3. Driver punya job aktif

```json
{
    "success": false,
    "message": "Anda masih memiliki pengiriman aktif yang belum selesai"
}
```

**Status Code:** 422

### 4. Job sudah diambil driver lain

```json
{
    "success": false,
    "message": "Job ini sudah diambil driver lain"
}
```

**Status Code:** 422

### 5. Delivery tidak ditemukan

```json
{
    "success": false,
    "message": "Delivery tidak ditemukan"
}
```

**Status Code:** 404

### 6. Tidak punya akses ke delivery

```json
{
    "success": false,
    "message": "Anda tidak memiliki akses untuk pengiriman ini"
}
```

**Status Code:** 403

### 7. Delivery belum diambil

```json
{
    "success": false,
    "message": "Pengiriman ini belum dalam status diambil"
}
```

**Status Code:** 422

---

## Struktur File

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── Driver/
│           │   └── DeliveryController.php   ← BARU
│           └── Seller/
│               └── OrderController.php       ← MODIFIED (process method)

app/Models/
├── Delivery.php                              ← BARU
└── Order.php                                 ← MODIFIED (delivery relation)

routes/
└── api.php                                   ← MODIFIED (driver routes)
```

---

## Relasi Database

### Delivery.php

```php
public function order()
{
    return $this->belongsTo(Order::class);
}

public function driver()
{
    return $this->belongsTo(User::class, 'driver_id');
}
```

### Order.php

```php
public function delivery()
{
    return $this->hasOne(Delivery::class);
}
```

---

## Perhitungan Earning

Earning driver dihitung dari `delivery_fee` order:

```
Earning = round(delivery_fee × 0.8)
```

**Contoh:**

- Order delivery_fee: Rp 25.000
- Earning driver (80%): Rp 20.000
- System/platform: Rp 5.000

---

## Batasan Fitur (Level 5)

- ✅ Driver bisa melihat job available
- ✅ Driver bisa mengambil job (dengan validasi 1 job aktif)
- ✅ Driver bisa menyelesaikan job + dapat earning
- ✅ Driver bisa melihat riwayat delivery
- ✅ Driver bisa melihat job aktif
- ❌ Overdue handling (scope Level 6)
- ❌ Geolocation/radius (belum ada)
- ❌ Return/refund handling (scope Level 6)

---

## Catatan Pengembangan

1. **Atomicity**: Semua update status menggunakan `DB::transaction()`
2. **Validasi**: Driver hanya boleh punya 1 job aktif
3. **Wallet**: Wallet driver dibuat otomatis jika belum ada (`firstOrCreate`)
4. **due_at**: Hanya sebagai data, tidak ada auto-check overdue saat ini
5. Semua pesan response menggunakan Bahasa Indonesia
