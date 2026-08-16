<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Terverifikasi — EV Charge ID</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 24px; }
        .card { background: #ffffff; border-radius: 12px; padding: 32px; max-width: 480px; margin: 0 auto; text-align: center; }
        .check { font-size: 48px; }
        h1 { font-size: 22px; color: #111827; margin: 12px 0 8px; }
        p { color: #6b7280; font-size: 14px; line-height: 1.6; margin: 8px 0; }
        .name { color: #111827; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="check">&#9989;</div>
        @if($alreadyVerified)
            <h1>Email Sudah Terverifikasi</h1>
            <p>Email <span class="name">{{ $name }}</span> sudah terverifikasi sebelumnya — tidak ada yang perlu dilakukan.</p>
        @else
            <h1>Email Berhasil Diverifikasi!</h1>
            <p>Terima kasih, <span class="name">{{ $name }}</span>. Alamat email Anda kini terverifikasi.</p>
        @endif
        <p>Silakan kembali ke aplikasi <strong>EV Charge ID</strong>, lalu masuk dengan email dan password Anda.</p>
    </div>
</body>
</html>
