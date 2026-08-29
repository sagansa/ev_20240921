<?php

namespace App\Services;

use App\Support\VehicleCategories;

/**
 * Penugasan kategori & ukuran kendaraan (level MODEL) + derivasi POWERTRAIN
 * dari FUEL — untuk membersihkan/merampingkan CONNECTING CSV GAIKINDO.
 *
 * Sumber penugasan (urutan prioritas):
 *  1. Override spesifik brand (BRAND|MODEL) — utk nama model ambigu antar
 *     brand (mis. "Ranger" = pickup FORD tapi truk berat UD TRUCKS).
 *  2. Kamus MODEL (case-insensitive).
 *  3. Aturan pola nama (kode truk HINO/ISUZU/UD/FUSO/Scania/MB bus, dsb.).
 *  Selain itu: category null + confidence "low" → masuk file review manusia.
 *
 * POWERTRAIN dipakai apa adanya bila sudah valid (BEV/PHEV/HEV/ICE); bila
 * tidak, derivasi dari FUEL; bila FUEL kosong/rusak, kamus model BEV.
 */
class VehicleCategoryAssigner
{
    /** @var array<string, string> MODEL → "Category" atau "Category|Size". */
    protected const MODEL_MAP = [
        // AION / Hyptec
        'AION UT' => 'Hatchback|Small', 'UT' => 'Hatchback|Small',
        'AION V' => 'SUV|Small', 'V' => 'SUV|Small',
        'AION Y' => 'SUV|Small', 'Y' => 'SUV|Small',
        'ES' => 'Sedan|Medium',
        'HYPTEC HT' => 'SUV|Medium',
        // Audi
        'A4' => 'Sedan|Medium', 'A5' => 'Sedan|Medium', 'A6' => 'Sedan|Large',
        'A8L' => 'Sedan|Large', 'Q3' => 'SUV|Small', 'Q5' => 'SUV|Medium',
        'Q7' => 'SUV|Large', 'Q8' => 'SUV|Large',
        'RS4' => 'Sedan|Medium', 'RS5' => 'Sport',
        // BAIC
        'BJ-30' => 'SUV|Small', 'BJ-80' => 'SUV|Large', 'BJ40' => 'Off-Road',
        'X-55' => 'SUV|Small', 'X55' => 'SUV|Small', 'X55 II' => 'SUV|Small',
        // BMW
        '220I' => 'Sedan|Small', '320I' => 'Sedan|Medium', '330I' => 'Sedan|Medium',
        '430I' => 'Sedan|Medium', '520I' => 'Sedan|Large', '735I' => 'Sedan|Large',
        'M135I' => 'Hatchback|Small', 'M2' => 'Sport', 'M3' => 'Sport',
        'M4' => 'Sport', 'M5' => 'Sedan|Large', 'M8' => 'Sport',
        'SERI 2' => 'Sedan|Small', 'SERI 3' => 'Sedan|Medium',
        'SERI 4' => 'Sedan|Medium', 'SERI 5' => 'Sedan|Large',
        'SERI 7' => 'Sedan|Large', 'SERI 8' => 'Sport',
        'X1' => 'SUV|Small', 'X2' => 'SUV|Small', 'X3' => 'SUV|Medium',
        'X4' => 'SUV|Medium', 'X5' => 'SUV|Large', 'X6' => 'SUV|Large',
        'X7' => 'SUV|Large', 'Z4' => 'Sport',
        'I4' => 'Sedan|Medium', 'I5' => 'Sedan|Large', 'I7' => 'Sedan|Large',
        'IX' => 'SUV|Large', 'IX1' => 'SUV|Small', 'IX2' => 'SUV|Small',
        'IX3' => 'SUV|Medium',
        'XM' => 'Sport',
        // BYD
        'ATTO 1' => 'City Car', 'ATTO 3' => 'SUV|Small',
        'DOLPHIN' => 'Hatchback|Small', 'M6' => 'MPV|Medium',
        'SEAL' => 'Sedan|Medium', 'SEALION 7' => 'SUV|Medium',
        'E6' => 'MPV|Medium',
        // Changan Deepal / Lumin
        'DEEPAL S05' => 'SUV|Medium', 'DEEPAL S07' => 'SUV|Medium',
        'LUMIN' => 'City Car',
        // Chery
        'GT' => 'SUV|Medium', 'J6' => 'Off-Road', 'J6T' => 'Pickup',
        'OMODA E5' => 'SUV|Small',
        'TIGGO' => 'SUV|Small', 'TIGGO 5X' => 'SUV|Small',
        'TIGGO 7' => 'SUV|Small', 'TIGGO 8' => 'SUV|Medium',
        'TIGGO 8 PRO' => 'SUV|Medium', 'TIGGO 9' => 'SUV|Large',
        'TIGGO CROSS' => 'SUV|Small', 'ICAR 03' => 'SUV|Small',
        // Citroen
        'C3' => 'Hatchback|Small', 'C3 AIRCROSS' => 'SUV|Small',
        'C5 AIRCROSS' => 'SUV|Medium', 'EC3' => 'City Car',
        'E-C3' => 'City Car', 'Ë-C4' => 'Hatchback|Medium',
        // Daihatsu
        'AYLA' => 'City Car', 'GRAN MAX' => 'Van/Minibus',
        'LUXIO' => 'Van/Minibus', 'ROCKY' => 'SUV|Small',
        'SIGRA' => 'MPV|Small', 'SIRION' => 'Hatchback|Small',
        'TERIOS' => 'SUV|Small', 'XENIA' => 'MPV|Small',
        // Denza / DFSK / Farizon
        'D9' => 'MPV|Large',
        'E5 PLUS' => 'SUV|Small', 'SERES 3' => 'SUV|Small',
        'SERES E5' => 'SUV|Medium', 'SUPER CAB' => 'Pickup',
        'GELORA' => 'Van/Minibus',
        'GLORY' => 'MPV|Medium', 'GLORY 560' => 'SUV|Medium',
        'GLORY 580' => 'SUV|Medium', 'GLORY I-AUTO' => 'MPV|Medium',
        'SV' => 'Van/Minibus',
        // FORD / Geely / GWM
        'EVEREST' => 'SUV|Large', 'MUSTANG' => 'Sport', 'RANGER' => 'Pickup',
        'EX2' => 'SUV|Small', 'EX5' => 'SUV|Medium',
        'STARRAY' => 'SUV|Medium',
        'TANK 300' => 'Off-Road', 'TANK 500' => 'Off-Road',
        'H6' => 'SUV|Medium', 'HAVAL H6' => 'SUV|Medium',
        'JOLION' => 'SUV|Small', 'ORA 03' => 'Hatchback|Small',
        'ORA 07' => 'Sedan|Medium',
        // HINO (kode truk dipetakan; detail di rules())
        'DURO 136MD' => 'Truk Ringan', 'DUTRO' => 'Truk Ringan',
        'R SERIES' => 'Truk Berat', 'FC BUS' => 'Bus',
        // Honda
        'ACCORD' => 'Sedan|Medium', 'BR-V' => 'SUV|Small',
        'BRIO' => 'City Car', 'CR-V' => 'SUV|Medium', 'CRV' => 'SUV|Medium',
        'CITY' => 'Sedan|Small', 'CIVIC' => 'Sedan|Medium',
        'HR-V' => 'SUV|Small', 'MOBILIO' => 'MPV|Small',
        'ODYSSEY' => 'MPV|Large', 'PRELUDE' => 'Sport',
        'VELOZ' => 'MPV|Small', 'WR-V' => 'SUV|Small',
        'E:N1' => 'SUV|Small', 'STEP WGN' => 'MPV|Medium',
        // Hyundai
        'CRETA' => 'SUV|Small', 'GV70' => 'SUV|Medium', 'GV80' => 'SUV|Large',
        'GENESIS G80' => 'Sedan|Large', 'GRAND I10' => 'City Car',
        'H-100' => 'Van/Minibus', 'IONIQ' => 'Sedan|Medium',
        'IONIQ 5' => 'SUV|Medium', 'IONIQ 6' => 'Sedan|Medium',
        'IONIQ 9' => 'SUV|Large', 'KONA EV' => 'SUV|Small',
        'PALISADE' => 'SUV|Large', 'SANTA FE' => 'SUV|Medium',
        'STARGAZER' => 'MPV|Small', 'STARGAZER X' => 'MPV|Small',
        'STARIA' => 'MPV|Large', 'TUCSON' => 'SUV|Medium',
        'VENUE' => 'SUV|Small',
        // Isuzu
        'D-MAX' => 'Pickup', 'MU-X' => 'SUV|Large',
        // Jaecoo / Jeep / Jetour
        'J5' => 'SUV|Small', 'J7' => 'SUV|Medium', 'J8' => 'SUV|Large',
        'GRAND CHEROKEE' => 'SUV|Large', 'WRANGLER' => 'Off-Road',
        'DASHING' => 'SUV|Small', 'T1' => 'SUV|Small', 'T2' => 'SUV|Small',
        'X70P' => 'SUV|Medium',
        // KIA
        'CARENS' => 'MPV|Medium', 'CARNIVAL' => 'MPV|Large',
        'EV6' => 'SUV|Medium', 'EV9' => 'SUV|Large',
        'GRAND SEDONA' => 'MPV|Large', 'K-2700' => 'Truk Ringan',
        'K-2700' => 'Truk Ringan', 'SELTOS' => 'SUV|Small',
        'SONET' => 'SUV|Small',
        // Lexus
        'LBX' => 'SUV|Small', 'LC' => 'Sport', 'LM' => 'MPV|Large',
        'LS' => 'Sedan|Large', 'LX' => 'SUV|Large', 'NX' => 'SUV|Medium',
        'RX' => 'SUV|Medium', 'RZ' => 'SUV|Medium', 'UX' => 'SUV|Small',
        // Maxus / Mazda
        'MIFA 7' => 'MPV|Large', 'MIFA 9' => 'MPV|Large',
        'CX-3' => 'SUV|Small', 'CX-30' => 'SUV|Small', 'CX-5' => 'SUV|Medium',
        'CX-60' => 'SUV|Medium', 'CX-8' => 'SUV|Large', 'CX-9' => 'SUV|Large',
        'MX-3' => 'Hatchback|Small', 'MX-30' => 'SUV|Small',
        'MX-5' => 'Sport', 'MAZDA 2' => 'Hatchback|Small',
        'MAZDA 3' => 'Hatchback|Medium', 'MAZDA 6' => 'Sedan|Large',
        // Mercedes-Benz (kode bus/truk di rules())
        'A CLASS' => 'Hatchback|Small', 'A-CLASS' => 'Hatchback|Small',
        'AMG GT' => 'Sport', 'C-CLASS' => 'Sedan|Medium',
        'CLA' => 'Sedan|Medium', 'CLE' => 'Sedan|Medium',
        'CLS' => 'Sedan|Medium', 'E-CLASS' => 'Sedan|Large',
        'EQA' => 'SUV|Small', 'EQB' => 'SUV|Small', 'EQE' => 'Sedan|Large',
        'EQS' => 'Sedan|Large', 'EQS SUV' => 'SUV|Large',
        'G-CLASS' => 'Off-Road', 'GLA' => 'SUV|Small', 'GLB' => 'SUV|Small',
        'GLC' => 'SUV|Medium', 'GLE' => 'SUV|Large', 'GLS' => 'SUV|Large',
        'GT' => 'Sport', 'MAYBACH S' => 'Sedan|Large',
        'SPRINTER' => 'Van/Minibus', 'V-CLASS' => 'Van/Minibus',
        'VITO' => 'Van/Minibus', 'SL' => 'Sport',
        // MINI
        'ACEMAN' => 'SUV|Small', 'CLUBMAN' => 'Hatchback|Medium',
        'COOPER' => 'Hatchback|Small', 'COOPER CLUBMAN' => 'Hatchback|Medium',
        'COOPER COUNTRYMAN' => 'SUV|Small', 'COUNTRYMAN' => 'SUV|Small',
        'GP' => 'Sport', 'HATCH' => 'Hatchback|Small', 'JCW' => 'Sport',
        'JOHN COOPER WORKS' => 'Sport',
        'JOHN COOPER WORKS CONVERTIBLE' => 'Sport',
        // Mitsubishi
        'DESTINATOR' => 'SUV|Medium', 'ECLIPSE CROSS' => 'SUV|Medium',
        'PAJERO SPORT' => 'SUV|Large', 'OUTLANDER' => 'SUV|Medium',
        'TRITON' => 'Pickup', 'XFORCE' => 'SUV|Small',
        'XPANDER' => 'MPV|Medium', 'XPANDER CROSS' => 'SUV|Small',
        'L100' => 'Van/Minibus', 'L300' => 'Van/Minibus',
        // MG / Morris Garage
        '4 EV' => 'Sedan|Medium', '5 GT' => 'Hatchback|Medium',
        'CYBERSTER' => 'Sport', 'EP' => 'SUV|Small', 'HS' => 'SUV|Medium',
        'S5 EV' => 'SUV|Small', 'VS' => 'SUV|Medium', 'ZS' => 'SUV|Small',
        'MG' => 'SUV|Small',
        // Neta / Nissan
        'NETA V' => 'SUV|Small', 'NETA V-II' => 'SUV|Small',
        'NETA X' => 'SUV|Small',
        'LEAF' => 'Hatchback|Medium', 'LIVINA' => 'MPV|Small',
        'MAGNITE' => 'SUV|Small', 'NAVARA' => 'Pickup',
        'TERRA' => 'SUV|Large', 'X-TRAIL' => 'SUV|Medium',
        'KICKS' => 'SUV|Small',
        // Peugeot / Polytron / Scania
        '2008' => 'SUV|Small', '3008' => 'SUV|Medium', '5008' => 'SUV|Large',
        'G3' => 'MPV|Small',
        'G-SERIES' => 'Truk Berat', 'G SERIES' => 'Truk Berat',
        'K-SERIES' => 'Bus', 'P-SERIES' => 'Truk Berat',
        'R-SERIES' => 'Truk Berat',
        // Seres / Subaru
        'E1' => 'Hatchback|Small', 'E502' => 'MPV|Medium',
        'CROSSTREK' => 'SUV|Small', 'FORESTER' => 'SUV|Medium',
        'OUTBACK' => 'SUV|Large', 'BRZ' => 'Sport', 'WRX' => 'Sedan|Medium',
        'XV' => 'SUV|Small',
        // Suzuki
        'APV' => 'Van/Minibus', 'ALPHA HYBRID' => 'Hatchback|Small',
        'BALENO' => 'Hatchback|Small', 'CARRY' => 'Pickup',
        'ERTIGA' => 'MPV|Small', 'FRONX' => 'SUV|Small',
        'GRAND VITARA' => 'SUV|Medium', 'IGNIS' => 'City Car',
        'JIMNY' => 'Off-Road', 'S-PRESSO' => 'City Car',
        'SX4 S-CROSS' => 'SUV|Small', 'XL7' => 'SUV|Small',
        // TATA / Toyota
        'INTRA' => 'Pickup', 'SUPER ACE' => 'Pickup', 'XENON' => 'Pickup',
        'LP' => 'Truk Ringan', 'LPT' => 'Truk Berat', 'PRIMA' => 'Truk Berat',
        '86' => 'Sport', 'AGYA' => 'City Car', 'ALPHARD' => 'MPV|Large',
        'AVANZA' => 'MPV|Small', 'BZ4X' => 'SUV|Medium',
        'C+POD' => 'City Car', 'C-HR' => 'Crossover|Small',
        'CALYA' => 'MPV|Small', 'CAMRY' => 'Sedan|Medium',
        'COROLLA ALTIS' => 'Sedan|Medium', 'COROLLA' => 'Hatchback|Small',
        'COROLLA CROSS' => 'SUV|Small', 'CROWN' => 'Sedan|Large',
        'DYNA' => 'Truk Ringan', 'FORTUNER' => 'SUV|Large',
        'HI-ACE' => 'Van/Minibus', 'HILUX' => 'Pickup',
        'INNOVA' => 'MPV|Medium', 'LAND CRUISER' => 'Off-Road',
        'MIRAI' => 'Sedan|Medium', 'PRIUS' => 'Hatchback|Medium',
        'RAIZE' => 'SUV|Small', 'RUSH' => 'SUV|Small',
        'SIENTA' => 'MPV|Small', 'SUPRA' => 'Sport',
        'URBAN CRUISER EV' => 'SUV|Small', 'VELLFIRE' => 'MPV|Large',
        'VEL' => 'MPV|Small', 'VIOS' => 'Sedan|Small',
        'VOXY' => 'MPV|Medium', 'YARIS' => 'Hatchback|Small',
        'YARIS CROSS' => 'SUV|Small', 'RAV4' => 'SUV|Medium',
        // UD Trucks / VinFast / VW / Volvo
        'CDE' => 'Truk Berat', 'CGE' => 'Truk Berat', 'CKE' => 'Truk Berat',
        'CQE' => 'Truk Berat', 'CWE' => 'Truk Berat', 'GDE' => 'Truk Berat',
        'GKE' => 'Truk Berat', 'GWE' => 'Truk Berat', 'RKE' => 'Truk Berat',
        'SKE' => 'Truk Berat',
        'LIMO GREEN' => 'Van/Minibus', 'MPV7' => 'MPV|Medium',
        'VF 3' => 'SUV|Small', 'VF 5' => 'SUV|Small', 'VF 6' => 'SUV|Small',
        'VF 7' => 'SUV|Medium', 'VF E34' => 'SUV|Medium',
        'ID.BUZZ' => 'Van/Minibus', 'POLO' => 'Hatchback|Small',
        'T-CROSS' => 'SUV|Small', 'TIGUAN' => 'SUV|Medium',
        'C40' => 'SUV|Small', 'EC40' => 'SUV|Small', 'ES90' => 'Sedan|Medium',
        'EX30' => 'SUV|Small', 'EX40' => 'SUV|Small', 'EX90' => 'SUV|Large',
        'XC40' => 'SUV|Small', 'XC60' => 'SUV|Medium', 'XC90' => 'SUV|Large',
        // Wuling
        'AIR EV' => 'City Car', 'AIRA EV' => 'City Car',
        'ALMAZ' => 'SUV|Medium', 'ALVEZ' => 'SUV|Small',
        'BINGUO' => 'City Car', 'BINGUO EV' => 'City Car',
        'CLOUD' => 'MPV|Medium', 'CONFERO' => 'MPV|Small',
        'CORTEZ' => 'MPV|Medium', 'FORMO' => 'Van/Minibus',
        'MITRA EV' => 'Van/Minibus',
        // Xpeng
        'G6' => 'SUV|Medium', 'X9' => 'MPV|Large',
    ];

