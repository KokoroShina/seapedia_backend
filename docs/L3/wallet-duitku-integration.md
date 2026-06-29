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
  "payment_method": "BCAVA"
}
```

**Response Sukses:**
```json
{
  "success": true,
  "message": "Transaksi top-up berhasil dibuat",
  "data": {
    "merchantOrderId": "T1-1750320000-a1b2c3",
    "reference": "REF123456",
    "amount": 100000,
    "status": "pending",
    "paymentUrl": "https://sandbox.duitku.com/pay/xxx",
    "vaNumber": "12345678901",
    "qrString": null
  }
}
```

---

### 3. Get Payment Methods (Public)
```
GET /api/payment-methods
```
**Auth:** None (Public endpoint)

**Deskripsi:** Mengambil daftar metode pembayaran yang tersedia dari DuitKu

**Response Sukses:**
```json
{
  "success": true,
  "message": "Payment methods berhasil diambil",
  "data": [
    {
      "code": "VA",
      "name": "MAYBANK VA",
      "image": "https://images.duitku.com/hotlink-ok/VA.PNG",
      "fee": 0
    },
    {
      "code": "BT",
      "name": "PERMATA VA",
      "image": "https://images.duitku.com/hotlink-ok/PERMATA.PNG",
      "fee": 0
    },
    {
      "code": "VC",
      "name": "BCA Virtual Account",
      "image": "https://images.duitku.com/hotlink-ok/BCA.PNG",
      "fee": 0
    },
    {
      "code": "NC",
      "name": "CIMB Niaga Virtual Account",
      "image": "https://images.duitku.com/hotlink-ok/CIMB.PNG",
      "fee": 0
    },
    {
      "code": "BNI",
      "name": "BNI Virtual Account",
      "image": "https://images.duitku.com/hotlink-ok/BNI.PNG",
      "fee": 0
    },
    {
      "code": "BR",
      "name": "BRI Virtual Account",
      "image": "https://images.duitku.com/hotlink-ok/BRI.PNG",
      "fee": 0
    },
    {
      "code": "M2",
      "name": "Mandiri Bill Payment",
      "image": "https://images.duitku.com/hotlink-ok/MANDIRI.PNG",
      "fee": 0
    },
    {
      "code": "I1",
      "name": "BNI Ib Banking",
      "image": "https://images.duitku.com/hotlink-ok/BNI.PNG",
      "fee": 0
    },
    {
      "code": "A1",
      "name": "ATMA",
      "image": "https://images.duitku.com/hotlink-ok/ATMA.PNG",
      "fee": 0
    },
    {
      "code": "S1",
      "name": "SAHABAT SAMPOERNA",
      "image": "https://images.duitku.com/hotlink-ok/SAMPOERNA.PNG",
      "fee": 0
    },
    {
      "code": "FT",
      "name": "FINTTY",
      "image": "https://images.duitku.com/hotlink-ok/FINTTY.PNG",
      "fee": 0
    },
    {
      "code": "D1",
      "name": "DOKU",
      "image": "https://images.duitku.com/hotlink-ok/DOKU.PNG",
      "fee": 0
    },
    {
      "code": "QJ",
      "name": "QRIS",
      "image": "https://images.duitku.com/hotlink-ok/QRIS.PNG",
      "fee": 0
    },
    {
      "code": "DA",
      "name": "DANA",
      "image": "https://images.duitku.com/hotlink-ok/DANA.PNG",
      "fee": 0
    },
    {
      "code": "SP",
      "name": "SHOPEEPAY",
      "image": "https://images.duitku.com/hotlink-ok/SP.PNG",
      "fee": 0
    },
    {
      "code": "OV",
      "name": "OVO",
      "image": "https://images.duitku.com/hotlink-ok/OVO.PNG",
      "fee": 0
    },
    {
      "code": "LINK",
      "name": "LINKAJA",
      "image": "https://images.duitku.com/hotlink-ok/LINK.PNG",
      "fee": 0
    },
    {
      "code": "IC",
      "name": "INDOMARET",
      "image": "https://images.duitku.com/hotlink-ok/INDOMARET.PNG",
      "fee": 0
    },
    {
      "code": "A2",
      "name": "ALFAMART",
      "image": "https://images.duitku.com/hotlink-ok/ALFAMART.PNG",
      "fee": 0
    },
    {
      "code": "BC",
      "name": "CREDIT CARD",
      "image": "https://images.duitku.com/hotlink-ok/CREDIT.PNG",
      "fee": 0
    }
  ]
}
```

---

### 4. Check Transaction Status dari DuitKu (Public)
```
GET /api/wallet/check-status/{merchantOrderId}
```
**Auth:** None (Public endpoint)

**Deskripsi:** Mengecek status transaksi langsung dari DuitKu

**Response Sukses:**
```json
{
  "success": true,
  "message": "Status transaksi berhasil diambil",
  "data": {
    "merchantOrderId": "T1-1750320000-a1b2c3",
    "reference": "REF123456",
    "amount": 100000,
    "fee": 0,
    "statusCode": "00",
    "statusMessage": "SUCCESS"
  }
}
```

**Status Code DuitKu:**
| Code | Description |
|------|-------------|
| 00 | SUCCESS - Pembayaran berhasil |
| 01 | PENDING - Menunggu pembayaran |
| 02 | FAILED - Pembayaran gagal |
| 03 | EXPIRED - Pembayaran kadaluarsa |

---

### 5. Callback DuitKu (Public)
```
POST /api/wallet/topup/callback
```
**Auth:** None (Public endpoint untuk menerima callback dari DuitKu)

**Deskripsi:** Endpoint ini dipanggil oleh DuitKu setelah user menyelesaikan pembayaran. Endpoint ini:

1. **Validasi signature HMAC-SHA256** untuk memastikan callback benar-benar dari DuitKu
2. **Cek idempotency** — jika transaksi sudah diproses, return OK tanpa proses ulang
3. **Update status transaksi** berdasarkan resultCode
4. **Tambahkan saldo wallet** jika resultCode = '00' (sukses)
5. **Log semua callback** untuk debugging

**Request dari DuitKu:**
| Parameter | Type | Description |
|-----------|------|-------------|
| merchantCode | string | Kode merchant |
| amount | integer | Nominal transaksi |
| merchantOrderId | string | Order ID unik |
| signature | string | HMAC-SHA256 signature untuk validasi |
| resultCode | string | Kode hasil ('00' = sukses) |
| reference | string | Reference dari payment gateway |
| paymentCode | string | Kode metode pembayaran |
| productDetail | string | Detail produk |
| additionalParam | string | Parameter tambahan |
| merchantUserId | string | ID user di merchant |
| publisherOrderId | string | Order ID dari payment gateway |
| spUserHash | string | User hash (untuk ShopeePay) |
| settlementDate | string | Tanggal settlement |
| issuerCode | string | Kode issuer bank |

**Response:**
- `OK` - Berhasil diproses
- `SIGNATURE_VALIDATION_FAILED` - Signature tidak valid (HTTP 400)
- `ORDER_NOT_FOUND` - Transaksi tidak ditemukan

**Catatan Keamanan:**
- Signature validation WAJIB dilakukan sebelum memproses callback
- Idempotency check memastikan satu transaksi hanya diproses sekali
- Semua callback di-log untuk audit trail

---

### 6. Cek Status Transaksi (User-specific)
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
    "merchantOrderId": "T1-1750320000-a1b2c3",
    "amount": 100000,
    "status": "pending",
    "payment_method": "BCAVA",
    "created_at": "2026-06-18T10:00:00.000000Z",
    "updated_at": "2026-06-18T10:05:00.000000Z"
  }
}
```

