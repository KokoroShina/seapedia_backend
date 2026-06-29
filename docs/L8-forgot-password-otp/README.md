# Dokumentasi Fitur Forgot Password dengan OTP

## 📋 Overview

Fitur **Forgot Password dengan OTP (One-Time Password)** memungkinkan user mereset password mereka melalui kode verifikasi 6 digit yang dikirim ke email. Fitur ini menggunakan library gratis bawaan Laravel.

---

## 📁 Struktur File

```
docs/L8-forgot-password-otp/
├── README.md                    # Dokumentasi utama
├── API-REFERENCE.md             # Referensi API lengkap
├── CONFIGURATION.md             # Panduan konfigurasi
└── img/                         # Gambar (jika ada)
    └── flowchart.png

app/
├── Http/Controllers/Api/
│   └── ForgotPasswordController.php    # Main controller
├── Http/Requests/ForgotPassword/
│   ├── SendOtpRequest.php              # Validasi kirim OTP
│   ├── VerifyOtpRequest.php            # Validasi verifikasi OTP
│   └── ResetPasswordRequest.php        # Validasi reset password
├── Mail/
│   └── OtpMail.php                    # Mailable untuk email OTP
├── Models/
│   └── PasswordResetOtp.php           # Model OTP
├── Mail/resources/views/
│   └── emails/
│       └── otp.blade.php               # Template email OTP

database/migrations/
└── 2026_06_23_000001_create_password_reset_otps_table.php

config/
└── auth.php                           # Config OTP (otp_expires_minutes, dll)

routes/
└── api.php                            # Routes untuk forgot password

tests/Feature/
└── ForgotPasswordTest.php             # Feature tests (10 test cases)
```

---

## 🔄 Alur Kerja (Flow)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           FORGOT PASSWORD FLOW                              │
└─────────────────────────────────────────────────────────────────────────────┘

   User                                      Server                              Email
    │                                          │                                   │
    │  1. POST /api/auth/forgot-password/     │                                   │
    │              send-otp                    │                                   │
    │       { email: "user@example.com" }     │                                   │
    │ ───────────────────────────────────────►│                                   │
    │                                          │                                   │
    │                                          │ 2. Generate OTP 6 digit            │
    │                                          │    (contoh: "847291")             │
    │                                          │                                   │
    │                                          │ 3. Simpan ke database:            │
    │                                          │    - email                        │
    │                                          │    - otp (hashed)                 │
    │                                          │    - expires_at (+15 menit)       │
    │                                          │    - is_used = false              │
    │                                          │                                   │
    │                                          │ 4. Kirim email OTP                │
    │                                          │──────────────────────────────────►│
    │                                          │                                   │
    │  5. Response: "OTP dikirim ke email"    │                                   │
    │ ◄───────────────────────────────────────│                                   │
    │                                          │                                   │
    │  6. Buka email, lihat kode OTP          │                                   │
    │ ◄───────────────────────────────────────│                                   │
    │                                          │                                   │
    │  7. POST /api/auth/forgot-password/      │                                   │
    │              verify-otp                  │                                   │
    │       { email, otp: "847291" }          │                                   │
    │ ───────────────────────────────────────►│                                   │
    │                                          │                                   │
    │                                          │ 8. Validasi OTP:                  │
    │                                          │    ✓ Email match                  │
    │                                          │    ✓ OTP match                    │
    │                                          │    ✓ Not expired                  │
    │                                          │    ✓ Not used                     │
    │                                          │                                   │
    │                                          │ 9. Update: verified_at = now()    │
    │                                          │           is_used = true         │
    │                                          │                                   │
    │  10. Response: "OTP terverifikasi"     │                                   │
    │ ◄───────────────────────────────────────│                                   │
    │                                          │                                   │
    │  11. POST /api/auth/forgot-password/    │                                   │
    │              reset-password              │                                   │
    │       { email, otp, password,           │                                   │
    │         password_confirmation }        │                                   │
    │ ───────────────────────────────────────►│                                   │
    │                                          │                                   │
    │                                          │ 12. Validasi:                    │
    │                                          │    ✓ OTP sudah verified          │
    │                                          │    ✓ Within expiry time          │
    │                                          │    ✓ Password requirements met   │
    │                                          │                                   │
    │                                          │ 13. Update password user          │
    │                                          │    (bcrypt)                       │
    │                                          │                                   │
    │                                          │ 14. Invalidate all OTPs          │
    │                                          │    untuk email ini               │
    │                                          │                                   │
    │  15. Response: "Password berhasil      │                                   │
    │           direset"                       │                                   │
    │ ◄───────────────────────────────────────│                                   │
    │                                          │                                   │
    │  16. User login dengan password baru     │                                   │
    │                                          │                                   │
```

---

## ⚙️ Konfigurasi

### File `.env`

```env
# Email Configuration (pilih salah satu provider)

# --- BREVO (SENDINBLUE) - RECOMMENDED ---
# Gratis 300 email/hari
# Daftar di: https://www.brevo.com/
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_brevo_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@seapedia.com"
MAIL_FROM_NAME="Seapedia"

# --- GMAIL SMTP ---
# Gratis 500 email/hari
# Butuh: App Password (aktifkan 2FA dulu)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls

# --- MAILTRAP (Development Only) ---
# Unlimited untuk development
# Daftar di: https://mailtrap.io/
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls

# --- MAILGUN ---
# Gratis 5,000 email/bulan
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@yourdomain.com
MAIL_PASSWORD=your_mailgun_password
MAIL_ENCRYPTION=tls
```

### Konfigurasi OTP (`config/auth.php`)

```php
'otp_expires_minutes' => env('AUTH_OTP_EXPIRES_MINUTES', 15),  // Default 15 menit
'otp_length' => env('AUTH_OTP_LENGTH', 6),                     // Default 6 digit
'otp_max_attempts' => env('AUTH_OTP_MAX_ATTEMPTS', 5),          // Max attempts (future)
```

---

## 🔒 Security Features

1. **Email Enumeration Prevention**: Tidak ada perbedaan response antara email yang terdaftar dan tidak
2. **OTP Expiration**: OTP expired setelah 15 menit (konfigurasi)
3. **Single Use OTP**: Setiap OTP hanya bisa dipakai sekali
4. **OTP Invalidation**: Request OTP baru akan invalidate semua OTP lama
5. **Rate Limiting Ready**: Sudah siap untuk di-extend dengan throttle
6. **Password Requirements**: Minimal 8 karakter, mixed case, dan angka

---

## 📝 Notes

- Semua menggunakan library bawaan Laravel (gratis)
- Tidak perlu package tambahan
- Support semua SMTP provider
- Optimized untuk production