    /** Override spesifik brand utk nama model ambigu antar brand. */
    protected const BRAND_MODEL_MAP = [
        'AION|ES' => 'Sedan|Medium',
        'LEXUS ES|ES' => 'Sedan|Large',
        'HINO|ZS' => 'Truk Berat',
        'MORRIS GARAGE|ZS' => 'SUV|Small',
        'HINO|A' => 'Truk Berat',
        'HINO|AK' => 'Truk Berat',
        'HINO|GB' => 'Truk Ringan',
        'BAIC|T1' => 'Pickup',
        'JETOUR|T1' => 'SUV|Small',
        'KIA|K-SERIES' => 'Truk Ringan',
        'SCANIA|K-SERIES' => 'Bus',
        'FORD|RANGER' => 'Pickup',
        'UD TRUCKS|RANGER' => 'Truk Berat',
        'TOYOTA GR|YARIS' => 'Hatchback|Small',
        'TOYOTA GR|COROLLA' => 'Hatchback|Small',
        'TOYOTA|RAV4' => 'SUV|Medium',
        'CHERY|GT' => 'SUV|Medium',
        'FARIZON SV|SV' => 'Van/Minibus',
        'MINI|GP' => 'Sport',
    ];

    /** Model BEV dikenal — fallback POWERTRAIN bila FUEL rusak/kosong. */
    protected const KNOWN_BEV_MODELS = [
        'ATTO 1', 'ATTO 3', 'DOLPHIN', 'SEAL', 'SEALION 7', 'M6', 'E6',
        'AIR EV', 'AIRA EV', 'BINGUO', 'BINGUO EV', 'ALMAZ', 'ALVEZ',
        'CLOUD', 'CONFERO', 'CORTEZ', 'FORMO', 'MITRA EV',
        'OMODA E5', 'ICAR 03', 'E5 PLUS', 'SERES 3', 'SERES E5',
        'E1', 'E502', 'ACEMAN', 'DEEPAL S05', 'DEEPAL S07', 'LUMIN',
        'NETA V', 'NETA V-II', 'NETA X', 'LEAF', 'KICKS',
        'IONIQ 5', 'IONIQ 6', 'IONIQ 9', 'KONA EV', 'EV6', 'EV9',
        'BZ4X', 'C+POD', 'URBAN CRUISER EV',
        '4 EV', 'S5 EV', 'ORA 03', 'ORA 07', 'I4', 'I5', 'I7',
        'IX', 'IX1', 'IX2', 'IX3', 'EQA', 'EQB', 'EQE', 'EQS',
        'G6', 'X9', 'VF 3', 'VF 5', 'VF 6', 'VF 7', 'VF E34',
        'LIMO GREEN', 'MPV7', 'ID.BUZZ', 'C40', 'EC40', 'ES90',
        'EX30', 'EX40', 'EX90', 'E:N1', 'MIFA 7', 'MIFA 9', 'D9',
        'AION UT', 'AION V', 'AION Y', 'HYPTEC HT', 'STARRAY', 'EX2', 'EX5',
    ];

