<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\StationReviewResource;
use App\Models\Charge;
use App\Models\ChargingStation;
use App\Models\StationReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Review lokasi SPKLU (Fase 1).
 *
 * - GET list & summary: PUBLIK (tanpa auth).
 * - GET eligibility & POST create: AUTH.
 * - DELETE: ADMIN only (moderasi; user tidak bisa edit/hapus review sendiri).
 *
 * Gate store/eligibility:
 *  1. Station harus terdaftar PLN (`source = 'pln'` — keputusan spec saat
 *     serving switch ke ESDM: review disable utk station non-PLN).
 *  2. User harus pernah MENYELESAIKAN sesi charging di station tsb
 *     (`charges.charging_station_id` + `is_finish_charging = true`).
 */
class StationReviewController extends Controller
{
    public const MESSAGE_NOT_PLN = 'Ulasan hanya bisa dibuat untuk SPKLU PLN.';
    public const MESSAGE_NOT_COMPLETED = 'Kamu belum pernah menyelesaikan sesi charging di lokasi ini.';

    /** List review publik (paginated, anonim). */
    public function index(Request $request, ChargingStation $station): JsonResponse
    {
        $perPage = (int) ($request->per_page ?? 10);
        $perPage = max(1, min(50, $perPage));
        $paginator = $station->reviews()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Station reviews retrieved successfully',
            'data' => StationReviewResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    /** Ringkasan agregat (AVG + COUNT + distribusi bintang) — publik. */
    public function summary(ChargingStation $station): JsonResponse
    {
        $reviews = $station->reviews();

        $count = $reviews->count();
        $avg = $count > 0
            ? round((float) $reviews->avg('rating'), 1)
            : 0.0;

        $distribution = $station->reviews()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $byStar = [];
        for ($i = 1; $i <= 5; $i++) {
            $byStar[$i] = (int) ($distribution[$i] ?? 0);
        }

        return response()->json([
            'success' => true,
            'message' => 'Station review summary retrieved successfully',
            'data' => [
                'rating_avg' => $avg,
                'rating_count' => (int) $count,
                'distribution' => $byStar,
            ],
        ]);
    }

    /** Cek apakah user boleh menulis review (auth) — tanpa error 403. */
    public function eligibility(ChargingStation $station): JsonResponse
    {
        [$eligible, $reason] = $this->evaluateEligibility($station);

        return response()->json([
            'success' => true,
            'message' => 'Review eligibility retrieved successfully',
            'data' => [
                'is_eligible' => $eligible,
                'reason' => $reason,
            ],
        ]);
    }

    /** Simpan review baru (auth) — gate ketat, 403 bila belum eligible. */
    public function store(Request $request, ChargingStation $station): JsonResponse
    {
        [$eligible, $reason] = $this->evaluateEligibility($station);
        if (! $eligible) {
            return $this->forbidden($reason);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $review = StationReview::create([
            'charging_station_id' => $station->id,
            'user_id' => Auth::id(),
            'rating' => (int) $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_anonymous' => $request->boolean('is_anonymous', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'data' => new StationReviewResource($review),
        ], 201);
    }

    /** Hapus review (admin only — moderasi). */
    public function destroy(Request $request, ChargingStation $station, StationReview $review): JsonResponse
    {
        $user = Auth::user();
        if (! $user->hasRole('admin') && ! $user->hasRole('super_admin')) {
            return $this->forbidden('Unauthorized to delete reviews.');
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ]);
    }

    /**
     * Evaluasi gate eligibility. Return [bool, ?string reason].
     * Reason dipakai utk teks penjelasan tombol "Tulis ulasan".
     */
    private function evaluateEligibility(ChargingStation $station): array
    {
        if ($station->source !== 'pln') {
            return [false, self::MESSAGE_NOT_PLN];
        }

        $completed = Charge::query()
            ->where('user_id', Auth::id())
            ->where('charging_station_id', $station->id)
            ->where('is_finish_charging', true)
            ->exists();

        if (! $completed) {
            return [false, self::MESSAGE_NOT_COMPLETED];
        }

        return [true, null];
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
