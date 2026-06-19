# Dokumentasi Wallet Top-up via DuitKu Payment Gateway - SEAPEDIA

## Daftar Endpoint

### 1. Lihat Saldo Wallet
```
GET /api/wallet
```
**Auth:** Required (Sanctum)

**Response Sukses:**
```json
{
  "success": true,
  "message": "Saldo wallet berhasil diambil",
  "data": {
    "id": 1,
    "user_id": 1,
    "balance": 50000,
    "created_at": "2026-06-18T10:00:00.000000Z",
    "updated_at": "2026-06-18T10:00:00.000000Z"
  }
}
```

---

### 2. Request Top-up via DuitKu
```
POST /api/wallet/topup
```
**Auth:** Required (Sanctum)

**Request Body:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| amount | integer | Yes | Nominal top-up (minimal 10000) |
| payment_method | string | Yes | Kode metode pembayaran DuitKu |

**Contoh Request:**
```json
{
  "amount": 100000,
  "payment_method": "QRIS"
}
```

**Response Sukses:**
```json
{
  "success": true,
  "message": "Transaksi top-up berhasil dibuat",
  "data": {
    "merchantOrderId": "TOPUP-1-1750320000-a1b2c3d4-e5f6-7890",
    "reference": "REF123456",
    "amount": 100000,
    "status": "pending",
    "paymentUrl": "https://sandbox.duitku.com/pay/xxx",
    "vaNumber": null,
    "qrString": null
  }
}
```

---

### 3. Callback DuitKu (Public)
```
POST /api/wallet/topup/callback
```
**Auth:** None (Public endpoint untuk menerima callback dari DuitKu)

**Request dari DuitKu:**
| Parameter | Type | Description |
|-----------|------|-------------|
| merchantCode | string | Kode merchant |
| amount | integer | Nominal transaksi |
| merchantOrderId | string | Order ID unik |
| signature | string | MD5 signature untuk validasi |
| resultCode | string | Kode hasil ('00' = sukses) |

**Response:**
- `OK` - Berhasil diproses
- `SIGNATURE_VALIDATION_FAILED` - Signature tidak valid
- `ORDER_NOT_FOUND` - Transaksi tidak ditemukan

---

### 4. Cek Status Transaksi
```
GET /api/wallet/topup/status/{merchantOrderId}
```
**Auth:** Required (Sanctum)

**Response Sukses:**
```json
{
  "success": true,
  "message": "Status transaksi berhasil diambil",
  "data": {
    "merchantOrderId": "TOPUP-1-1750320000-a1b2c3d4-e5f6-7890",
    "amount": 100000,
    "status": "pending",
    "payment_method": "QRIS",
    "created_at": "2026-06-18T10:00:00.000000Z",
    "updated_at": "2026-06-18T10:05:00.000000Z"
  }
}
```

---

### 5. Riwayat Transaksi
```
GET /api/wallet/transactions
```
**Auth:** Required (Sanctum)

**Query Parameters:**
| Parameter | Default | Description |
|-----------|---------|-------------|
| page | 1 | Halaman |
| per_page | 15 | Item per halaman |

**Response Sukses:**
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
        "type": "topup",
        "amount": 100000,
        "status": "success",
        "payment_reference": "TOPUP-1-1750320000-a1b2c3d4",
        "payment_method": "QRIS",
        "description": "Top-up via DuitKu - Berhasil",
        "created_at": "2026-06-18T10:00:00.000000Z",
        "updated_at": "2026-06-18T10:05:00.000000Z"
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

---

## Diagram Alur Top-up

```
┌─────────┐                    ┌──────────────┐                    ┌──────────┐
│  User   │                    │   Backend    │                    │  DuitKu  │
└────┬────┘                    └──────┬───────┘                    └────┬─────┘
     │                                │                                  │
     │  1. POST /wallet/topup        │                                  │
     │     (amount, payment_method)  │                                  │
     │──────────────────────────────>│                                  │
     │                                │                                  │
     │                                │  2. Generate merchantOrderId    │
     │                                │     TOPUP-{user_id}-{timestamp} │
     │                                │                                  │
     │                                │  3. POST /v2/inquiry            │
     │                                │     (dengan signature MD5)      │
     │                                │────────────────────────────────>│
     │                                │                                  │
     │                                │  4. Response: paymentUrl/       │
     │                                │     vaNumber/qrString           │
     │                                │<────────────────────────────────│
     │                                │                                  │
     │  5. Response: payment data     │                                  │
     │<──────────────────────────────│                                  │
     │                                │                                  │
     │  6. User bayar via payment    │                                  │
     │     gateway DuitKu            │                                  │
     │                                │                                  │
     │                                │  7. POST /wallet/topup/callback  │
     │                                │     (resultCode, signature)      │
     │                                │<─────────────────────────────────│
     │                                │                                  │
     │                                │  8. Validate signature MD5       │
     │                                │                                  │
     │                                │  9. If resultCode='00':          │
     │                                │     - Update status='success'    │
     │                                │     - Increment wallet balance   │
     │                                │     Else:                        │
     │                                │     - Update status='failed'     │
     │                                │                                  │
     │                                │  10. Return "OK" to DuitKu      │
     │                                │────────────────────────────────>│
     │                                │                                  │
```

