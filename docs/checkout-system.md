# Dokumentasi Checkout System - SEAPEDIA

## Endpoint Checkout

```
POST /api/checkout
```
**Auth:** Required (Sanctum)

---

## Request

**Request Body:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| address_id | integer | Yes | ID alamat dari tabel addresses |
| delivery_method | string | Yes | Metode pengiriman |

**Contoh Request:**
```json
{
  "address_id": 1,
  "delivery_method": "instant"
}
```

---

## Response

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
    "voucher_id": null,
    "promo_id": null,
    "delivery_method": "instant",
    "subtotal": 15000000.00,
    "discount_amount": 0.00,
    "delivery_fee": 20000.00,
    "ppn": 1800000.00,
    "total": 16820000.00,
    "status": "sedang_dikemas",
    "items": [
      {
        "id": 1,
        "order_id": 1,
        "product_id": 5,
        "product_name": "Laptop ASUS",
        "product_price": 15000000.00,
        "quantity": 1,
        "subtotal": 15000000.00
      }
    ],
    "status_histories": [
      {
        "id": 1,
        "order_id": 1,
        "status": "sedang_dikemas",
        "note": "Pesanan berhasil dibuat"
      }
    ],
    "address": {
      "id": 1,
      "user_id": 1,
      "label": "Rumah",
      "address": "Jl. Sudirman No. 123",
      "is_default": true
    },
    "store": {
      "id": 1,
      "name": "Toko Elektronik ABC"
    }
  }
}
```

---

## Skenario Error

### 1. Cart Kosong (400)
```json
{
  "success": false,
  "message": "Cart kosong"
}
```

### 2. Alamat Tidak Valid (404)
```json
{
  "success": false,
  "message": "Alamat tidak ditemukan"
}
```

### 3. Stok Tidak Mencukupi (422)
```json
{
  "success": false,
  "message": "Stok Laptop ASUS tidak mencukupi. Sisa stok: 0"
}
```

### 4. Saldo Wallet Tidak Mencukupi (422)
```json
{
  "success": false,
  "message": "Saldo wallet tidak mencukupi"
}
```

---

## Delivery Fee

| Metode | Biaya |
|--------|-------|
| instant | Rp 20.000 |
| next_day | Rp 12.000 |
| regular | Rp 8.000 |

---

## Rumus Perhitungan

```
PPN = 12% × subtotal
Total = subtotal + delivery_fee + ppn - discount_amount
```

**Catatan:** Untuk saat ini, `discount_amount` selalu 0 karena fitur voucher/promo belum diimplementasikan.

---

## Alur Checkout Step-by-Step

1. **Validasi Input**
   - `address_id` harus exists dan milik user login
   - `delivery_method` harus salah satu dari: `instant`, `next_day`, `regular`

2. **Validasi Cart**
   - Cart tidak boleh kosong
   - Ambil data cart beserta item dan relasi product

3. **Validasi Stok (ALL-OR-NOTHING)**
   - Cek stock setiap produk di cart
   - Jika ada satu produk pun yang stock kurang → seluruh checkout dibatalkan

4. **Hitung Subtotal**
   - Sum dari `(product_price × quantity)` untuk setiap item

5. **Hitung Total**
   - subtotal + delivery_fee + ppn - discount_amount

6. **Validasi Saldo Wallet**
   - Saldo wallet user harus >= total
   - Jika kurang → checkout ditolak

7. **Proses dalam DB Transaction**
   - Buat record `orders`
   - Buat record `order_items` dengan snapshot data produk
   - Kurangi stock produk
   - Buat record `order_status_histories`
   - Kurangi saldo wallet
   - Buat record `wallet_transactions`
   - Hapus cart_items dan cart

8. **Return Response**
   - Return data order lengkap dengan relasi items, status_histories, address, store

---

## Struktur Database

### Tabel: orders
| Kolom | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| buyer_id | bigint | Foreign key ke users |
| store_id | bigint | Foreign key ke stores |
| address_id | bigint | Foreign key ke addresses |
| voucher_id | bigint | Nullable |
| promo_id | bigint | Nullable |
| delivery_method | string | instant/next_day/regular |
| subtotal | decimal | Subtotal pesanan |
| discount_amount | decimal | Discount (default 0) |
| delivery_fee | decimal | Biaya pengiriman |
| ppn | decimal | PPN 12% |
| total | decimal | Total pembayaran |
| status | string | Status pesanan |
| created_at | timestamp | |
| updated_at | timestamp | |

### Tabel: order_items (tanpa timestamps)
| Kolom | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| order_id | bigint | Foreign key ke orders |
| product_id | bigint | Foreign key ke products |
| product_name | string | Snapshot nama produk |
| product_price | decimal | Snapshot harga produk |
| quantity | integer | Jumlah item |
| subtotal | decimal | Subtotal item |

### Tabel: order_status_histories
| Kolom | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| order_id | bigint | Foreign key ke orders |
| status | string | Status pesanan |
| note | text | Catatan |
| created_at | timestamp | |

---

## Model Relationships

### Order Model
```php
public function items()
{
    return $this->hasMany(OrderItem::class);
}

public function statusHistories()
{
    return $this->hasMany(OrderStatusHistory::class);
}

public function address()
{
    return $this->belongsTo(Address::class);
}

public function store()
{
    return $this->belongsTo(Store::class);
}
```

### OrderItem Model
```php
public $timestamps = false;

public function order()
{
    return $this->belongsTo(Order::class);
}

public function product()
{
    return $this->belongsTo(Product::class);
}
```

### OrderStatusHistory Model
```php
public function order()
{
    return $this->belongsTo(Order::class);
}
```

---

## Snapshot Data Produk

Data produk di `order_items` adalah **snapshot** (salinan) dari data produk SAAT checkout:

- `product_name`: nama produk saat itu
- `product_price`: harga produk saat itu
- `subtotal`: `product_price × quantity`

Ini memastikan data order tidak berubah jika produk diedit/dihapus setelah checkout.

---

## Batasan

- Checkout hanya bisa dilakukan jika cart tidak kosong
- Semua item dalam cart harus berasal dari toko yang sama
- Stok produk dicek ALL-OR-NOTHING: jika satu produk pun stock kurang, checkout gagal
- Saldo wallet harus mencukupi total pembayaran
- Fitur voucher/promo belum diimplementasikan (selalu null/0)
- Setelah checkout sukses, cart akan dikosongkan
