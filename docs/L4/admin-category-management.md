# Dokumentasi: Admin Category Management — SEAPEDIA

## Overview

Fitur Admin Category Management memungkinkan admin untuk membuat, melihat, mengupdate, dan menghapus kategori produk. Kategori digunakan untuk mengorganisir produk di marketplace.

## Endpoint

### 1. List Semua Kategori

**Endpoint:** `GET /api/admin/categories`

**Middleware:** `auth:sanctum`, `role:admin`

**Contoh Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Makanan",
      "slug": "makanan",
      "icon": "🍔",
      "is_active": true,
      "created_at": "2026-06-25T09:00:00+07:00",
      "updated_at": "2026-06-25T09:00:00+07:00"
    }
  ]
}
```

---

### 2. Create Kategori

**Endpoint:** `POST /api/admin/categories`

**Middleware:** `auth:sanctum`, `role:admin`

**Request Body:**
```json
{
  "name": "Minuman",
  "icon": "🥤",
  "is_active": true
}
```

**Validation Rules:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | max 100 characters |
| icon | string | No | max 50 characters |
| is_active | boolean | No | default: true |

**Response 201:**
```json
{
  "success": true,
  "message": "Kategori berhasil dibuat",
  "data": {
    "id": 2,
    "name": "Minuman",
    "slug": "minuman",
    "icon": "🥤",
    "is_active": true,
    "created_at": "2026-06-25T09:00:00+07:00",
    "updated_at": "2026-06-25T09:00:00+07:00"
  }
}
```

**Catatan:** Slug di-generate otomatis dari name menggunakan `Str::slug()`. Jika slug sudah ada, akan ditambahkan timestamp.

---

### 3. Get Kategori Detail

**Endpoint:** `GET /api/admin/categories/{id}`

**Middleware:** `auth:sanctum`, `role:admin`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Makanan",
    "slug": "makanan",
    "icon": "🍔",
    "is_active": true,
    "created_at": "2026-06-25T09:00:00+07:00",
    "updated_at": "2026-06-25T09:00:00+07:00"
  }
}
```

**Response 404:**
```json
{
  "success": false,
  "message": "Kategori tidak ditemukan"
}
```

---

### 4. Update Kategori

**Endpoint:** `PUT /api/admin/categories/{id}`

**Middleware:** `auth:sanctum`, `role:admin`

**Request Body (semua field opsional):**
```json
{
  "name": "Makanan Laut",
  "icon": "🐟",
  "is_active": true
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Kategori berhasil diperbarui",
  "data": {
    "id": 1,
    "name": "Makanan Laut",
    "slug": "makanan-laut",
    "icon": "🐟",
    "is_active": true,
    "created_at": "2026-06-25T09:00:00+07:00",
    "updated_at": "2026-06-25T10:00:00+07:00"
  }
}
```

**Catatan:** Jika name diubah, slug akan di-regenerate. Slug unik dicek dengan pengecualian kategori itu sendiri.

---

### 5. Delete Kategori

**Endpoint:** `DELETE /api/admin/categories/{id}`

**Middleware:** `auth:sanctum`, `role:admin`

**Response 200:**
```json
{
  "success": true,
  "message": "Kategori berhasil dihapus"
}
```

**Response 400 (kategori masih punya produk):**
```json
{
  "success": false,
  "message": "Kategori tidak dapat dihapus karena masih memiliki produk"
}
```

**Catatan:** Kategori hanya bisa dihapus jika tidak memiliki produk. Jika masih punya produk, harus hapus/edit produk tersebut terlebih dahulu.

---

## Struktur Database

### Tabel: categories

| Kolom | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| name | varchar(100) | Nama kategori |
| slug | varchar(100) | URL-friendly name (unique) |
| icon | varchar(50) | Emoji/icon untuk display |
| is_active | boolean | Status aktif (default: true) |
| created_at | timestamp | |
| updated_at | timestamp |

---

## Model Category

```php
// app/Models/Category.php

public function products()
{
    return $this->hasMany(Product::class);
}

public function scopeActive($query)
{
    return $query->where('is_active', true);
}
```

---

## Relasi dengan Produk

- Setiap `Product` memiliki `category_id` yang mereferensikan `categories`
- Endpoint public `GET /api/categories` hanya mengembalikan kategori dengan `is_active = true`
- Admin bisa melihat semua kategori termasuk yang tidak aktif

---

## Catatan Pengembangan

1. **Slug uniqueness**: Jika slug sudah ada, akan ditambahkan suffix timestamp
2. **Soft delete**: Untuk saat ini tidak ada soft delete, kategori dihapus permanen
3. **Produk reference**: Cek relasi produk sebelum hapus untuk menjaga data integrity
4. **Icon**: Menggunakan emoji atau shortcode untuk icon, fleksibel untuk frontend render
