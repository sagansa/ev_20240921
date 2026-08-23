<?php

namespace App\Observers;

use App\Mail\TesterRegisteredMail;
use App\Models\Tester;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TesterObserver
{
    /**
     * Handle the Tester "created" event.
     *
     * Kirim email notifikasi ke admin. Kegagalan SMTP tidak pernah
     * menggagalkan response API registrasi (failure-tolerant).
     */
    public function created(Tester $tester): void
    {
        if (! config('admin_notify.enabled', true)) {
            return;
        }

        $adminEmail = config('admin_notify.email');
        if (empty($adminEmail)) {
            Log::warning('ADMIN_NOTIFY_EMAIL not set — skipping tester notification email.');
            return;
        }

        try {
            Mail::to($adminEmail)->send(new TesterRegisteredMail($tester));
        } catch (\Throwable $e) {
            Log::error('Failed to send tester notification email', [
                'tester_id' => $tester->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
