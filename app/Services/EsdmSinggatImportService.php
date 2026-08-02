<?php

namespace App\Services;

use App\Models\EsdmSinggatSpbkluBattery;
use App\Models\EsdmSinggatSpbkluCabinet;
use App\Models\EsdmSinggatSpbkluStation;
use App\Models\EsdmSinggatSpkluConnector;
use App\Models\EsdmSinggatSpkluInstallation;
use App\Models\EsdmSinggatSpkluStation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Import data ESDM Singgat (gatrik.esdm.go.id) ke tabel esdm_singgat_*.
 *
 * Pipeline ini BERDIRI SENDIRI — tidak ada relasi ke spklu_locations,
 * spklu_charger_boxes, maupun spklu_scrape_raw. Tujuannya menyimpan data
 * mentah ESDM persis apa adanya untuk di-cleaning terpisah.
 *
 * Sumber: data/esdm_singgat_lokasi.json
 *   {
 *     "metadata": {...},
 *     "response": {
 *       "spklu":  [ {id, nama_stasiun, instalasi:[{konektor:[...]}], ...} ],
 *       "spbklu": [ {id, nama_stasiun, kabinet:[{baterai:[...]}], ...} ]
 *     }
 *   }
 *
 * img_konektor (base64 PNG) TIDAK disimpan di DB — diganti path file hasil
 * ekstrak. Hanya 7 gambar unik (1 per tipe plug); path menunjuk ke
 * public/storage/esdm/konektor_unique/{slug}.png.
 */
class EsdmSinggatImportService
{
    /** Tipe plug → path relatif terhadap public/. Konsisten dgn extraction script. */
    private function resolveKonektorImagePath(?string $namaKonektor): ?string
    {
        if (empty($namaKonektor)) {
            return null;
        }

        // Sama persis dgn slug Python: alnum dipertahankan, sisanya jadi '_', max 20 char
        $slug = Str::substr(preg_replace('/[^A-Za-z0-9]/', '_', $namaKonektor), 0, 20);

        return "storage/esdm/konektor_unique/{$slug}.png";
    }

    /**
     * Import dari file JSON.
     *
     * @param  string  $filePath  Path absolut/relatif ke esdm_singgat_lokasi.json.
     * @param  bool    $replaceExisting  Hapus data lama sebelum import (default true).
     * @param  bool    $withSpbklu  Import juga data SPBKLU (motor). Default true.
     * @return array  Statistik import.
     */
    public function importFromFile(string $filePath, bool $replaceExisting = true, bool $withSpbklu = true): array
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("File JSON tidak ditemukan: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (! is_array($data) || ! isset($data['response'])) {
            throw new InvalidArgumentException('Format JSON tidak valid: key "response" tidak ditemukan.');
        }

