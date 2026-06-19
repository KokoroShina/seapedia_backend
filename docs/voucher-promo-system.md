# Dokumentasi Voucher & Promo System - SEAPEDIA

## Overview

Sistem Voucher & Promo untuk SEAPEDIA memungkinkan admin untuk membuat voucher dan promo diskon. Buyer dapat menggunakan voucher secara manual saat checkout, sedangkan promo otomatis diterapkan berdasarkan minimum purchase.

## Tipe Diskon

- **Tipe**: Hanya `percentage` (persentase) untuk saat ini
- **Nilai**: `value` harus antara 1-100 (%)

---

## Endpoint Admin - Voucher

### 1. GET /api/admin/vouchers

Daftar semua voucher dengan pagination (15 per halaman), diurutkan dari yang terbaru.

**Headers:**
```
Authorization: Bearer {token}
```

**Response Sukses:**
```json
{
  "success": true,
  "message": "Daftar voucher berhasil diambil",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "code": "DISKON20",
        "type": "percentage",
        "value": "20.00",
        "expired_at": "2026-12-31T23:59:59.000000Z",
        "max_usage": 100,
        "used_count": 45,
        "created_at": "2026-06-01T00:00:00.000000Z"
      }
    ],
    "total": 1,
    "per_page": 15
  }
}
```

---

### 2. POST /api/admin/vouchers

Buat voucher baru.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "code": "DISKON20",
  "value": 20,
  "expired_at": "2026-12-31",
  "max_usage": 100
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| code | string | Yes | max:50, unique |
| value | numeric | Yes | min:1, max:100 |
| expired_at | date | Yes | after:now |
| max_usage | integer | Yes | min:1 |

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Voucher berhasil dibuat",
  "data": {
    "id": 1,
    "code": "DISKON20",
    "type": "percentage",
    "value": "20.00",
    "expired_at": "2026-12-31T23:59:59.000000Z",
    "max_usage": 100,
    "used_count": 0,
    "created_at": "2026-06-18T20:00:00.000000Z"
  }
}
```

---

### 3. GET /api/admin/vouchers/{id}

Detail voucher berdasarkan ID.

**Response Sukses:**
```json
{
  "success": true,
  "message": "Detail voucher berhasil diambil",
  "data": {
    "id": 1,
    "code": "DISKON20",
    "type": "percentage",
    "value": "20.00",
    "expired_at": "2026-12-31T23:59:59.000000Z",
    "max_usage": 100,
    "used_count": 45,
    "created_at": "2026-06-01T00:00:00.000000Z"
  }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Voucher tidak ditemukan"
}
```

---

### 4. PUT /api/admin/vouchers/{id}

Update voucher.

**Request Body (all fields optional):**
```json
{
  "code": "DISKON25",
  "value": 25,
  "expired_at": "2027-06-30",
  "max_usage": 200
}
```

**Response Sukses:**
```json
{
  "success": true,
  "message": "Voucher berhasil diperbarui",
  "data": { ... }
}
```

---

### 5. DELETE /api/admin/vouchers/{id}

Hapus voucher.

**Response Sukses:**
```json
{
  "success": true,
  "message": "Voucher berhasil dihapus"
}
```

---

## Endpoint Admin - Promo

### 1. GET /api/admin/promos

Daftar semua promo dengan pagination (15 per halaman), diurutkan dari yang terbaru.

**Headers:**
```
Authorization: Bearer {token}
```

**Response Sukses:**
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
        "value": "15.00",
        "min_purchase": "100000.00",
        "expired_at": "2026-12-31T23:59:59.000000Z",
        "created_at": "2026-06-01T00:00:00.000000Z"
      }
    ],
    "total": 1,
    "per_page": 15
  }
}
```

---

### 2. POST /api/admin/promos

Buat promo baru. Promo TIDAK memiliki kode - otomatis aktif saat checkout jika syarat min_purchase terpenuhi.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "value": 15,
  "min_purchase": 100000,
  "expired_at": "2026-12-31"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| value | numeric | Yes | min:1, max:100 |