    /**
     * Penugasan kategori/ukuran/powertrain untuk satu baris CSV.
     *
     * @return array{category: ?string, size: ?string, powertrain: ?string, confidence: string}
     */
    public function assign(
        ?string $brand,
        ?string $model,
        ?string $type = null,
        ?string $fuel = null,
        ?string $powertrain = null
    ): array {
        $key = strtoupper(trim((string) $model));
        $brandKey = strtoupper(trim((string) $brand));

        $hit = self::BRAND_MODEL_MAP[$brandKey.'|'.$key]
            ?? self::MODEL_MAP[$key]
            ?? null;

        [$category, $size] = $hit !== null ? explode('|', $hit.'|') : [null, null];
        $confidence = $hit !== null ? 'exact' : 'low';

        if ($hit === null) {
            $rule = $this->ruleMatch($brandKey, $key);
            if ($rule !== null) {
                [$category, $size, $confidence] = $rule;
            }
        }

        // Ukuran hanya valid untuk kategori ber-ukuran.
        if (! in_array($category, VehicleCategories::SIZABLE, true)) {
            $size = null;
        }

        return [
            'category' => $category,
            'size' => $size,
            'powertrain' => $this->resolvePowertrain($key, $fuel, $powertrain),
            'confidence' => $confidence,
        ];
    }