---

### 7. Riwayat Transaksi
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
        "payment_reference": "T1-1750320000-a1b2c3",
        "payment_method": "BCAVA",
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
     │  1. GET /payment-methods      │                                  │
     │<──────────────────────────────│                                  │
     │                                │                                  │
     │  2. POST /wallet/topup        │                                  │
     │     (amount, payment_method)  │                                  │
     │──────────────────────────────>│                                  │
     │                                │                                  │
     │                                │  3. Generate merchantOrderId    │
     │                                │     T{user_id}{timestamp}{rand} │
     │                                │                                  │
     │                                │  4. POST /v2/inquiry            │
     │                                │     (signature: HMAC-SHA256)    │
     │                                │────────────────────────────────>│
     │                                │                                  │
     │                                │  5. Response: paymentUrl/       │
     │                                │     vaNumber/qrString           │
     │                                │<────────────────────────────────│
     │                                │                                  │
     │  6. Response: payment data     │                                  │
     │<──────────────────────────────│                                  │
     │                                │                                  │
     │  7. User bayar via payment    │                                  │
     │     gateway DuitKu            │                                  │
     │                                │                                  │
     │                                │  8. POST /wallet/topup/callback  │
     │                                │     (resultCode, signature)      │
     │                                │<─────────────────────────────────│
     │                                │                                  │
     │                                │  9. Validate signature           │
     │                                │     HMAC-SHA256                  │
     │                                │                                  │
     │                                │  10. If resultCode='00':        │
     │                                │     - Update status='success'   │
     │                                │     - Increment wallet balance   │
     │                                │     Else:                       │
     │                                │     - Update status='failed'    │
     │                                │                                  │
     │                                │  11. Return "OK" to DuitKu     │
     │                                │────────────────────────────────>│
     │                                │                                  │
