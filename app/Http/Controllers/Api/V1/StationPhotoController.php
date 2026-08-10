<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RequiresCompletedSession;
use App\Http\Resources\StationPhotoResource;
use App\Models\ChargingStation;
use App\Models\StationPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Foto lokasi SPKLU (Fase 2) — galeri foto per lokasi, terpisah dari review.
 *
 * - GET list: PUBLIK (tanpa auth), paginated.
 * - POST upload (multipart): AUTH + gate completed-session + PLN-only.
 *   Multi-upload: array `photos` (max 5 file, masing-masing ≤2MB, image/*).
 * - DELETE: ADMIN only (moderasi; user tidak bisa hapus foto sendiri).
 *
 * Gate sama dgn review (Fase 1) — dipakai bersama via trait
 * RequiresCompletedSession.
 */
class StationPhotoController extends Controller
{
    use RequiresCompletedSession;

    public const MESSAGE_NOT_PLN = 'Foto hanya bisa diunggah untuk SPKLU PLN.';
    public const MESSAGE_NOT_COMPLETED = 'Kamu belum pernah menyelesaikan sesi charging di lokasi ini.';
    public const MAX_PHOTOS_PER_UPLOAD = 5;
    public const MAX_FILE_SIZE_KB = 2048; // 2MB

    /** List foto publik (paginated, anonim). */
    public function index(Request $request, ChargingStation $station): JsonResponse
    {
        $perPage = (int) ($request->per_page ?? 12);
        $perPage = max(1, min(50, $perPage));
        $paginator = $station->photos()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Station photos retrieved successfully',
            'data' => StationPhotoResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    /** Upload foto (multipart, multi-file) — auth + gate completed-session. */
    public function store(Request $request, ChargingStation $station): JsonResponse
    {
        if (! $this->isPlnStation($station)) {
            return $this->forbidden(self::MESSAGE_NOT_PLN);
        }

        if (! $this->hasCompletedSession($station)) {
            return $this->forbidden(self::MESSAGE_NOT_COMPLETED);
        }

        $validated = $request->validate([
            'photos' => 'required|array|min:1|max:' . self::MAX_PHOTOS_PER_UPLOAD,
            'photos.*' => 'image|max:' . self::MAX_FILE_SIZE_KB, // image/* MIME, ≤2MB
        ]);

        $dir = "station-photos/{$station->id}";
        $created = [];
        foreach ($request->file('photos') as $file) {
            // store() menghasilkan path unik (hash) di disk `public`.
            $path = $file->store($dir, 'public');
            $created[] = StationPhoto::create([
                'charging_station_id' => $station->id,
                'user_id' => Auth::id(),
                'path' => $path,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' foto berhasil diunggah',
            'data' => StationPhotoResource::collection(collect($created)),
        ], 201);
    }

    /** Hapus foto (admin only — moderasi). */
    public function destroy(Request $request, ChargingStation $station, StationPhoto $photo): JsonResponse
    {
        $user = Auth::user();
        if (! $user->hasRole('admin') && ! $user->hasRole('super_admin')) {
            return $this->forbidden('Unauthorized to delete photos.');
        }

        // Hapus file fisik lalu record (forceDelete bersihkan storage via model).
        if ($photo->path && Storage::disk('public')->exists($photo->path)) {
            Storage::disk('public')->delete($photo->path);
        }
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully',
        ]);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
