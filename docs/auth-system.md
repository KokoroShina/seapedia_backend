# Dokumentasi: Auth System — SEAPEDIA

## Overview

Sistem autentikasi SEAPEDIA menggunakan **Laravel Sanctum** (token-based authentication). User bisa memiliki lebih dari satu role (multi-role), namun hanya satu role yang aktif dalam satu sesi/token — disebut **active role**.

## Keputusan Desain

| Keputusan | Alasan |
|---|---|
| Role disimpan sebagai string enum (`admin`, `seller`, `buyer`, `driver`) | Lebih readable dibanding integer, gak perlu mapping manual di frontend |
| Active role disimpan di **token ability Sanctum**, bukan di database | Stateless — gak perlu kolom tambahan, dan mendukung multi-device dengan role berbeda per sesi |
| Role `admin` tidak bisa didaftarkan lewat register publik | Admin dibuat manual (seeder/tinker) untuk alasan keamanan |
| Switch role = invalidate token lama + issue token baru | Simple, aman, dan konsisten dengan prinsip "satu token = satu active role" |

## Relasi Data

```
users ||--o{ user_roles }o--|| roles
```

User dan Role berelasi many-to-many lewat tabel pivot `user_roles`. Tidak ada kolom `active_role` di tabel manapun.

## Endpoints

### `POST /api/auth/register`
Mendaftarkan user baru dengan satu role pilihan (`buyer`, `seller`, atau `driver`).

**Request:**
```json
{
  "username": "johndoe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "buyer"
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Registrasi berhasil",
  "data": {
    "user": { "id": 1, "username": "johndoe", "email": "john@example.com" },
    "active_role": "buyer",
    "token": "1|xxxxxxxxxxxxxxxxxxxx"
  }
}
```

### `POST /api/auth/login`
Login dan dapatkan token dengan active role default (role pertama yang dimiliki).

**Request:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": { "id": 1, "username": "johndoe", "email": "john@example.com" },
    "roles": ["buyer", "seller"],
    "active_role": "buyer",
    "token": "2|xxxxxxxxxxxxxxxxxxxx"
  }
}
```

**Response 401 (kredensial salah):**
```json
{
  "success": false,
  "message": "Email atau password salah"
}
```

### `POST /api/auth/logout` 🔒
Menghapus token yang sedang digunakan. Butuh header `Authorization: Bearer {token}`.

**Response 200:**
```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

### `POST /api/auth/switch-role` 🔒
Mengganti active role. Token lama otomatis di-invalidate, token baru di-issue dengan ability role baru.

**Request:**
```json
{
  "role": "seller"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Role berhasil diganti",
  "data": {
    "active_role": "seller",
    "token": "3|xxxxxxxxxxxxxxxxxxxx"
  }
}
```

**Response 403 (role tidak dimiliki user):**
```json
{
  "success": false,
  "message": "Role tidak ditemukan"
}
```

> ⚠️ Setelah switch role, **frontend wajib mengganti token yang disimpan** (localStorage/cookie) dengan token baru dari response. Token lama sudah tidak valid.

## Cara Mengecek Active Role di Backend

Karena active role disimpan di token ability, cara cek di controller/middleware lain:

```php
$request->user()->tokenCan('seller'); // true/false
```

## Catatan untuk Frontend (Next.js)

- Simpan `token` setelah login/register/switch-role (replace token lama).
- Kirim header `Authorization: Bearer {token}` di setiap request yang butuh auth.
- `active_role` dari response dipakai untuk menentukan UI/dashboard mana yang ditampilkan (buyer/seller/driver).
- Jika user punya multiple roles, tampilkan dropdown switch role di UI yang memanggil endpoint `switch-role`.
