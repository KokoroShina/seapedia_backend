<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Reset Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        .title {
            font-size: 20px;
            color: #333;
            margin: 20px 0;
        }
        .otp-box {
            background-color: #f0f9ff;
            border: 2px dashed #2563eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 8px;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #92400e;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Seapedia</div>
        </div>
        
        <h2 class="title">Kode OTP Reset Password</h2>
        
        <p>Kami menerima permintaan reset password untuk akun Anda:</p>
        <p><strong>{{ $email }}</strong></p>
        
        <div class="otp-box">
            <p style="margin: 0 0 10px 0; color: #666;">Gunakan kode berikut:</p>
            <div class="otp-code">{{ $otp }}</div>
        </div>
        
        <div class="warning">
            ⚠️ <strong>Perhatian:</strong><br>
            • Kode OTP ini berlaku selama {{ $expiresMinutes }} menit<br>
            • Jangan bagikan kode ini kepada siapapun<br>
            • Jika Anda tidak meminta reset password, abaikan email ini
        </div>
        
        <div class="footer">
            <p>Email ini dikirim secara otomatis oleh sistem Seapedia.</p>
            <p>&copy; {{ date('Y') }} Seapedia. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
