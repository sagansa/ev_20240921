<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 24px; }
        .card { background: #ffffff; border-radius: 12px; padding: 32px; max-width: 480px; margin: 0 auto; }
        .otp { font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #16a34a; text-align: center; margin: 24px 0; }
        .hint { color: #6b7280; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <p>Halo {{ $name }},</p>
        <p>Anda menerima email ini karena ada permintaan reset password. Gunakan kode berikut:</p>
        <div class="otp">{{ $otp }}</div>
        <p class="hint">Kode berlaku selama {{ $expiresInMinutes }} menit dan hanya untuk satu kali penggunaan.</p>
        <p class="hint">Jika Anda tidak meminta reset password, abaikan email ini.</p>
    </div>
</body>
</html>
