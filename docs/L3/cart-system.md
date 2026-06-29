# Dokumentasi Cart System - SEAPEDIA

## Daftar Endpoint

### 1. Lihat Isi Cart
```
GET /api/cart
```
**Auth:** Required (Sanctum)

**Response Sukses:**
```json
{
  "success": true,
  "message": "Cart berhasil diambil",
  "data": {
    "store": {
      "id": 1,
      "name": "Toko Elektronik ABC"
    },
    "items": [
      {
        "id": 1,
        "product_id": 5,
        "name": "Laptop ASUS",
        "price": 15000000.00,
        "quantity": 1,
        "subtotal": 15000000.00
      }
    ],
    "total": 15000000.00
  }
}
```

**Response Cart Kosong:**
```json
{
  "success": true,
  "message": "Cart kosong",
  "data": null
}
```

---

### 2. Tambah Produk ke Cart
```
POST /api/cart/items
```
**Auth:** Required (Sanctum)

**Request Body:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| product_id | integer | Yes | ID produk dari tabel products |
| quantity | integer | Yes | Jumlah (minimal 1) |

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Produk berhasil ditambahkan ke cart"
}
```

**Response Error - Store Conflict (422):**
```json
{
  "success": false,
  "message": "Cart Anda berisi produk dari toko lain. Selesaikan atau kosongkan cart terlebih dahulu."
}
```

---

### 3. Update Quantity Item
```
PUT /api/cart/items/{itemId}
```
**Auth:** Required (Sanctum)

**Request Body:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| quantity | integer | Yes | Jumlah baru (minimal 1) |

**Response Sukses:**
```json
{
  "success": true,
  "message": "Quantity berhasil diperbarui",
  "data": {
    "id": 1,
    "cart_id": 1,
    "product_id": 5,
    "quantity": 3,
    "updated_at": "2026-06-18T10:00:00.000000Z"
  }
}
```

**Response Error - Item Tidak Ditemukan (404):**
```json
{
  "success": false,
  "message": "Item tidak ditemukan di cart Anda"
}
```

---

### 4. Hapus Item dari Cart
```
DELETE /api/cart/items/{itemId}
```
**Auth:** Required (Sanctum)

**Response Sukses:**
```json
{
  "success": true,
  "message": "Item berhasil dihapus dari cart"
}
```

**Response Error - Item Tidak Ditemukan (404):**
```json
{
  "success": false,
  "message": "Item tidak ditemukan di cart Anda"
}
```

---

## Logika Single-Store Rule

Setiap cart hanya boleh berisi produk dari **satu toko**. Aturan:

1. **Cart baru**: Jika user belum punya cart, buat cart baru dengan `store_id` dari produk yang ditambahkan.

2. **Cart kosong**: Jika cart sudah ada tapi tidak ada item, `store_id` boleh di-update mengikuti produk baru.

3. **Store conflict**: Jika cart sudah ada dan berisi item dari toko lain → **ditolak dengan error 422**.

4. **Store match**: Jika produk yang ditambahkan berasal dari toko yang sama dengan cart, produk ditambahkan.

---

## Logika Merge Quantity

Jika produk yang sama (`product_id` sama) ditambahkan lagi ke cart:

- **Quantity di-merge**: quantity lama + quantity baru
- **Bukan membuat baris baru** di `cart_items`

**Contoh:**
```
Cart awal: Produk A (quantity = 2)
User tambahkan Produk A (quantity = 3)
Result: Produk A (quantity = 5)
```

---

## Struktur Database

### Tabel: carts
| Kolom | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key ke users |
| store_id | bigint | Foreign key ke stores (nullable) |
| created_at | timestamp | |
| updated_at | timestamp | |

### Tabel: cart_items
| Kolom | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| cart_id | bigint | Foreign key ke carts |
| product_id | bigint | Foreign key ke products |
| quantity | integer | Jumlah item |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## Model Relationships

### Cart Model (`app/Models/Cart.php`)
```php
public function items()
{
    return $this->hasMany(CartItem::class);
}

public function store()
{
    return $this->belongsTo(Store::class);
}
```

### CartItem Model (`app/Models/CartItem.php`)
```php
public function product()
{
    return $this->belongsTo(Product::class);
}
```

---

## Batasan

- Minimum quantity adalah 1
- Produk harus exists di tabel `products`
- Item hanya bisa diakses/diedit/dihapus oleh pemilik cart