| min_purchase | numeric | Yes | min:0 |
| expired_at | date | Yes | after:now |

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Promo berhasil dibuat",
  "data": {
    "id": 1,
    "code": null,
    "type": "percentage",
    "value": "15.00",
    "min_purchase": "100000.00",
    "expired_at": "2026-12-31T23:59:59.000000Z",
    "created_at": "2026-06-18T20:00:00.000000Z"
  }
}
```

---

### 3. GET /api/admin/promos/{id}

Detail promo berdasarkan ID.

**Response Sukses:**
```json
{
  "success": true,
  "message": "Detail promo berhasil diambil",
  "data": { ... }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Promo tidak ditemukan"
}
```

---

### 4. PUT /api/admin/promos/{id}

Update promo.

**Request Body (all fields optional):**
```json
{
  "value": 20,
  "min_purchase": 150000,
  "expired_at": "2027-06-30"
}
```

**Response Sukses:**
```json
{
  "success": true,
  "message": "Promo berhasil diperbarui",
  "data": { ... }
}
```

---

### 5. DELETE /api/admin/promos/{id}

Hapus promo.

**Response Sukses:**
```json
{
  "success": true,
  "message": "Promo berhasil dihapus"
}
```

---

## Endpoint Checkout - Dengan Voucher/Promo

### POST /api/checkout

Checkout dengan opsional voucher_code.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "address_id": 1,
  "delivery_method": "instant",
  "voucher_code": "DISKON20"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| address_id | integer | Yes | ID alamat pengiriman |
| delivery_method | string | Yes | `instant`, `next_day`, atau `regular` |
| voucher_code | string | No | Kode voucher (opsional) |

**Response Sukses (201):**
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
    "promo_id": 1,
    "delivery_method": "instant",
    "subtotal": "200000.00",
    "discount_amount": "70000.00",
    "delivery_fee": "20000.00",
    "ppn": "24000.00",
    "total": "174000.00",
    "status": "sedang_dikemas",
    "created_at": "2026-06-18T20:00:00.000000Z",
    "updated_at": "2026-06-18T20:00:00.000000Z",
    "discount_details": {
      "discount_amount": 70000,
      "voucher_id": 1,
      "promo_id": 1
    }
  }
}
```

---

## Skenario Error Checkout (Voucher)

### 1. Voucher Tidak Ditemukan (422)

```json
{
  "success": false,
  "message": "Kode voucher tidak valid"
}
```

**Kondisi:** Kode voucher yang diinput tidak ada di database.

---

### 2. Voucher Expired (422)

```json
{
  "success": false,
  "message": "Kode voucher sudah kadaluarsa"
}
```

**Kondisi:** Tanggal `expired_at` sudah lewat dari waktu sekarang.

---

### 3. Voucher Mencapai Batas Penggunaan (422)

```json
{
  "success": false,
  "message": "Kode voucher sudah mencapai batas penggunaan"
}
```

**Kondisi:** `used_count >= max_usage`.

---

## Cara Kerja Stacking Diskon (Voucher + Promo)

### Rumus Perhitungan

```
total_percentage = voucher.value + promo.value
discount_amount = round(subtotal * total_percentage / 100)
```

### Aturan Penting

1. **Stacking bisa digabungkan**: Voucher + Promo BISA dipakai bersamaan dalam satu checkout
2. **Diskon dihitung sekaligus**: Total persentase diskon = voucher + promo
3. **Hanya SATU promo per checkout**: Jika ada lebih dari satu promo valid, dipilih yang memiliki `value` (persentase) TERBESAR
4. **Diskon dipotong SEKALI**: Bukan dipotong bertingkat/berurutan

---

## Contoh Perhitungan Konkret

### Contoh 1: Hanya Voucher

**Kondisi:**
- Subtotal: Rp 200.000
- Voucher: 20% diskon
- Promo: Tidak ada yang aktif (subtotal < min_purchase)

**Perhitungan:**
```
total_percentage = 20 + 0 = 20
discount_amount = round(200000 * 20 / 100) = 40.000
total = 200.000 + 20.000 + 24.000 - 40.000 = 204.000
```

---

### Contoh 2: Hanya Promo

**Kondisi:**
- Subtotal: Rp 150.000
- Voucher: Tidak ada (buyer tidak input voucher_code)
- Promo: 15% diskon (min_purchase: Rp 100.000)

**Perhitungan:**
```
total_percentage = 0 + 15 = 15
discount_amount = round(150000 * 15 / 100) = 22.500
total = 150.000 + 12.000 + 18.000 - 22.500 = 157.500
```

