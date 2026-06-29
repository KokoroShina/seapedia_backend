# Panduan Konfigurasi - Forgot Password OTP

## 📋 Prerequisites

1. PHP 8.3+
2. Laravel 13+
3. Database (MySQL, PostgreSQL, SQLite)
4. SMTP Server atau Email Service

---

## 🚀 Langkah Instalasi

### 1. Jalankan Migration

```bash
php artisan migrate
```

Ini akan membuat tabel `password_reset_otps` dengan kolom:
- `id` - Primary key
- `email` - Email user
- `otp` - Kode OTP (6 digit)
- `expires_at` - Waktu kadaluarsa
- `verified_at` - Waktu verifikasi (nullable)
- `is_used` - Status penggunaan
- `timestamps` - Created & updated at

### 2. Konfigurasi Email

#### Option A: Brevo (Sendinblue) - ⭐ RECOMMENDED

**Kelebihan:** Gratis 300 email/hari, easy setup

1. Daftar di https://www.brevo.com/
2. Verifikasi domain/email Anda
3. Dapatkan SMTP credentials dari Brevo dashboard
4. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your_brevo_email@domain.com
MAIL_PASSWORD=your_brevo_smtp_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@seapedia.com"
MAIL_FROM_NAME="Seapedia"
```

#### Option B: Gmail SMTP

**Kelebihan:** Gratis 500 email/hari

1. Aktifkan 2-Factor Authentication di Google Account
2. Buat App Password:
   - Buka https://myaccount.google.com/security
   - 2-Step Verification → App Passwords
   - Buat app password baru
3. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your.email@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@gmail.com"
MAIL_FROM_NAME="Seapedia"
```

⚠️ **Catatan:** Gmail membatasi pengiriman email. Gunakan untuk development saja.

#### Option C: Mailtrap (Development Only)

**Kelebihan:** Unlimited untuk development, email tidak wirklich dikirim

1. Daftar di https://mailtrap.io/
2. Buat inbox baru
3. Copy credentials dari SMTP Settings
4. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@seapedia.com"
MAIL_FROM_NAME="Seapedia"
```

#### Option D: Mailgun

**Kelebihan:** 5,000 email/bulan gratis

1. Daftar di https://www.mailgun.com/
2. Verifikasi domain
3. Dapatkan SMTP credentials
4. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@yourdomain.com
MAIL_PASSWORD=your_mailgun_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
MAIL_FROM_NAME="Seapedia"
```

#### Option E: Amazon SES

**Kelebihan:** 62,000 email/bulan gratis (new AWS accounts)

1. Buat AWS Account
2. Aktifkan Amazon SES
3. Dapatkan SMTP credentials
4. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your_ses_username
MAIL_PASSWORD=your_ses_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
MAIL_FROM_NAME="Seapedia"
```

---

### 3. Konfigurasi OTP

#### Config di `config/auth.php`:

```php
// OTP Configuration for Password Reset
'otp_expires_minutes' => env('AUTH_OTP_EXPIRES_MINUTES', 15),  // 15 menit
'otp_length' => env('AUTH_OTP_LENGTH', 6),                      // 6 digit
'otp_max_attempts' => env('AUTH_OTP_MAX_ATTEMPTS', 5),           // Max attempts
```

#### Atau set di `.env`:

```env
AUTH_OTP_EXPIRES_MINUTES=15
AUTH_OTP_LENGTH=6
AUTH_OTP_MAX_ATTEMPTS=5
```

### 4. Clear Config Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 5. Test Konfigurasi

```bash
# Check routes
php artisan route:list --path=forgot

# Test dengan Tinker (development)
php artisan tinker
```

```php
// Test kirim email
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

Mail::to('test@example.com')->send(new OtpMail('123456', 'test@example.com'));
```

---

## 🔧 Customization

### 1. Ubah Template Email

Edit `resources/views/emails/otp.blade.php`:

```blade
{{-- Ubah subject email --}}
public function envelope(): Envelope
{
    return new Envelope(
        subject: 'Kode OTP Reset Password - NamaApp Anda',
    );
}

