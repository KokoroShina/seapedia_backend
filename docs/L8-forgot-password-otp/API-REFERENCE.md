# API Reference - Forgot Password OTP

## Base URL

```
http://localhost:3000/api
```

---

## 1. Send OTP

Kirim kode OTP ke email user.

### Endpoint

```
POST /api/auth/forgot-password/send-otp
```

### Request

#### Headers

| Header | Value |
|--------|-------|
| Content-Type | application/json |
| Accept | application/json |

#### Body

```json
{
    "email": "user@example.com"
}
```

#### Validation Rules

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| email | string | ✅ | Valid email format, max 255 characters |

### Response

#### Success (200 OK)

```json
{
    "success": true,
    "message": "Kode OTP telah dikirim ke email Anda.",
    "data": {
        "expires_at": "2026-06-23T16:15:00+07:00",
        "expires_in_minutes": 15
    }
}
```

#### Email Tidak Terdaftar (200 OK)
*Returns success untuk prevent email enumeration*

```json
{
    "success": true,
    "message": "Jika email tersebut terdaftar, kode OTP telah dikirim."
}
```

#### Validation Error (422 Unprocessable Entity)

```json
{
    "message": "The email field must be a valid email address.",
    "errors": {
        "email": ["The email field must be a valid email address."]
    }
}
```

### Contoh cURL

```bash
curl -X POST http://localhost:3000/api/auth/forgot-password/send-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com"}'
```

### Contoh JavaScript (Fetch)

```javascript
const response = await fetch('/api/auth/forgot-password/send-otp', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        email: 'user@example.com'
    })
});

const data = await response.json();
console.log(data);
```

---

## 2. Verify OTP

Verifikasi kode OTP yang telah dikirim ke email.

### Endpoint

```
POST /api/auth/forgot-password/verify-otp
```

### Request

#### Headers

| Header | Value |
|--------|-------|
| Content-Type | application/json |
| Accept | application/json |

#### Body

```json
{
    "email": "user@example.com",
    "otp": "847291"
}
```

#### Validation Rules

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| email | string | ✅ | Valid email format, max 255 characters |
| otp | string | ✅ | Exactly 6 digits, numbers only |

### Response

#### Success (200 OK)

```json
{
    "success": true,
    "message": "Kode OTP terverifikasi. Silakan reset password Anda."
}
```

#### OTP Invalid (400 Bad Request)

```json
{
    "success": false,
    "message": "Kode OTP tidak valid atau sudah expired."
}
```

#### Validation Error (422 Unprocessable Entity)

```json
{
    "message": "The otp must be exactly 6 digits.",
    "errors": {
        "otp": [
            "The otp must be exactly 6 digits.",
            "The otp must be numeric."
        ]
    }
}
```

### Contoh cURL

```bash
curl -X POST http://localhost:3000/api/auth/forgot-password/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "otp": "847291"}'
```

---

## 3. Reset Password

Reset password dengan OTP yang sudah diverifikasi.

### Endpoint

```
POST /api/auth/forgot-password/reset-password
```

### Request

#### Headers

| Header | Value |
|--------|-------|
| Content-Type | application/json |
| Accept | application/json |

#### Body

```json
{
    "email": "user@example.com",
    "otp": "847291",
    "password": "NewPassword123",
    "password_confirmation": "NewPassword123"
}
```

#### Validation Rules

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| email | string | ✅ | Valid email format, max 255 characters |
| otp | string | ✅ | Exactly 6 digits, numbers only |
| password | string | ✅ | Min 8 chars, mixed case, contains number, confirmed |
| password_confirmation | string | ✅ | Must match password |

### Response

#### Success (200 OK)

```json
{
    "success": true,
    "message": "Password berhasil direset. Silakan login dengan password baru Anda."
}
```

#### Session Expired (400 Bad Request)

```json
{
    "success": false,
    "message": "Sesi OTP tidak valid. Silakan mulai ulang proses reset password."
}
```

#### Validation Error (422 Unprocessable Entity)

```json
{
    "message": "The password must be at least 8 characters.",
    "errors": {
        "password": [
            "The password must be at least 8 characters.",
            "The password confirmation does not match."
        ]
    }
}
```

### Contoh cURL

```bash
curl -X POST http://localhost:3000/api/auth/forgot-password/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "otp": "847291",
    "password": "NewPassword123",
    "password_confirmation": "NewPassword123"
  }'
```

---

## 📊 HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request (OTP invalid/expired) |
| 422 | Validation Error |
| 500 | Server Error |

---

## 🔄 Complete Flow Example

### Step 1: Request OTP

```javascript
// Frontend: User clicks "Forgot Password"
const result = await fetch('/api/auth/forgot-password/send-otp', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: 'user@example.com' })
});

// Response: { success: true, message: "Kode OTP telah dikirim ke email Anda." }
```

### Step 2: User reads email

```
Subject: Kode OTP Reset Password - Seapedia

Kode OTP Anda: 847291

Berlaku selama 15 menit
```

### Step 3: Verify OTP

```javascript
// Frontend: User enters OTP
const result = await fetch('/api/auth/forgot-password/verify-otp', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ 
        email: 'user@example.com',
        otp: '847291'
    })
});

// Response: { success: true, message: "Kode OTP terverifikasi. Silakan reset password Anda." }
```

### Step 4: Reset Password

```javascript
// Frontend: User enters new password
const result = await fetch('/api/auth/forgot-password/reset-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        email: 'user@example.com',
        otp: '847291',
        password: 'NewPassword123',
        password_confirmation: 'NewPassword123'
    })
});

// Response: { success: true, message: "Password berhasil direset. Silakan login dengan password baru Anda." }
```

### Step 5: User Login

```javascript
// Frontend: User login with new password
const result = await fetch('/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'NewPassword123'
    })
});

// Success! User is now logged in
```

---

## ⚠️ Error Handling

### Frontend Error Handling Example

```javascript
async function forgotPassword(email) {
    try {
        // Step 1: Send OTP
        const sendResponse = await fetch('/api/auth/forgot-password/send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        
        const sendData = await sendResponse.json();
        
        if (!sendData.success) {
            throw new Error(sendData.message);
        }
        
        // Show success message
        showMessage('OTP telah dikirim ke email Anda');
        
    } catch (error) {
        showError(error.message);
    }
}

async function verifyAndReset(email, otp, newPassword) {
    try {
        // Step 2: Verify OTP
        const verifyResponse = await fetch('/api/auth/forgot-password/verify-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, otp })
        });
        
        const verifyData = await verifyResponse.json();
        
        if (!verifyData.success) {
            throw new Error(verifyData.message);
        }
        
        // Step 3: Reset Password
        const resetResponse = await fetch('/api/auth/forgot-password/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email,
                otp,
                password: newPassword,
                password_confirmation: newPassword
            })
        });
        
        const resetData = await resetResponse.json();
        
        if (!resetData.success) {
            throw new Error(resetData.message);
        }
        
        // Success! Redirect to login
        showMessage('Password berhasil direset. Silakan login.');
        redirectTo('/login');
        
    } catch (error) {
        showError(error.message);
    }
}
```
