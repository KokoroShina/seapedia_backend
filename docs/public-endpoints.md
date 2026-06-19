# Dokumentasi: Public Endpoints — SEAPEDIA

Dokumentasi ini menjelaskan endpoints REST API publik SEAPEDIA yang **tidak membutuhkan autentikasi**. Endpoint ini ditujukan untuk menampilkan data di halaman depan marketplace sebelum user login.

## Daftar Endpoint Publik

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/api/stores` | Mengambil daftar seluruh toko (terbaru dulu, paginasi 15) |
| `GET` | `/api/stores/{id}` | Mengambil detail satu toko beserta daftar produknya |
| `GET` | `/api/products` | Mengambil daftar produk dengan filter toko & pencarian |
| `GET` | `/api/products/{id}` | Mengambil detail satu produk beserta informasi tokonya |
| `GET` | `/api/reviews` | Mengambil seluruh review aplikasi (terbaru dulu, paginasi 15) |

---

## 1. List Toko
Mengambil semua toko diurutkan dari yang terbaru (`created_at DESC`).

* **URL:** `/api/stores`
* **Method:** `GET`
* **Query Parameters:**
  * `page` (optional) -> Angka halaman pagination (default: 1)

### Contoh Response Sukses (200 OK)
```json
{
  "success": true,
  "message": "Daftar toko berhasil diambil",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Toko Ikan Hias Segar",
        "description": "Menjual berbagai jenis ikan hias air laut segar",
        "image": "stores/aquarium.jpg"
      }
    ],
    "first_page_url": "http://localhost:8000/api/stores?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/stores?page=1",
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://localhost:8000/api/stores?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "next_page_url": null,
    "path": "http://localhost:8000/api/stores",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

---

## 2. Detail Toko
Mengambil detail informasi satu toko beserta daftar produk miliknya (relasi `products`).

* **URL:** `/api/stores/{id}`
* **Method:** `GET`

### Contoh Response Sukses (200 OK)
```json
{
  "success": true,
  "message": "Detail toko berhasil diambil",
  "data": {
    "id": 1,
    "user_id": 2,
    "name": "Toko Ikan Hias Segar",
    "description": "Menjual berbagai jenis ikan hias air laut segar",
    "image": "stores/aquarium.jpg",
    "created_at": "2026-06-17T00:00:00.000000Z",
    "updated_at": "2026-06-17T00:00:00.000000Z",
    "products": [
      {
        "id": 5,
        "store_id": 1,
        "name": "Clownfish Nemo",
        "description": "Ikan badut hias lucu",
        "price": "75000.00",
        "stock": 20,
        "image": "products/nemo.jpg",
        "created_at": "2026-06-17T00:05:00.000000Z",
        "updated_at": "2026-06-17T00:05:00.000000Z"
      }
    ]
  }
}
```

### Contoh Response Error (404 Not Found)
```json
{
  "success": false,
  "message": "Toko tidak ditemukan"
}
```

---

## 3. List Produk
Mengambil daftar semua produk diurutkan dari yang terbaru (`created_at DESC`), mendukung filter toko dan pencarian nama.

* **URL:** `/api/products`
* **Method:** `GET`
* **Query Parameters:**
  * `store_id` (optional) -> Filter produk berdasarkan ID toko tertentu (contoh: `/api/products?store_id=1`)
  * `search` (optional) -> Pencarian nama produk dengan pencocokan parsial/LIKE (contoh: `/api/products?search=nemo`)
  * `page` (optional) -> Angka halaman pagination (default: 1)

### Contoh Response Sukses (200 OK)
```json
{
  "success": true,
  "message": "Daftar produk berhasil diambil",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 5,
        "store_id": 1,
        "name": "Clownfish Nemo",
        "description": "Ikan badut hias lucu",
        "price": "75000.00",
        "stock": 20,
        "image": "products/nemo.jpg"
      }
    ],
    "first_page_url": "http://localhost:8000/api/products?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/products?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/products",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

---

## 4. Detail Produk
Mengambil detail satu produk beserta info toko terkait (relasi `store`, hanya kolom `id` dan `name`).

* **URL:** `/api/products/{id}`
* **Method:** `GET`

### Contoh Response Sukses (200 OK)
```json
{
  "success": true,
  "message": "Detail produk berhasil diambil",
  "data": {
    "id": 5,
    "store_id": 1,
    "name": "Clownfish Nemo",
    "description": "Ikan badut hias lucu",
    "price": "75000.00",
    "stock": 20,
    "image": "products/nemo.jpg",
    "created_at": "2026-06-17T00:05:00.000000Z",
    "updated_at": "2026-06-17T00:05:00.000000Z",
    "store": {
      "id": 1,
      "name": "Toko Ikan Hias Segar"
    }
  }
}
```

### Contoh Response Error (404 Not Found)
```json
{
  "success": false,
  "message": "Produk tidak ditemukan"
}
```

---

## 5. List App Reviews
Mengambil semua review aplikasi diurutkan dari yang terbaru (`created_at DESC`).

* **URL:** `/api/reviews`
* **Method:** `GET`
* **Query Parameters:**
  * `page` (optional) -> Angka halaman pagination (default: 1)

### Contoh Response Sukses (200 OK)
```json
{
  "success": true,
  "message": "Daftar review berhasil diambil",
  "data": {
    "current_page": 1,
    "data": [
      {
        "reviewer_name": "Budi",
        "rating": 5,
        "comment": "Aplikasi marketplace terbaik untuk hasil laut!",
        "created_at": "2026-06-17T00:10:00.000000Z"
      }
    ],
    "first_page_url": "http://localhost:8000/api/reviews?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/reviews?page=1",
    "links": [],
    "next_page_url": null,
    "path": "http://localhost:8000/api/reviews",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

---

## Catatan Teknis

### 1. Format Pagination
- Semua list endpoints (`/api/stores`, `/api/products`, `/api/reviews`) mengembalikan format data paginasi standard Laravel.
- Struktur pagination berisi metadata tambahan seperti `current_page`, `last_page`, `per_page`, `total`, serta URL halaman sebelum dan sesudahnya (`prev_page_url`, `next_page_url`).
- Batasan default per halaman adalah **15 item**.

### 2. Mekanisme Filtering & Pencarian di `/api/products`
- **Filter `store_id`**: Jika terdapat parameter `store_id` (misalnya `?store_id=3`), API akan menyaring data produk dengan klausa database `where('store_id', $store_id)`.
- **Pencarian `search`**: Jika terdapat parameter `search` (misalnya `?search=kakap`), API akan mencari produk menggunakan klausa database `where('name', 'like', '%kakap%')` (pencocokan parsial yang tidak sensitif huruf besar/kecil di sebagian besar sistem database).
- Kedua filter ini dapat digunakan secara bersamaan, misalnya: `/api/products?store_id=1&search=nemo`.
