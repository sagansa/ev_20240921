<?php

namespace App\Http\Controllers;

use App\Models\Tester;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV daftar tester utk undangan email Play Console (Closed Testing).
 * Hanya admin. Streamed langsung — tanpa antrian/job.
 */
class TesterExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $user = $request->user();
        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('super_admin'))) {
            abort(403);
        }

        $columns = [
            'Email',
            'Status',
            'Platform',
            'Tanggal Daftar',
            'Last Ping',
            'Versi Terakhir',
            'Hari Aktif',
            'Device ID',
        ];

        return response()->streamDownload(function () use ($columns): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            Tester::query()
                ->orderByDesc('created_at')
                ->chunk(200, function ($testers) use ($handle): void {
                    foreach ($testers as $tester) {
                        fputcsv($handle, [
                            $tester->email,
                            $tester->status === 'store_active' ? 'Aktif di build testing' : 'Terdaftar',
                            $tester->platform,
                            $tester->created_at?->setTimezone('Asia/Jakarta')->toDateTimeString(),
                            $tester->last_ping_at?->setTimezone('Asia/Jakarta')->toDateTimeString(),
                            $tester->last_ping_version_code,
                            $tester->active_days,
                            $tester->device_id,
                        ]);
                    }
                });

            fclose($handle);
        }, 'testers-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
