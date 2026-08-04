<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client ke LM Studio — LLM lokal dengan endpoint OpenAI-compatible.
 *
 * Dipakai stage 2 matching PLN ↔ ESDM: mengklasifikasikan apakah sepasang
 * stasiun PLN & ESDM merujuk ke lokasi fisik SPKLU yang sama.
 *
 * Endpoint yang dipakai:
 *   GET  {base_url}/models             → isAvailable()
 *   POST {base_url}/chat/completions   → classifyMatch()
 *
 * Keputusan AI selalu advisory: hasil klasifikasi berstatus `ai_suggested`
 * (perlu approve admin) sebelum di-fold ke serving. Bila server mati / output
 * invalid, method melempar RuntimeException — caller melakukan fallback.
 */
class LmStudioClient
{
    private string $baseUrl;

    private string $apiKey;

    private string $model;

    private int $timeout;

    private bool $enabled;

    /** Instruksi sistem: tegas, output JSON murni tanpa teks lain. */
    private const SYSTEM_PROMPT = <<<'PROMPT'
Anda adalah asisten khusus untuk mencocokkan dua catatan stasiun pengisian kendaraan listrik (SPKLU) yang merujuk ke lokasi fisik yang SAMA, dari dua sumber berbeda: PLN dan ESDM.

Diberikan sepasang stasiun PLN dan stasiun ESDM (beserta alamat, koordinat, provinsi, provider, jarak antar keduanya, dan kemiripan nama), tentukan apakah keduanya adalah lokasi SPKLU yang SAMA.

Indikator lokasi SAMA:
- Nama serupa / identik (mis. "PLN EV CS Kendari" vs "Kendari - PLN EV CS").
- Alamat atau kawasan yang sama (nama gedung / jalan / kompleks yang cocok).
- Koordinat saling berdekatan (jarak < 500 m kuat; 500–1000 m mencurigakan).
- Provider PLN (PLN Mobile) — SPKLU yang dikelola PLN hampir selalu terdaftar di data ESDM.

Indikator BUKAN lokasi sama:
- Hanya kota yang sama tapi nama tempat beda total.
- Jarak jauh (> 1 km) atau koordinat di area berbeda.
- Fasilitas berbeda (mis. charging di mal A vs mal B di kota yang sama).

Output HARUS berupa JSON objek saja, tanpa teks lain:
{
  "match": true,
  "confidence": 80,
  "reason": "Penjelasan singkat dalam bahasa Indonesia",
  "signals": ["nama mirip", "jarak dekat"]
}
PROMPT;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('lmstudio.base_url'), '/');
        $this->apiKey = (string) config('lmstudio.api_key');
        $this->model = (string) config('lmstudio.model');
        $this->timeout = (int) config('lmstudio.timeout', 120);
        $this->enabled = (bool) config('lmstudio.enabled', true);
    }

    /** Cek cepat server LM Studio (GET /models, timeout 3 detik). */
    public function isAvailable(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer '.$this->apiKey])
                ->timeout(3)
                ->get($this->baseUrl.'/models');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Klasifikasi sepasang stasiun.
     *
     * @param  array<string, mixed>  $payload  {pln_name, pln_addr, ..., esdm_*, esdm_distance_m, name_similarity_pct}
     * @return array{match: bool, confidence: int, reason: string, signals: array}
     *
     * @throws RuntimeException  bila server mati / status non-2xx / output tidak valid
     */
    public function classifyMatch(array $payload): array
    {
        $response = Http::withHeaders(['Authorization' => 'Bearer '.$this->apiKey])
            ->timeout($this->timeout)
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                ],
                'temperature' => 0,
            ]);

        if (! $response->successful()) {
            // Sertakan body error LM Studio (mis. "No models loaded", detail validasi)
            // supaya root cause terlihat di ai_reasoning.error — bukan hanya status code.
            $body = trim((string) $response->body());
            $bodyShort = mb_substr($body, 0, 300);

            throw new RuntimeException('LM Studio request gagal: '.$response->status().' '.$response->reason().($bodyShort !== '' ? ' — '.$bodyShort : ''));
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('LM Studio response kosong (choices[0].message.content).');
        }

        return $this->parse($content);
    }

    /**
     * Parse & validasi output model menjadi array shape yang diharapkan.
     * Toleran terhadap teks/fence di sekitar JSON.
     *
     * @throws RuntimeException  bila output bukan JSON valid
     */
    private function parse(string $content): array
    {
        $decoded = $this->extractJson($content);
        if ($decoded === null) {
            throw new RuntimeException('LM Studio output bukan JSON valid: '.mb_substr($content, 0, 200));
        }

        $match = isset($decoded['match']) ? filter_var($decoded['match'], FILTER_VALIDATE_BOOL) : false;
        $confidence = (int) round((float) ($decoded['confidence'] ?? 0));
        $confidence = max(0, min(100, $confidence));

        return [
            'match' => $match,
            'confidence' => $confidence,
            'reason' => trim((string) ($decoded['reason'] ?? '')),
            'signals' => is_array($decoded['signals'] ?? null) ? array_values($decoded['signals']) : [],
        ];
    }

    /** Ambil objek JSON pertama dari respons (strip fence/teks sekitar). */
    private function extractJson(string $content): ?array
    {
        $trimmed = trim($content);

        // Strip fence markdown ```json ... ```
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $trimmed, $m)) {
            $trimmed = trim($m[1]);
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Toleransi: model kadang membungkus dgn kalimat — ambil objek JSON pertama.
        if (preg_match('/\{[\s\S]*\}/', $trimmed, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
