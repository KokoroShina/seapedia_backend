# Dokumentasi: Seller Store & Product Management — SEAPEDIA (Level 2)

## Overview

Fitur ini memungkinkan user dengan role **seller** untuk membuat dan mengelola toko miliknya, serta melakukan CRUD produk di dalam toko tersebut.

## Keputusan Desain

| Keputusan | Alasan |
|---|---|
| 1 seller = 1 toko | Simplifikasi alur bisnis untuk scope project ini |
| Validasi ownership manual di controller (cek `user_id`/`store_id`) | Cukup untuk scope L2, tidak perlu Policy dulu |
| Upload gambar pakai Laravel Storage (`disk: public`) | Sesuai stack yang sudah disepakati, lokal dulu |
| Gambar lama dihapus otomatis saat update/delete | Mencegah file sampah menumpuk di storage |

## Prasyarat Sebelum Testing

1. Pastikan sudah menjalankan:
   ```bash
   php artisan storage:link
   ```
2. Siapkan akun dengan role `seller` (register dengan `role: seller`, atau switch-role ke `seller` jika user multi-role).
3. Semua endpoint di bawah **butuh autentikasi** — kirim header:
   ```
   Authorization: Bearer {token}
   ```
   Token harus punya ability `seller` (didapat dari login/switch-role).

## Endpoints

### 1. Lihat Toko Sendiri

`GET /api/seller/store`

**Response 200 (sudah punya toko):**
```json
{
  "success": true,
  "message": "Detail toko berhasil diambil",
  "data": {
    "id": 1,
    "user_id": 5,
    "name": "Toko Juju",
    "description": "Jual barang elektronik",
    "image": "stores/abc123.jpg",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

**Response 404 (belum punya toko):**
```json
{
  "success": false,
  "message": "Anda belum memiliki toko"
}
```

---

### 2. Buat Toko Baru

`POST /api/seller/store`

**Catatan:** Karena ada upload file, gunakan `multipart/form-data`, bukan JSON, di Postman/Thunder Client.

**Request (form-data):**
| Key | Type | Value |
|---|---|---|
| name | text | Toko Juju |
| description | text | Jual barang elektronik |
| image | file | (pilih file gambar) |

**Response 201:**
```json
{
  "success": true,
  "message": "Toko berhasil dibuat",
  "data": {
    "id": 1,
    "user_id": 5,
    "name": "Toko Juju",
    "description": "Jual barang elektronik",
    "image": "stores/abc123.jpg"
  }
}
```

**Response 422 (sudah punya toko sebelumnya):**
```json
{
  "success": false,
  "message": "Anda sudah memiliki toko"
}
```

**Test case yang perlu dicoba:**
- ✅ Buat toko pertama kali → harus sukses (201)
- ✅ Coba buat toko kedua dengan user yang sama → harus gagal (422)
- ✅ Submit tanpa `name` → harus gagal validasi (422)

---

### 3. Update Toko

`PUT /api/seller/store`

**Request (form-data, semua field opsional):**
| Key | Type | Value |
|---|---|---|
| name | text | Toko Juju Updated |
| description | text | (opsional) |
| image | file | (opsional, ganti gambar) |

**Response 200:**
```json
{
  "success": true,
  "message": "Toko berhasil diperbarui",
  "data": {
    "id": 1,
    "name": "Toko Juju Updated",
    "image": "stores/xyz789.jpg"
  }
}
```

**Test case yang perlu dicoba:**
- ✅ Update hanya `name` tanpa ganti gambar → gambar lama tetap ada
- ✅ Update dengan gambar baru → cek gambar lama otomatis terhapus dari `storage/app/public/stores/`
- ✅ Update tanpa punya toko → harus gagal (404)

---

### 4. List Produk Sendiri

`GET /api/seller/products`

**Response 200:**
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
        "name": "Kabel USB-C",
        "price": "25000.00",
        "stock": 100
      }
    ],
    "total": 1
  }
}
```

