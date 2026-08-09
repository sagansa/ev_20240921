<?php

use App\Models\Battery;
use App\Models\Charge;
use App\Models\StateOfHealth;
use App\Models\Vehicle;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill data lama → model baterai baru:
 *  - Tiap vehicle (non-trashed) dapat 1 baterai default ber-label "Original",
 *    kapasitas dari type_vehicle.battery_capacity, terpasang sejak dibuat.
 *  - Semua charges & state_of_healths milik vehicle tsb di-link ke baterai itu,
 *    sehingga data historis masuk segmen "Battery Original" tanpa pecah.
 *
 * Idempoten: bila vehicle sudah punya baterai, tidak dibuat ganda; charge/SoH
 * yang sudah terisi battery_id tidak di-overwrite.
 *
 * Memakai Eloquent (bukan DB::connection('ev') langsung) agar path connection
 * konsisten dgn lingkungan: `ev` di produksi, default di testing (trait
 * UsesDefaultConnectionWhenTesting) — sekaligus transaksi test terlihat.
 */
return new class extends Migration
{
    public function up(): void
    {
        $created = 0;
        $linked = 0;

        foreach (Vehicle::with('typeVehicle')->get() as $vehicle) {
            // withTrashed() agar idempoten: bila battery pernah dibuat lalu
            // soft-deleted (mis. testing rollback+rerun), jangan duplikat.
            $existing = Battery::withTrashed()
                ->where('vehicle_id', $vehicle->id)
                ->exists();

            if (! $existing) {
                // `user_id` di-copy apa adanya dari vehicle (sumber: Auth::user()
                // saat pembuatan kendaraan). User model memakai koneksi
                // `sagansa_user`, jadi di sini TIDAK divalidasi ke tabel users
                // lokal (ev.users) yang hanya berisi subset lama. Battery.user_id
                // nullable & tanpa FK agar toleran cross-connection.
                Battery::create([
                    'vehicle_id' => $vehicle->id,
                    'user_id' => $vehicle->user_id,
                    'label' => 'Original',
                    'serial_number' => null,
                    'capacity_kwh' => $vehicle->typeVehicle?->battery_capacity,
                    'installed_at' => $vehicle->created_at?->toDateString() ?? now()->toDateString(),
                    'installed_km' => null,
                    'removed_at' => null,
                    'removed_km' => null,
                    'status' => 1,
                    'note' => 'Baterai awal kendaraan (backfill otomatis)',
                ]);
                $created++;
            }

            $batteryId = Battery::query()
                ->where('vehicle_id', $vehicle->id)
                ->orderBy('installed_at', 'asc')
                ->value('id');

            if (! $batteryId) {
                continue;
            }

            $linked += Charge::query()
                ->where('vehicle_id', $vehicle->id)
                ->whereNull('battery_id')
                ->update(['battery_id' => $batteryId]);

            $linked += StateOfHealth::query()
                ->where('vehicle_id', $vehicle->id)
                ->whereNull('battery_id')
                ->update(['battery_id' => $batteryId]);
        }
    }

    public function down(): void
    {
        // Backfill tidak dibatalkan pada rollback sederhana — data relasional
        // yang sudah di-link dibiarkan (nullable, aman). Bila perlu undo penuh,
        // hapus baterai ber-label "Original" buatan backfill. forceDelete karena
        // Battery pakai SoftDeletes (delete() biasa hanya soft-delete sehingga
        // re-run up() akan membuat duplikat).
        Battery::withTrashed()
            ->where('label', 'Original')
            ->where('note', 'Baterai awal kendaraan (backfill otomatis)')
            ->forceDelete();
    }
};
