# Admin Time Simulation & Auto-Return Overdue — SEAPEDIA (Level 6)

Fitur ini memungkinkan admin untuk mensimulasikan waktu sistem dan secara otomatis memproses pesanan yang overdue (lewat batas waktu pengiriman).

## Konsep Waktu Simulasi

### Prinsip Dasar

- `system_settings` menyimpan satu baris dengan `key = 'time_offset_hours'`, `value` = jumlah jam offset (string angka, contoh `'0'`, `'72'`).
- **"Waktu sekarang sistem"** = `now()->addHours((int) $offsetValue)`
- Default jika belum ada record di `system_settings`: offset = `0` (artinya waktu sistem = waktu asli).
- Admin bisa **menambah** offset (mempercepat waktu), bersifat kumulatif — bukan mengganti, tapi menjumlahkan ke offset yang sudah ada.
- Admin juga bisa **reset** offset kembali ke `0`.

### Helper Terpusat

Semua logic waktu simulasi dikonsentrasikan di `SystemTimeService`:

```php
// app/Services/SystemTimeService.php

public function getOffsetHours(): int    // Ambil offset saat ini (default 0)
public function now(): \Carbon\Carbon     // Waktu sekarang sistem (now() + offset)
public function advanceHours(int $hours): int  // Tambahkan offset, return offset baru
public function resetOffset(): void      // Reset offset ke 0
```

---

## Endpoint API

**Base URL:** `/api/admin`  
**Authentication:** `auth:sanctum` + `role:admin`

### 1. Lihat Status Waktu Simulasi

```
GET /api/admin/time-simulation
```

**Response Sukses:**
```json
{
  "success": true,
  "message": "Status waktu simulasi berhasil diambil",
  "data": {
    "offset_hours": 0,
    "real_time": "2026-06-20T12:00:00+07:00",
    "simulated_time": "2026-06-20T12:00:00+07:00",
    "is_simulating": false
  }
}
```

---

### 2. Majukan Waktu Simulasi

```
POST /api/admin/time-simulation/advance
```

**Request Body:**
```json
{
  "hours": 48
}
```

**Validasi:**
- `hours`: required, integer, min 1

**Response Sukses:**
```json
{
  "success": true,
  "message": "Waktu simulasi berhasil dimajukan",
  "data": {
    "added_hours": 48,
    "new_offset_hours": 48,
    "simulated_time": "2026-06-22T12:00:00+07:00"
  }
}
```

---

### 3. Reset Waktu Simulasi

```
POST /api/admin/time-simulation/reset
```

**Response Sukses:**
```json
{
  "success": true,
  "message": "Waktu simulasi berhasil direset",
  "data": {
    "offset_hours": 0,
    "real_time": "2026-06-20T12:00:00+07:00"
  }
}
```

---

### 4. Jalankan Pengecekan Overdue

```
POST /api/admin/orders/check-overdue
```

**Logic Pengecekan:**

1. Cari semua `deliveries` dengan:
   - `due_at < waktu_simulasi_sekarang` (menggunakan `SystemTimeService::now()`)
   - `status` masih `'taken'` ATAU `'available'` (sudah diambil driver atau belum)
   - Order terkait (`orders.status`) masih `'sedang_dikirim'` ATAU `'menunggu_pengirim'`

2. Untuk setiap delivery yang overdue, proses dalam `DB::transaction()`:
   - Update `orders.status` menjadi `'dikembalikan'`
   - Insert record ke `order_status_histories`
   - Refund penuh ke wallet buyer (`orders.total`)
   - Insert record ke `wallet_transactions` (type: `refund`)
   - Kembalikan stock produk sesuai `order_items`
   - Update `deliveries.status` menjadi `'cancelled'`

**Response Sukses (ada yang overdue):**
```json
{
  "success": true,
  "message": "Pengecekan overdue selesai",
  "data": {
    "processed_count": 2,
    "affected_orders": [15, 23]
  }
}
```

**Response Sukses (tidak ada yang overdue):**
```json
{
  "success": true,
  "message": "Tidak ada pesanan yang overdue saat ini",
  "data": {
    "processed_count": 0,
    "affected_orders": []
  }
}
```

