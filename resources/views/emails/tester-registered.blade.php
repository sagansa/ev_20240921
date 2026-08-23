<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 24px; }
        .card { background: #ffffff; border-radius: 12px; padding: 32px; max-width: 480px; margin: 0 auto; }
        .hint { color: #6b7280; font-size: 14px; text-align: center; }
        .label { font-weight: 600; color: #374151; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        td { padding: 6px 0; vertical-align: top; }
        td:first-child { width: 130px; color: #6b7280; font-size: 14px; }
        td:last-child { font-size: 14px; color: #111827; }
    </style>
</head>
<body>
    <div class="card">
        <p class="label">Tester baru terdaftar di sistem Closed Testing.</p>
        <table>
            <tr><td>Email</td><td>{{ $tester->email }}</td></tr>
            <tr><td>Source</td><td>{{ $tester->source }}</td></tr>
            <tr><td>Platform</td><td>{{ $tester->platform ?? '—' }}</td></tr>
            <tr><td>Device ID</td><td>{{ $tester->device_id ?? '—' }}</td></tr>
            <tr><td>Waktu</td><td>{{ $tester->created_at->format('d M Y H:i:s') }} UTC</td></tr>
        </table>
        <p class="hint"><a href="{{ url('/admin/testers') }}" style="color:#16a34a;">Buka halaman Testers di panel admin</a></p>
    </div>
</body>
</html>
