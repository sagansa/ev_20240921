<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tester;
use App\Models\TesterPing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tester funnel (Closed Testing Play Console).
 *
 * - POST register: AUTH (identitas dari token Sanctum). Body hanya
 *   `{device_id?, platform?}`. Email di-copy dari users saat itu (tanpa join
 *   lintas DB di panel). Idempotent via updateOrCreate by user_id.
 * - POST ping: PUBLIK (install fresh build store belum login). Bearer
 *   opsional: ada token → match tester by user_id; else match by device_id.
 *   Selalu insert row `tester_pings` (append-only, hitung hari aktif).
 * - GET app/config: PUBLIK — force-update & flag tracking build usage.
 */
class TesterController extends Controller
{
    /**
     * Daftarkan device sebagai tester. Auth wajib.
     */
    public function register(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:128'],
            'platform' => ['nullable', 'string', 'max:32'],
        ]);

        $tester = Tester::updateOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $user->email,
                'device_id' => $validated['device_id'] ?? null,
                'platform' => $validated['platform'] ?? null,
                'source' => 'internal_app_sharing',
            ],
        );

        // Baca default kolom (mis. status) dari DB setelah create/update.
        $tester->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Tester registered successfully',
            'data' => [
                'id' => $tester->id,
                'email' => $tester->email,
                'status' => $tester->status,
            ],
        ], 201);
    }

    /**
     * Daftarkan tester dari app Islam (email gate) — PUBLIK, tanpa login.
     *
     * Menerima email dari body (bukan akun), idempotent per `device_id`
     * (updateOrCreate). `user_id` = null; source = `islam_email_gate`.
     * Row ikut muncul di Filament + export CSV yang sudah ada.
     */
    public function registerEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'device_id' => ['required', 'string', 'max:128'],
            'platform' => ['nullable', 'string', 'max:32'],
        ]);

        $tester = Tester::updateOrCreate(
            ['device_id' => $validated['device_id']],
            [
                'email' => $validated['email'],
                'platform' => $validated['platform'] ?? null,
                'source' => 'islam_email_gate',
                'user_id' => null,
            ],
        );

        $tester->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Tester registered successfully',
            'data' => [
                'id' => $tester->id,
                'email' => $tester->email,
                'status' => $tester->status,
            ],
        ], 201);
    }

    /**
     * Ping build usage — publik, Bearer opsional.
     */
    public function ping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:128'],
            'channel' => ['required', 'string', 'in:ias,store', 'max:16'],
            'version_code' => ['required', 'string', 'max:32'],
            'platform' => ['nullable', 'string', 'max:32'],
        ]);

        $channel = $validated['channel'];
        $deviceId = $validated['device_id'];
        $versionCode = $validated['version_code'];
        $platform = $validated['platform'] ?? null;

        // Resolve tester: token (kalau ada) lebih spesifik dari device_id.
        $tester = null;
        if (Auth::guard('sanctum')->check()) {
            $tester = Tester::where('user_id', Auth::guard('sanctum')->id())->first();
        }
        if ($tester === null) {
            $tester = Tester::where('device_id', $deviceId)->first();
        }

        if ($tester !== null) {
            $now = now();

            if ($channel === 'store') {
                $tester->status = 'store_active';
                $tester->first_store_ping_at = $tester->first_store_ping_at ?? $now;
            }

            $tester->last_ping_at = $now;
            $tester->last_ping_version_code = $versionCode;
            $tester->platform = $platform ?? $tester->platform;
            $tester->save();
        }

        TesterPing::create([
            'tester_id' => $tester?->id,
            'device_id' => $deviceId,
            'channel' => $channel,
            'version_code' => $versionCode,
            'platform' => $platform,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ping recorded',
            'data' => [
                'tracked' => $tester !== null,
                'status' => $tester?->status ?? 'unknown',
            ],
        ], 201);
    }

    /**
     * Konfigurasi app untuk klien (force-update & tracking) — publik.
     */
    public function appConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'App config retrieved successfully',
            'data' => [
                'min_version_code' => (int) config('appconfig.min_version_code'),
                'latest_version_name' => config('appconfig.latest_version_name'),
                'update_message' => config('appconfig.update_message'),
                'update_url' => config('appconfig.update_url'),
                'track_build_usage' => (bool) config('appconfig.track_build_usage'),
                'latest_store_version_code' => (int) config('appconfig.latest_store_version_code'),
                'min_version_code_ios' => (int) config('appconfig.min_version_code_ios'),
                'update_url_ios' => config('appconfig.update_url_ios'),
            ],
        ]);
    }
}