        return $this->importFromData($data, $replaceExisting, $withSpbklu);
    }

    /**
     * Import dari array hasil decode JSON.
     */
    public function importFromData(array $data, bool $replaceExisting = true, bool $withSpbklu = true): array
    {
        $batch = Str::uuid()->toString();

        return DB::connection('ev')->transaction(function () use ($data, $replaceExisting, $withSpbklu, $batch) {
            // ── SPKLU (mobil) ────────────────────────────────────────────────
            $spkluStats = $this->importSpklu($data['response']['spklu'] ?? [], $replaceExisting, $batch);

            // ── SPBKLU (baterai motor) ───────────────────────────────────────
            $spbkluStats = $withSpbklu
                ? $this->importSpbklu($data['response']['spbklu'] ?? [], $replaceExisting, $batch)
                : ['total' => 0, 'stations' => 0, 'cabinets' => 0, 'batteries' => 0, 'deleted_stations' => 0];

            return [
                'import_batch' => $batch,
                'spklu' => $spkluStats,
                'spbklu' => $spbkluStats,
            ];
        });
    }

    // ─── SPKLU ─────────────────────────────────────────────────────────────

    private function importSpklu(array $stations, bool $replace, string $batch): array
    {
        $deletedStations = 0;
        $deletedInstallations = 0;
        $deletedConnectors = 0;

        if ($replace) {
            // FK cascade akan membersihkan installations → connectors otomatis
            $deletedStations = EsdmSinggatSpkluStation::query()->delete();
        }

        $insertedStations = 0;
        $insertedInstallations = 0;
        $insertedConnectors = 0;

        foreach ($stations as $rec) {
            $esdmId = $rec['id'] ?? null;
            if ($esdmId === null) {
                continue;
            }

            $station = EsdmSinggatSpkluStation::create([
                'esdm_id' => $esdmId,
                'nama_stasiun' => $rec['nama_stasiun'] ?? '',
                'alamat_spklu' => $rec['alamat_spklu'] ?? null,
                'kode_provinsi' => isset($rec['kode_provinsi']) ? (string) $rec['kode_provinsi'] : null,
                'kode_kota' => isset($rec['kode_kota']) ? (string) $rec['kode_kota'] : null,
                'nama_badan_usaha' => $rec['nama_badan_usaha'] ?? null,
                'latitude_spklu_raw' => isset($rec['latitude_spklu']) ? (string) $rec['latitude_spklu'] : null,
                'longitude_spklu_raw' => isset($rec['longitude_spklu']) ? (string) $rec['longitude_spklu'] : null,
                'count_konektor' => (int) ($rec['count_konektor'] ?? 0),
                'estimasi' => isset($rec['estimasi']) ? (float) $rec['estimasi'] : null,
                'estimasi_menit' => isset($rec['estimasi_menit']) ? (float) $rec['estimasi_menit'] : null,
                'encrypt_id' => $rec['encrypt_id'] ?? null,
                'fasilitas' => $rec['fasilitas'] ?? null,
                'raw_payload' => $rec,
                'import_batch' => $batch,
            ]);
            $insertedStations++;

            foreach ($rec['instalasi'] ?? [] as $inst) {
                $installation = EsdmSinggatSpkluInstallation::create([
                    'esdm_id' => $inst['id'] ?? null,
                    'station_id' => $station->id,
                    'station_esdm_id' => isset($inst['spklu_lokasi_id']) ? (int) $inst['spklu_lokasi_id'] : null,
                    'nomor_identitas' => $inst['nomor_identitas'] ?? null,
                    'merek_mesin' => $inst['merek_mesin'] ?? null,
                    'nomor_seri_mesin' => $inst['nomor_seri_mesin'] ?? null,
                    'jenis_pengisian_spklu' => $inst['jenis_pengisian_spklu'] ?? null,
                    'harga_pengisian_raw' => isset($inst['harga_pengisian']) ? (string) $inst['harga_pengisian'] : null,
                    'harga_layanan_raw' => isset($inst['harga_layanan']) ? (string) $inst['harga_layanan'] : null,
                ]);
                $insertedInstallations++;

                foreach ($inst['konektor'] ?? [] as $kon) {
                    EsdmSinggatSpkluConnector::create([
                        'esdm_id' => $kon['id'] ?? null,
                        'installation_id' => $installation->id,
                        'installation_esdm_id' => isset($kon['spklu_mesin_id']) ? (int) $kon['spklu_mesin_id'] : null,
                        'nama_konektor' => $kon['nama_konektor'] ?? null,
                        'status' => $kon['status'] ?? null,
                        'status_konektor' => $kon['status_konektor'] ?? null,
                        'img_path' => $this->resolveKonektorImagePath($kon['nama_konektor'] ?? null),
                    ]);
                    $insertedConnectors++;
                }
            }
        }

        return [
            'total' => count($stations),
            'stations' => $insertedStations,
            'installations' => $insertedInstallations,
            'connectors' => $insertedConnectors,
            'deleted_stations' => $deletedStations,
        ];
    }

    // ─── SPBKLU ────────────────────────────────────────────────────────────

    private function importSpbklu(array $stations, bool $replace, string $batch): array
    {
        $deletedStations = 0;

        if ($replace) {
            $deletedStations = EsdmSinggatSpbkluStation::query()->delete();
        }

        $insertedStations = 0;
        $insertedCabinets = 0;
        $insertedBatteries = 0;

        foreach ($stations as $rec) {
            $esdmId = $rec['id'] ?? null;
            if ($esdmId === null) {
                continue;
            }

            $station = EsdmSinggatSpbkluStation::create([
                'esdm_id' => $esdmId,
                'nama_stasiun' => $rec['nama_stasiun'] ?? '',
                'alamat_spbklu' => $rec['alamat_spbklu'] ?? null,
                'kode_provinsi' => isset($rec['kode_provinsi']) ? (string) $rec['kode_provinsi'] : null,
                'kode_kota' => isset($rec['kode_kota']) ? (string) $rec['kode_kota'] : null,
                'nama_badan_usaha' => $rec['nama_badan_usaha'] ?? null,
                'nomor_identitas' => $rec['nomor_identitas'] ?? null,
                'latitude_spbklu_raw' => isset($rec['latitude_spbklu']) ? (string) $rec['latitude_spbklu'] : null,
                'longitude_spbklu_raw' => isset($rec['longitude_spbklu']) ? (string) $rec['longitude_spbklu'] : null,
                'count_battery' => (int) ($rec['count_battery'] ?? 0),
                'estimasi' => isset($rec['estimasi']) ? (float) $rec['estimasi'] : null,
                'estimasi_menit' => isset($rec['estimasi_menit']) ? (float) $rec['estimasi_menit'] : null,
                'encrypt_id' => $rec['encrypt_id'] ?? null,
                'raw_payload' => $rec,
                'import_batch' => $batch,
            ]);
            $insertedStations++;

            foreach ($rec['kabinet'] ?? [] as $kab) {
                $cabinet = EsdmSinggatSpbkluCabinet::create([
                    'esdm_id' => $kab['id'] ?? null,
                    'station_id' => $station->id,
                    'station_esdm_id' => isset($kab['spbklu_lokasi_id']) ? (int) $kab['spbklu_lokasi_id'] : null,
                    'merek_kabinet' => $kab['merek_kabinet'] ?? null,
                    'status_instalasi' => $kab['status_instalasi'] ?? null,
                    'kapasitas_raw' => isset($kab['kapasitas']) ? (string) $kab['kapasitas'] : null,
                    'harga_penukaran_baterai_raw' => isset($kab['harga_penukaran_baterai']) ? (string) $kab['harga_penukaran_baterai'] : null,
                ]);
                $insertedCabinets++;

                foreach ($kab['baterai'] ?? [] as $bat) {
                    EsdmSinggatSpbkluBattery::create([
                        'esdm_id' => $bat['id'] ?? null,
                        'cabinet_id' => $cabinet->id,
                        'cabinet_esdm_id' => isset($bat['spbklu_kabinet_id']) ? (int) $bat['spbklu_kabinet_id'] : null,
                        'merek_baterai' => $bat['merek_baterai'] ?? null,
                        'tipe_baterai' => $bat['tipe_baterai'] ?? null,
                        'kapasitas_baterai_raw' => isset($bat['kapasitas_baterai']) ? (string) $bat['kapasitas_baterai'] : null,
                        'status_baterai' => $bat['status_baterai'] ?? null,
                        'persentase_raw' => isset($bat['persentase']) ? (string) $bat['persentase'] : null,
                    ]);
                    $insertedBatteries++;
                }
            }
        }

        return [
            'total' => count($stations),
            'stations' => $insertedStations,
            'cabinets' => $insertedCabinets,
            'batteries' => $insertedBatteries,
            'deleted_stations' => $deletedStations,
        ];
    }
}