---

### Contoh 3: Voucher + Promo (Stacking)

**Kondisi:**
- Subtotal: Rp 200.000
- Voucher: 20% diskon
- Promo: 15% diskon (min_purchase: Rp 100.000)

**Perhitungan:**
```
total_percentage = 20 + 15 = 35
discount_amount = round(200000 * 35 / 100) = 70.000
total = 200.000 + 20.000 + 24.000 - 70.000 = 174.000
```

---

### Contoh 4: Banyak Promo, Pilih yang Terbesar

**Kondisi:**
- Subtotal: Rp 200.000
- Voucher: Tidak ada
- Promo A: 10% (min_purchase: Rp 50.000)
- Promo B: 15% (min_purchase: Rp 100.000)
- Promo C: 20% (min_purchase: Rp 150.000)

**Perhitungan:**
- Semua promo valid karena subtotal >= min_purchase
- Dipilih Promo C (20%) karena memiliki value TERBESAR

```
total_percentage = 0 + 20 = 20
discount_amount = round(200000 * 20 / 100) = 40.000
total = 200.000 + 20.000 + 24.000 - 40.000 = 204.000
```

---

### Contoh 5: Subtotal Tidak Mencukupi Promo

**Kondisi:**
- Subtotal: Rp 80.000
- Voucher: Tidak ada
- Promo: 15% diskon (min_purchase: Rp 100.000)

**Perhitungan:**
- Promo TIDAK aktif karena subtotal < min_purchase

```
discount_amount = 0
total = 80.000 + 8.000 + 9.600 - 0 = 97.600
```

---

## Ringkasan Flow Checkout

```
1. Buyer input address_id, delivery_method, (opsional) voucher_code
2. Hitung subtotal dari cart
3. Validasi voucher (jika ada):
   - Cek voucher ditemukan
   - Cek voucher belum expired
   - Cek voucher belum reach max_usage
4. Cari promo otomatis (jika subtotal >= min_purchase):
   - Pilih promo dengan value TERBESAR
5. Hitung discount_amount = round(subtotal * (voucher_value + promo_value) / 100)
6. Hitung total = subtotal + delivery_fee + ppn - discount_amount
7. Validasi saldo wallet
8. Buat order dalam transaksi:
   - Insert order dengan voucher_id & promo_id
   - Increment used_count voucher (jika voucher dipakai)
   - Kurangi stock produk
   - Kurangi saldo wallet
   - Buat wallet transaction
   - Kosongkan cart
9. Return response dengan discount_details
```

---

## Struktur Database

### Tabel vouchers

| Kolom | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| code | varchar(50) | Kode voucher (unique) |
| type | varchar | Selalu 'percentage' |
| value | decimal(15,2) | Persentase diskon (1-100) |
| expired_at | timestamp | Tanggal kadaluarsa |
| max_usage | int | Batas maksimum penggunaan |
| used_count | int | Jumlah sudah digunakan (default 0) |
| created_at | timestamp | Tanggal dibuat |

### Tabel promos

| Kolom | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| code | varchar(50) | Nullable (tidak dipakai) |
| type | varchar | Selalu 'percentage' |
| value | decimal(15,2) | Persentase diskon (1-100) |
| min_purchase | decimal(15,2) | Syarat subtotal minimal |
| expired_at | timestamp | Tanggal kadaluarsa |
| created_at | timestamp | Tanggal dibuat |

### Tabel orders (field terkait)

| Kolom | Type | Description |
|-------|------|-------------|
| voucher_id | bigint | FK ke vouchers (nullable) |
| promo_id | bigint | FK ke promos (nullable) |
| discount_amount | decimal(15,2) | Total diskon |

---

## Catatan Penting

1. **Voucher** dibuat oleh admin, kode diinput manual oleh buyer saat checkout
2. **Promo** tidak punya kode, otomatis aktif jika subtotal >= min_purchase
3. **Stacking** voucher + promo BISA digabungkan
4. **Hanya 1 promo** yang aktif - yang memiliki value terbesar
5. **Diskon SEKALI** dari total persentase, bukan bertingkat
6. **PPN** dihitung dari subtotal SEBELUM diskon
7. **Validasi voucher** dilakukan sebelum checkout diproses
8. **used_count** voucher diincrement setelah checkout sukses