{{-- Ubah template HTML --}}
<div class="otp-box">
    <p style="margin: 0 0 10px 0; color: #666;">Kode OTP Anda:</p>
    <div class="otp-code">{{ $otp }}</div>
</div>
```

### 2. Ubah Logic OTP Generation

Edit `app/Http/Controllers/Api/ForgotPasswordController.php`:

```php
// Default: 6 digit random
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Custom: Bisa pakai alphanumeric
$otp = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

// Custom: Bisa pakai letters + numbers
$otp = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
```

### 3. Ubah Password Requirements

Edit `app/Http/Requests/ForgotPassword/ResetPasswordRequest.php`:

```php
public function rules(): array
{
    return [
        'password' => [
            'required',
            'string',
            'confirmed',
            Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()  // Tambahkan special characters
                ->uncompromised(),  // Check gegen HaveIBeenPwned
        ],
    ];
}
```

### 4. Tambahkan Rate Limiting

Tambahkan di `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... existing middleware
    'throttle.otp' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':otp,1',
];
```

Update routes:

```php
Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])
    ->middleware('throttle.otp');
```

---

## 📊 Database Schema

### Tabel: password_reset_otps

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key, auto increment |
| email | varchar(255) | Email user (indexed) |
| otp | varchar(6) | Kode OTP 6 digit |
| expires_at | timestamp | Waktu kadaluarsa OTP |
| verified_at | timestamp | Waktu verifikasi (nullable) |
| is_used | boolean | Status penggunaan |
| created_at | timestamp | Waktu creation |
| updated_at | timestamp | Waktu update |

### Indexes

- `email` - Untuk query berdasarkan email
- `email, otp` - Untuk verifikasi OTP
- `email, is_used` - Untuk invalidasi OTP lama

---

## 🧪 Testing

### Run Feature Tests

```bash
# Run all forgot password tests
php artisan test --filter=ForgotPasswordTest

# Run specific test
php artisan test --filter=user_can_request_otp_for_password_reset
```

### Manual Testing

```bash
# 1. Start server
php artisan serve

# 2. Test dengan curl
curl -X POST http://localhost:8000/api/auth/forgot-password/send-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com"}'

curl -X POST http://localhost:8000/api/auth/forgot-password/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "otp": "123456"}'
```

---

## 🔒 Security Checklist

- [x] Email enumeration prevention
- [x] OTP expiration (15 menit)
- [x] Single use OTP
- [x] Password hashing (bcrypt)
- [x] Password confirmation validation
- [x] Rate limiting ready (middleware prepared)
- [x] CSRF protection (Laravel default)
- [x] SQL injection prevention (Eloquent ORM)
- [x] XSS prevention (Blade templating)

### Optional Security Enhancements

1. **Rate Limiting per IP:**
   ```php
   Route::post('/forgot-password/send-otp', [...])
       ->middleware('throttle:5,1'); // 5 attempts per minute
   ```

2. **Log Failed Attempts:**
   ```php
   Log::warning('Failed OTP verification', [
       'email' => $email,
       'ip' => $request->ip(),
   ]);
   ```

3. **Notify User of Suspicious Activity:**
   ```php
   if ($user->password_changed_recently) {
       Mail::to($user)->send(new SuspiciousLoginAlert($user));
   }
   ```

---

## ❓ Troubleshooting

### Email tidak terkirim?

1. Check log: `storage/logs/laravel.log`
2. Check `.env` configuration
3. Test SMTP connection:
   ```bash
   php artisan tinker
   Mail::failures();
   ```

### OTP tidak valid?

1. Check apakah OTP sudah expired
2. Check apakah OTP sudah digunakan
3. Check timezone server vs client

### "Class not found" error?

```bash
composer dump-autoload
php artisan cache:clear
```

### Database error?

```bash
php artisan migrate:fresh --seed
```
