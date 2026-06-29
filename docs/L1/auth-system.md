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

---

## 4. Forgot Password (OTP)

### 4.1 Send OTP

**Endpoint:** `POST /api/auth/forgot-password/send-otp`

Kirim kode OTP 6 digit ke email user.

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Kode OTP telah dikirim ke email Anda.",
  "data": {
    "expires_at": "2026-06-25T09:15:00+07:00",
    "expires_in_minutes": 15
  }
}
```

**Catatan:** Email enumeration prevention — response tetap sukses meskipun email tidak terdaftar.

### 4.2 Verify OTP

**Endpoint:** `POST /api/auth/forgot-password/verify-otp`

Verifikasi kode OTP.

**Request:**
```json
{
  "email": "user@example.com",
  "otp": "847291"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Kode OTP terverifikasi. Silakan reset password Anda."
}
```

### 4.3 Reset Password

**Endpoint:** `POST /api/auth/forgot-password/reset-password`

Reset password dengan OTP yang sudah diverifikasi.

**Request:**
```json
{
  "email": "user@example.com",
  "otp": "847291",
  "password": "NewPassword123",
  "password_confirmation": "NewPassword123"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Password berhasil direset. Silakan login dengan password baru Anda."
}
```

**Catatan Keamanan:**
- OTP expired setelah 15 menit (konfigurasi di `config/auth.php`)
- Setiap request OTP baru akan invalidate semua OTP lama
- Password harus minimal 8 karakter dengan mixed case dan angka

---

## 5. Endpoint Review Baru

### POST /api/reviews

**Endpoint:** `POST /api/reviews`

Membuat review baru (semua role yang login boleh akses).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "rating": 5,
  "comment": "Aplikasi sangat bagus!"
}
```

**Validation:**
- `rating`: required, integer, min 1, max 5
- `comment`: required, string, max 1000, disanitasi XSS dengan `strip_tags()`

**Response 201:**
```json
{
  "success": true,
  "message": "Review berhasil ditambahkan",
  "data": {
    "id": 1,
    "reviewer_name": "johndoe",
    "rating": 5,
    "comment": "Aplikasi sangat bagus!",
    "created_at": "2026-06-25T09:00:00+07:00"
  }
}
```

---

## Catatan untuk Frontend (Next.js)

Karena active role disimpan di token ability, cara cek di controller/middleware lain:

```php
$request->user()->tokenCan('seller'); // true/false
```

## Catatan untuk Frontend (Next.js)

- Simpan `token` setelah login/register/switch-role (replace token lama).
- Kirim header `Authorization: Bearer {token}` di setiap request yang butuh auth.
- `active_role` dari response dipakai untuk menentukan UI/dashboard mana yang ditampilkan (buyer/seller/driver).
- Jika user punya multiple roles, tampilkan dropdown switch role di UI yang memanggil endpoint `switch-role`.