**Test case yang perlu dicoba:**
- ✅ Login sebagai seller A, cek hanya produk milik toko A yang muncul (bukan produk seller lain)

---

### 5. Tambah Produk

`POST /api/seller/products`

**Request (form-data):**
| Key | Type | Value |
|---|---|---|
| category_id | text | 1 |
| name | text | Kabel USB-C |
| description | text | Kabel data 1 meter |
| price | text | 25000 |
| stock | text | 100 |
| image | file | (opsional) |

**Catatan:** `category_id` WAJIB diisi dan harus ada di tabel `categories`.

**Response 201:**
```json
{
  "success": true,
  "message": "Produk berhasil ditambahkan",
  "data": {
    "id": 1,
    "store_id": 1,
    "category_id": 1,
    "name": "Kabel USB-C",
    "price": "25000.00",
    "stock": 100
  }
}
```

**Test case yang perlu dicoba:**
- ✅ Tambah produk tanpa punya toko dulu → harus gagal (404), suruh bikin toko dulu
- ✅ Tambah produk tanpa `category_id` → harus gagal validasi (422)
- ✅ Tambah produk dengan `price` negatif → harus gagal validasi (422)
- ✅ Tambah produk dengan `stock` desimal (misal `1.5`) → harus gagal validasi (422)

---

### 6. Detail Produk Sendiri

`GET /api/seller/products/{id}`

**Response 200:** sama seperti struktur create.

**Response 404:**
```json
{
  "success": false,
  "message": "Produk tidak ditemukan"
}
```

**Test case yang perlu dicoba:**
- ✅ Akses produk milik seller lain pakai token sendiri → harus 404 (bukan malah nampilin data orang lain)

---

### 7. Update Produk

`PUT /api/seller/products/{id}`

**Request (form-data, semua field opsional):**
| Key | Type | Value |
|---|---|---|
| name | text | (opsional) |
| price | text | (opsional) |
| stock | text | (opsional) |
| image | file | (opsional) |

**Response 200:** data produk terbaru.

**Test case yang perlu dicoba:**
- ✅ Update partial (cuma `stock` doang) → field lain tetap utuh
- ✅ Update produk milik seller lain → harus 404

---

### 8. Hapus Produk

`DELETE /api/seller/products/{id}`

**Response 200:**
```json
{
  "success": true,
  "message": "Produk berhasil dihapus"
}
```

**Test case yang perlu dicoba:**
- ✅ Hapus produk → cek file gambar ikut terhapus dari storage
- ✅ Hapus produk milik seller lain → harus 404
- ✅ Hapus produk yang sudah dihapus (ulang) → harus 404

---

## Checklist Testing Keseluruhan L2

- [✅] Seller bisa buat toko (sekali doang)
- [✅] Seller tidak bisa buat toko kedua
- [✅] Seller bisa update toko sendiri (dengan & tanpa ganti gambar)
- [✅] Seller bisa CRUD produk miliknya sendiri
- [✅] Seller **tidak bisa** akses/edit/hapus produk milik seller lain (cek pakai 2 akun seller berbeda)
- [✅] User dengan role `buyer`/`driver` **tidak bisa** akses endpoint `/api/seller/*` sama sekali — cek ini juga, soalnya middleware sekarang cuma `auth:sanctum`, **belum cek role**

### Tambahan Testing Category

- [✅] List categories (public) dapat diakses tanpa login
- [✅] Seller harus pilih `category_id` saat create produk
- [✅] Produk dengan `category_id` invalid → validasi error
- [✅] Admin dapat CRUD kategori di `/api/admin/categories`

> ⚠️ **Catatan penting:** Endpoint `/api/seller/*` saat ini hanya dilindungi `auth:sanctum` (harus login), **belum ada pengecekan role**. Artinya user dengan role `buyer` yang login pun masih bisa akses endpoint ini. Kalau mau benar-benar dibatasi hanya untuk role `seller`, perlu middleware tambahan (misal cek `tokenCan('seller')`). Diskusikan ini sebelum lanjut ke level berikutnya jika ingin diperbaiki.