---

## Diagram Alur Auto-Return Overdue

```
┌─────────────────────────────────────────────────────────────────┐
│                     ADMIN TIME SIMULATION                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐     ┌────────────────┐     ┌──────────────┐   │
│  │ GET /time-   │     │ POST /time-    │     │ POST /time-  │   │
│  │ simulation   │     │ simulation/    │     │ simulation/  │   │
│  │              │     │ advance        │     │ reset        │   │
│  │ Lihat offset │     │ {hours: 48}    │     │              │   │
│  │ & waktu      │     │ +48 jam ke     │     │ offset = 0   │   │
│  │ simulasi     │     │ offset         │     │              │   │
│  └──────────────┘     └────────────────┘     └──────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    OVERDUE CHECK FLOW                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  POST /orders/check-overdue                                      │
│           │                                                      │
│           ▼                                                      │
│  ┌─────────────────┐                                            │
│  │ SystemTimeService│                                           │
│  │ ::now()         │  ← Waktu simulasi (now + offset)          │
│  └────────┬────────┘                                            │
│           │                                                      │
│           ▼                                                      │
│  ┌─────────────────────────────────────────┐                   │
│  │ Query Deliveries:                       │                   │
│  │ - due_at < waktu_simulasi_sekarang      │                   │
│  │ - status IN ('available', 'taken')      │                   │
│  │ - order.status IN ('sedang_dikirim',    │                   │
│  │                   'menunggu_pengirim')   │                   │
│  └────────┬────────────────────────────────┘                   │
│           │                                                      │
│           ▼                                                      │
│  ┌─────────────────┐                                            │
│  │ Ada deliveries  │──NO──► "Tidak ada pesanan overdue"         │
│  │ overdue?        │                                            │
│  └────────┬────────┘                                            │
│           │YES                                                   │
│           ▼                                                      │
│  ┌─────────────────────────────────────────┐                   │
│  │ FOR EACH overdue delivery:              │                   │
│  │   DB::transaction()                    │                   │
│  │   ┌──────────────────────────────────┐ │                   │
│  │   │ 1. Update order → 'dikembalikan' │ │                   │
│  │   │ 2. Insert status history        │ │                   │
│  │   │ 3. Refund wallet buyer (+total)  │ │                   │
│  │   │ 4. Insert wallet transaction     │ │                   │
│  │   │ 5. Restore product stock         │ │                   │
│  │   │ 6. Update delivery → 'cancelled' │ │                   │
│  │   └──────────────────────────────────┘ │                   │
│  └────────┬────────────────────────────────┘                   │
│           │                                                      │
│           ▼                                                      │
│  ┌─────────────────┐                                            │
│  │ Return response │                                           │
│  │ {processed,     │                                           │
│  │  affected_ids}   │                                           │
│  └─────────────────┘                                            │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Skenario Testing Lengkap

### Skenario: Order Overdue (Delivery Belum Diambil Driver)

**Setup Awal:**
1. Buyer checkout order dengan `delivery_method = 'instant'`
   - `due_at` = sekarang + 3 jam
   - Order status = `'sedang_dikirim'`
   - Delivery status = `'available'` (belum diambil driver)

2. Verifikasi initial state:
```
GET /api/admin/orders/check-overdue
Response: processed_count = 0 (belum overdue)
```

**Langkah Testing:**

1. **Majukan waktu 4 jam:**
   ```
   POST /api/admin/time-simulation/advance
   Body: { "hours": 4 }
   
   Response:
   {
     "success": true,
     "data": {
       "added_hours": 4,
       "new_offset_hours": 4
     }
   }
   ```

2. **Trigger check-overdue:**
   ```
   POST /api/admin/orders/check-overdue
   
   Response:
   {
     "success": true,
     "message": "Pengecekan overdue selesai",
     "data": {
       "processed_count": 1,
       "affected_orders": [ORDER_ID]
     }
   }
   ```

3. **Verifikasi perubahan:**

   - **Order status:**
     ```
     GET /api/orders/{ORDER_ID}
     → status: "dikembalikan"
     ```

   - **Order status history:**
     ```
     GET /api/orders/{ORDER_ID}/status-history
     → [{ status: "dikembalikan", note: "Pesanan otomatis dikembalikan karena melebihi batas waktu pengiriman" }]
     ```

   - **Wallet buyer (saldo bertambah):**
     ```
     GET /api/wallet
     → balance: OLD_BALANCE + ORDER_TOTAL
     ```

   - **Wallet transaction:**
     ```
     GET /api/wallet/transactions
     → [{ type: "refund", amount: ORDER_TOTAL, description: "Refund otomatis - pesanan dikembalikan karena overdue" }]
     ```

   - **Product stock (kembali):**
     ```
     GET /api/products/{PRODUCT_ID}
     → stock: ORIGINAL_STOCK + QUANTITY_ORDERED
     ```

   - **Delivery status:**
     ```
     GET /api/driver/jobs/{DELIVERY_ID}
     → status: "cancelled"
     ```

---

### Skenario: Order Overdue (Delivery Sudah Diambil Driver)

**Setup Awal:**
1. Buyer checkout order
2. Driver ambil order → delivery status `'taken'`
3. Driver belum selesaikan delivery hingga `due_at` terlewat

**Langkah Testing:**
1. Admin majukan waktu melewati `due_at`
2. Trigger check-overdue
3. Verifikasi: refund + cancel delivery (walaupun driver sudah ambil)

---

### Skenario: Order Tidak Overdue

**Setup Awal:**
1. Buyer checkout order dengan `due_at = sekarang + 5 jam`

**Langkah Testing:**

1. Majukan waktu hanya 3 jam:
   ```
   POST /api/admin/time-simulation/advance
   Body: { "hours": 3 }
   ```

2. Trigger check-overdue:
   ```
   POST /api/admin/orders/check-overdue
   
   Response:
   {
     "success": true,
     "message": "Tidak ada pesanan yang overdue saat ini",
     "data": {
       "processed_count": 0,
       "affected_orders": []
     }
   }
   ```

3. Verifikasi order tetap normal (tidak berubah status)

---

### Skenario: Kumulatif Offset

**Setup Awal:**
- Offset saat ini = 0

**Langkah Testing:**

1. Majukan 24 jam:
   ```
   POST /api/admin/time-simulation/advance
   Body: { "hours": 24 }
   → new_offset: 24
   ```

2. Majukan lagi 48 jam:
   ```
   POST /api/admin/time-simulation/advance
   Body: { "hours": 48 }
   → new_offset: 72 (24 + 48, BUKAN 48)
   ```

3. Reset:
   ```
   POST /api/admin/time-simulation/reset
   → offset: 0
   ```

---

## Struktur File

```
app/
├── Models/
│   └── SystemSetting.php          # Model untuk system_settings
├── Services/
│   └── SystemTimeService.php      # Logic waktu simulasi terpusat
└── Http/Controllers/Api/Admin/
    ├── TimeSimulationController.php   # GET show, POST advance, POST reset
    └── OrderOverdueController.php     # POST checkOverdue

database/migrations/
└── 2026_06_20_000001_create_system_settings_table.php

routes/api.php
└── (route admin sudah include time-simulation & check-overdue)

docs/L6/
└── admin-time-simulation-overdue.md   # Dokumentasi ini
```

---

## Catatan Penting

1. **TIDAK menggunakan cron/scheduler** — pengecekan overdue murni manual trigger lewat endpoint.
2. **WAJIB gunakan `SystemTimeService::now()`** untuk pengecekan overdue, bukan `now()` biasa.
3. **Order yang sudah `'pesanan_selesai'` atau `'dikembalikan'` TIDAK akan diproses ulang.**
4. **Refund bersifat FULL** — seluruh `orders.total` dikembalikan ke wallet buyer.
5. **Stock produk dikembalikan** sesuai quantity di `order_items`.
6. **Semua proses dalam transaction** — jika satu order gagal, order lain tidak受影响.