```

---

## Signature HMAC-SHA256 DuitKu

### Signature untuk Create Transaction (v2/inquiry)
```
signature = HMAC-SHA256(merchantCode + merchantOrderId + paymentAmount, apiKey)
```

### Signature untuk Get Payment Methods
```
signature = HMAC-SHA256(merchantCode + amount + datetime, apiKey)
```

### Signature untuk Check Transaction Status
```
signature = HMAC-SHA256(merchantCode + merchantOrderId, apiKey)
```

### Signature untuk Callback Validation
```
signature = HMAC-SHA256(merchantCode + amount + merchantOrderId, apiKey)
```

### Contoh Perhitungan Signature (Create Transaction)

**Input:**
- `merchantCode` = `DEMO0123`
- `merchantOrderId` = `T1-1750320000-a1b2c3`
- `paymentAmount` = `100000`
- `apiKey` = `5c8cecab747e57664d3aec9fb93a3e1e`

**Perhitungan:**
```
stringToSign = "DEMO0123" + "T1-1750320000-a1b2c3" + "100000"
stringToSign = "DEMO0123T1-1750320000-a1b2c3100000"

signature = hash_hmac('sha256', stringToSign, apiKey)
signature = "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2"
```

### Implementasi di Code
```php
// DuitkuService.php

// Create Transaction
public function generateSignature(string $merchantCode, int $amount, string $merchantOrderId, string $apiKey): string
{
    $stringToSign = $merchantCode . $merchantOrderId . $amount;
    return hash_hmac('sha256', $stringToSign, $apiKey);
}

// Get Payment Methods
public function generatePaymentMethodSignature(string $merchantCode, int $amount, string $datetime, string $apiKey): string
{
    $stringToSign = $merchantCode . $amount . $datetime;
    return hash_hmac('sha256', $stringToSign, $apiKey);
}

// Check Transaction Status
public function generateStatusSignature(string $merchantCode, string $merchantOrderId, string $apiKey): string
{
    $stringToSign = $merchantCode . $merchantOrderId;
    return hash_hmac('sha256', $stringToSign, $apiKey);
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
| VA | Maybank Virtual Account |
| BT | Permata Virtual Account |
| VC | BCA Virtual Account |
| NC | CIMB Niaga Virtual Account |
| BNI | BNI Virtual Account |
| BR | BRI Virtual Account |
| M2 | Mandiri Bill Payment |
| I1 | BNI Ib Banking |
| A1 | ATMA |
| S1 | Sahabat Sampoerna |
| FT | Fintty |
| D1 | DOKU |
| QJ | QRIS |
| DA | DANA |
| SP | ShopeePay |
| OV | OVO |
| LINK | LinkAja |
| IC | Indomaret |
| A2 | Alfamart |
| BC | Credit Card |

**Catatan:** Kode di atas berdasarkan response dari endpoint Get Payment Methods.

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
   # Get payment methods
   curl https://localhost:8000/api/payment-methods

   # Request top-up
   curl -X POST https://localhost:8000/api/wallet/topup \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"amount": 100000, "payment_method": "BCAVA"}'

   # Check status
   curl https://localhost:8000/api/wallet/check-status/T1-1750320000-a1b2c3
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

### Tabel: wallets

| Kolom | Type | Default | Description |
|-------|------|---------|-------------|
| id | bigint | PK | Primary key |
| user_id | bigint | unique | User ID |
| balance | decimal(15,2) | 0 | Saldo wallet |
| created_at | timestamp | | Waktu dibuat |
| updated_at | timestamp | | Waktu diupdate |

### Tabel: wallet_transactions

| Kolom | Type | Default | Description |
|-------|------|---------|-------------|
| id | bigint | PK | Primary key |
| wallet_id | bigint | FK | Reference ke wallets |
| type | enum | | topup, withdrawal, payment, refund |
| amount | decimal(15,2) | | Nominal transaksi |
| status | string | pending | pending, success, failed |
| payment_reference | string | nullable | merchantOrderId dari DuitKu |
| payment_method | string | nullable | Kode metode pembayaran |
| description | string | nullable | Deskripsi transaksi |
| created_at | timestamp | | Waktu dibuat |
| updated_at | timestamp | | Waktu diupdate |

---

## Security Notes

1. **Jangan pernah** expose `DUITKU_API_KEY` di frontend
2. **Selalu validasi** signature HMAC-SHA256 dari callback
3. **Idempotency**: Cek status transaksi sebelum proses untuk menghindari duplicate
4. **HTTPS only** untuk production dengan callback URL yang valid
5. **Validate resultCode**: Hanya resultCode='00' yang berarti sukses