---

## Signature MD5 DuitKu

### Format Signature
```
signature = MD5(merchantCode + amount + merchantOrderId + apiKey)
```

### Contoh Perhitungan Signature

**Input:**
- `merchantCode` = `DEMO0123`
- `amount` = `100000`
- `merchantOrderId` = `TOPUP-1-1750320000-a1b2c3d4`
- `apiKey` = `5c8cecab747e57664d3aec9fb93a3e1e`

**Perhitungan:**
```
signature = MD5("DEMO0123" + "100000" + "TOPUP-1-1750320000-a1b2c3d4" + "5c8cecab747e57664d3aec9fb93a3e1e")
signature = MD5("DEMO0123100000TOPUP-1-1750320000-a1b2c3d45c8cecab747e57664d3aec9fb93a3e1e")
signature = "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
```

### Implementasi di Code
```php
// DuitkuService.php
public function generateSignature(string $merchantCode, int $amount, string $merchantOrderId, string $apiKey): string
{
    return md5($merchantCode . $amount . $merchantOrderId . $apiKey);
}

// Validasi callback
public function validateSignature(...): bool
{
    $expectedSignature = $this->generateSignature(...);
    return hash_equals($expectedSignature, $signature);
}
```

---

## Kode Payment Method DuitKu

| Kode | Metode Pembayaran |
|------|-------------------|
| `QRIS` | QRIS (semua e-wallet) |
| `VC` | Virtual Account BCA |
| `VA` | Virtual Account Bank Lain |
| `OV` | OVO |
| `DA` | DANA |
| `SP` | ShopeePay |
| `IC` | i.Saku |
| `LJ` | LinkAja |

**Catatan:** Kode di atas adalah contoh. Sesuaikan dengan dokumentasi resmi DuitKu untuk sandbox/production.

---

## Konfigurasi

### .env
```env
DUITKU_MERCHANT_CODE=DEMO0123
DUITKU_API_KEY=5c8cecab747e57664d3aec9fb93a3e1e
DUITKU_BASE_URL=https://sandbox.duitku.com/webapi/api/merchant
DUITKU_CALLBACK_URL=https://domain.com/api/wallet/topup/callback
DUITKU_RETURN_URL=https://domain.com/wallet/return
```

### config/duitku.php
```php
<?php

return [
    'merchant_code' => env('DUITKU_MERCHANT_CODE'),
    'api_key'       => env('DUITKU_API_KEY'),
    'base_url'      => env('DUITKU_BASE_URL', 'https://sandbox.duitku.com/webapi/api/merchant'),
    'callback_url'  => env('DUITKU_CALLBACK_URL'),
    'return_url'    => env('DUITKU_RETURN_URL'),
];
```

---

## Catatan Testing dengan Ngrok

### Langkah-langkah:

1. **Install Ngrok** (jika belum ada)
   ```bash
   # Download dari https://ngrok.com/download
   # Extract dan setup authtoken
   ```

2. **Jalankan Laravel Server**
   ```bash
   php artisan serve --port=8000
   ```

3. **Expose dengan Ngrok**
   ```bash
   ngrok http 8000
   ```

4. **Update .env**
   ```env
   DUITKU_CALLBACK_URL=https://abc123.ngrok-free.app/api/wallet/topup/callback
   DUITKU_RETURN_URL=https://abc123.ngrok-free.app/wallet/return
   ```

5. **Test Endpoint**
   ```bash
   # Request top-up
   curl -X POST https://abc123.ngrok-free.app/api/wallet/topup \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"amount": 100000, "payment_method": "QRIS"}'
   ```

6. **Cek Callback di Ngrok Terminal**
   - Ngrok akan menampilkan request callback dari DuitKu
   - Log response dari server

---

## Error Handling

### Response Error Umum

| HTTP Code | Success | Message |
|-----------|---------|---------|
| 400 | false | Validasi gagal |
| 401 | false | Unauthorized |
| 404 | false | Resource tidak ditemukan |
| 500 | false | Server error |

### Contoh Response Error
```json
{
  "success": false,
  "message": "Gagal membuat transaksi di DuitKu"
}
```

---

## Struktur Database

### Tabel: wallet_transactions (kolom baru)

| Kolom | Type | Default | Description |
|-------|------|---------|-------------|
| status | string | 'pending' | pending, success, failed |
| payment_reference | string | nullable | merchantOrderId dari DuitKu |
| payment_method | string | nullable | Kode metode pembayaran |

---

## Security Notes

1. **Jangan pernah** expose `DUITKU_API_KEY` di frontend
2. **Selalu validasi** signature MD5 dari callback
3. **Idempotency**: Cek status transaksi sebelum proses untuk menghindari duplicate
4. **HTTPS only** untuk production dengan callback URL yang valid