    /**
     * Aturan pola nama truk/bus (kode seri pabrikan) — "rule" confidence.
     *
     * @return array{0: string, 1: ?string, 2: string}|null
     */
    protected function ruleMatch(string $brand, string $model): ?array
    {
        // Bus eksplisit.
        if (str_contains($model, 'BUS')) {
            return ['Bus', null, 'rule'];
        }

        // Traktor/mixer/dump/chassis — kode komersial berat.
        if (preg_match('/TRACTOR|MIXER|DUMP|SELF LOADER|CHASSIS/', $model)) {
            return ['Truk Berat', null, 'rule'];
        }

        // HINO: 115/130/136* light duty — cek SEBELUM blanket brand rule
        // (semua brand varian HINO lain = heavy duty).
        if (preg_match('/^(115|130|136)/', $model)) {
            return ['Truk Ringan', null, 'rule'];
        }

        if ($brand === 'HINO' || str_starts_with($brand, 'HINO ')) {
            return ['Truk Berat', null, 'rule'];
        }

        if (preg_match('/^(RK|RM|SG|ZY|GB|SV)\d*/', $model)) {
            return ['Truk Berat', null, 'rule'];
        }

        // Isuzu: N-series light; F/G/CYZ heavy.
        if (preg_match('/^(NLR|NMR|NPS|NQR|NPR|ELF)/', $model)) {
            return ['Truk Ringan', null, 'rule'];
        }

        if (preg_match('/^(FRR|FTR|FVM|FVR|FVZ|GVR|GVZ|GXZ|CYZ|PHR)/', $model)) {
            return ['Truk Berat', null, 'rule'];
        }

        // UD Trucks (semua barisnya truk berat — brand memang begitu).
        if (str_starts_with($brand, 'UD TRUCKS')
            && preg_match('/^[CGRS][DEKQW]{2}$/', $model)) {
            return ['Truk Berat', null, 'rule'];
        }

        // FUSO / Canter — light duty.
        if (str_contains($brand, 'FUSO') || str_starts_with($model, 'FUSO')) {
            return ['Truk Ringan', null, 'rule'];
        }

        // Mercedes-Benz: O/OF/OH = bus chassis; Actros/Arocs/Axor = berat.
        if (str_contains($brand, 'MERCEDES') || str_contains($brand, 'MB ')) {
            if (preg_match('/^O[\sC]?|^OH/', $model)) {
                return ['Bus', null, 'rule'];
            }

            if (preg_match('/^(ACTROS|AROCS|AXOR)/', $model)) {
                return ['Truk Berat', null, 'rule'];
            }
        }

        // Scania / TATA Prima-LPT / FAW HD-FD: heavy.
        if (preg_match('/^(SCANIA|TATA)/', $brand)
            && preg_match('/^(G|P|R)-?SERIES|^PRIMA|^LPT|^LP\d*/', $model)) {
            return ['Truk Berat', null, 'rule'];
        }

        if (preg_match('/^(FB\d+|FD\d*|HD\d+)/', $model)) {
            return ['Truk Berat', null, 'rule'];
        }

        return null;
    }

    /**
     * POWERTRAIN final: nilai valid → pakai; FUEL map; kamus BEV; null.
     */
    protected function resolvePowertrain(string $modelKey, ?string $fuel, ?string $powertrain): ?string
    {
        $pt = strtoupper(trim((string) $powertrain));

        if (in_array($pt, ['BEV', 'PHEV', 'HEV', 'ICE'], true)) {
            return $pt;
        }

        $fuelKey = strtoupper(trim((string) $fuel));

        $mapped = match (true) {
            in_array($fuelKey, ['G', 'B', 'BENZIN', 'GASOLINE', 'PETROL'], true) => 'ICE',
            in_array($fuelKey, ['D', 'DIESEL'], true) => 'ICE',
            in_array($fuelKey, ['EV', 'BEV'], true) => 'BEV',
            in_array($fuelKey, ['HEV', 'HYBRID'], true) => 'HEV',
            $fuelKey === 'PHEV' => 'PHEV',
            default => null,
        };

        if ($mapped !== null) {
            return $mapped;
        }

        return in_array($modelKey, self::KNOWN_BEV_MODELS, true) ? 'BEV' : null;
    }
}
