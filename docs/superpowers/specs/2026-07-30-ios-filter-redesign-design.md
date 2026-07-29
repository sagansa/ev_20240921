# Redesign Filter SPKLU (iOS + Cross-Platform)

**Tanggal**: 2026-07-30
**Status**: Draft (menunggu review user)
**Scope**: Mobile (iOS + shared Kotlin; Android ikut manfaatkan shared)

## 1. Latar Belakang & Masalah

UI filter SPKLU di iOS (`FilterChipRowView.swift`, 447 baris) memiliki **repetisi data di banyak tempat** tanpa single source of truth. Setiap penambahan/perubahan provider atau opsi kecepatan mengharuskan edit 2-3 titik di iOS (plus 1 titik di Android untuk warna provider).

### Repetisi yang ditemukan

1. **Opsi kecepatan (type_charge) didefinisikan 3×** dengan data identik:
   - `typeLabel` switch (FilterChipRowView.swift:17-24): `medium`/`fast`/`ultra_fast`
   - Quick chips strip (baris ~110): "Fast DC", "Ultra Fast"
   - Modal `SpeedOptionRow` (baris ~330): Medium/Fast/Ultra Fast + subtitle

2. **Daftar provider didefinisikan 2×**:
   - Quick chips (hardcoded "PLN Mobile", "Voltron")
   - Modal `providers` array (12 provider dengan warna)

3. **Warna provider didefinisikan 3×** untuk provider yang sama:
   - Quick chips: `Color(red: 2/255, ...)` (PLN)
   - Modal array: warna identik diulang
   - Android `createCustomEvPinBitmapDescriptor` (SpkluMapScreen.kt:120): warna yang sama lagi → repetisi lintas-platform

4. **Styling 2 dropdown pill di-copy-paste**: Provider Pill & Speed Pill punya blok `.padding/.background/.foregroundColor/.clipShape/.overlay/.shadow` nyaris identik, hanya teks+warna yang beda.

5. **`showFilterSheet = true`** di-assign ke 3 tombol berbeda (dua dropdown + ikon slider).

### Akar masalah
Tidak ada single source of truth. Data filter (opsi kecepatan, daftar provider, warna provider) tersebar sebagai literal di banyak tempat.

## 2. Tujuan

- Hilangkan repetisi: tiap kategori data filter didefinisikan **sekali**.
- Konsistensi lintas-platform: iOS & Android baca source of truth yang sama (shared Kotlin).
- Menangani provider bertambah/berkurang **tanpa ubah kode aplikasi** (opsi provider dinamis dari data).
- Tetap memungkinkan warna kustom per provider via map statis dengan fallback otomatis.

## 3. Arsitektur

### 3.1 Prinsip: "Dinamis dari data + warna statis dengan fallback"

| Aspek | Sumber | Menangani provider baru? |
|---|---|---|
| **Opsi provider di filter** | Dinamis dari data aktual (`allLocations` unik, sorted) | ✅ Ya, otomatis muncul |
| **Warna provider** | Statis map di shared Kotlin + fallback warna hash | ⚠️ Pakai fallback sampai ditambah |
| **Opsi speed** | Statis di shared Kotlin | N/A (stabil: medium/fast/ultra_fast) |

### 3.2 Komponen baru

#### a. `shared/.../config/SpkluFilterConfig.kt` (source of truth)
Berisi definisi statis untuk speed options dan provider color map.

```kotlin
package com.ev.spklu.config

import kotlin.math.abs

/**
 * Single source of truth untuk konfigurasi filter SPKLU lintas-platform.
 *
 * - [ChargeSpeed]: opsi kecepatan (stabil, tidak sering berubah).
 * - [ProviderColorMap]: warna kustom untuk provider utama + fallback deterministik
 *   untuk provider baru yang belum terdaftar.
 */
object SpkluFilterConfig {

    /**
     * Opsi kecepatan charging. Urutan = urutan tampil.
     */
    val chargeSpeeds: List<ChargeSpeed> = listOf(
        ChargeSpeed(
            id = "medium",
            shortLabel = "Medium",
            fullLabel = "Medium (AC 22kW)",
            subtitle = "Pengisian standar AC 7kW - 22kW",
            colorRgb = ChargeSpeedColorRgb(59, 130, 246) // Blue
        ),
        ChargeSpeed(
            id = "fast",
            shortLabel = "Fast DC",
            fullLabel = "Fast (DC 50kW)",
            subtitle = "Pengisian cepat DC 25kW - 60kW",
            colorRgb = ChargeSpeedColorRgb(245, 158, 11) // Amber
        ),
        ChargeSpeed(
            id = "ultra_fast",
            shortLabel = "Ultra Fast",
            fullLabel = "Ultra Fast (DC 200kW+)",
            subtitle = "Pengisian ultra cepat DC 100kW+",
            colorRgb = ChargeSpeedColorRgb(16, 185, 129) // Green
        ),
    )

    /**
     * Warna kustom untuk provider utama (key = effectiveProviderName, case-insensitive match).
     * Provider baru yang tidak ada di map akan dapat warna fallback deterministik.
     */
    val providerColors: Map<String, Rgb> = mapOf(
        // key HARUS lowercase untuk matching
        "pln mobile" to Rgb(2, 136, 209),
        "voltron" to Rgb(123, 31, 162),
        "shell" to Rgb(251, 192, 45),
        "pertamina" to Rgb(211, 47, 47),
        "wuling" to Rgb(230, 81, 0),
        "hyundai" to Rgb(21, 101, 192),
        "starvo" to Rgb(104, 159, 56),
        "charge+" to Rgb(0, 150, 136),
        "casino" to Rgb(0, 172, 193),
        "dayagreen" to Rgb(76, 175, 80),
        "stroom" to Rgb(255, 152, 0),
        "toyota lexus" to Rgb(158, 158, 158),
    )

    /**
     * Warna untuk provider. Kalau ada di [providerColors] pakai itu,
     * kalau tidak, generate warna deterministik dari hash nama.
     */
    fun colorForProvider(name: String?): Rgb {
        val key = name?.trim()?.lowercase() ?: return Rgb(0, 191, 165) // teal default
        providerColors[key]?.let { return it }
        // Fallback deterministik: hash nama -> hue, S/L tetap
        val hash = abs(key.hashCode())
        return Rgb(hash % 256, (hash / 256) % 256, (hash / 65536) % 256)
    }
}

data class ChargeSpeed(
    val id: String,
    val shortLabel: String,
    val fullLabel: String,
    val subtitle: String,
    val colorRgb: ChargeSpeedColorRgb,
)

@Suppress("unused") // dipakai via Kotlin/Native interop
data class Rgb(val r: Int, val g: Int, val b: Int)

// Alias untuk kejelasan; tipenya sama
typealias ChargeSpeedColorRgb = Rgb
```

#### b. Komponen UI iOS yang di-refactor (`FilterChipRowView.swift`)

**Dipertahankan (struktur luar)**:
- `FilterChipRowView` dengan signature yang sama (tidak break pemanggil di `SpkluMapView.swift:298`).
- Dua bagian: compact 2-pill bar + quick chips strip + modal sheet.

**Yang diubah**:

1. **Opsi speed** dibaca dari `SpkluFilterConfig.shared.chargeSpeeds` (bukan switch literal). `typeLabel` dihitung dari lookup `chargeSpeeds.first { $0.id == selectedTypeCharge }`.

2. **Daftar provider** tidak lagi hardcoded. Di-generate dari data aktual:
   - `SpkluViewModel` expose `availableProviders: [String]` (unik dari `allLocations`, sorted).
   - Quick chips menampilkan top-N provider (mis. 2-3 provider dengan lokasi terbanyak) + tombol "Lainnya..." → modal.
   - Modal menampilkan semua provider dari `availableProviders` via `ForEach`.

3. **Warna provider** via `SpkluFilterConfig.shared.colorForProvider(name:)` (ter-expose ke Swift sebagai fungsi). Hilangkan array `providers: [(String, Color)]` literal.

4. **Ekstrak `FilterPillButton`** generik untuk menghilangkan duplikasi styling antara Provider Pill & Speed Pill:
   ```swift
   struct FilterPillButton: View {
       let icon: String
       let label: String
       let activeColor: Color?
       let isActive: Bool
       let action: () -> Void
       // ... satu implementasi styling, dipakai oleh kedua pill
   }
   ```

5. **Modal sheet** dipresentasikan dari satu titik (trigger tetap bisa dari banyak tombol, tapi presentation terpusat).

#### c. ViewModel iOS (`SpkluViewModel.swift`)

Tambah computed property:
```swift
var availableProviders: [String] {
    let names = allLocations.map { $0.effectiveProviderName }
    // urut by frekuensi (provider dgn lokasi terbanyak di depan) lalu alfabetis
    let counts = Dictionary(names.map { ($0, 1) }, uniquingKeysWith: +)
    return counts.keys.sorted { (a, b) -> Bool in
        if counts[a] != counts[b] { return counts[a]! > counts[b]! }
        return a < b
    }
}
```
Tidak ada perubahan pada `selectedTypeCharge`/`selectedProvider`/`filterLocations()`/`selectFilterType()`/`selectProviderFilter()` (logika filter tetap).

#### d. Android (bonus dari shared, opsional)

`SpkluMapScreen.kt:114-168` `createCustomEvPinBitmapDescriptor` saat ini hardcode `when (providerName.contains("pln")) {...}`. Dapat diubah membaca `SpkluFilterConfig.colorForProvider(providerName)` supaya warna marker & filter konsisten. **Bagian ini opsional** (lihat Out of Scope) — primary deliverable adalah iOS.

### 3.3 Alur data

```
allLocations (loaded)
       │
       ▼
availableProviders (unik, sorted by count)   ← dinamis
       │
       ├──► Quick chips (top-N)               ┐
       └──► Modal sheet (semua)               ├── baca warna dari
                                                │   SpkluFilterConfig.colorForProvider()
chargeSpeeds (SpkluFilterConfig.shared)         │   (statik + fallback)
       │                                        │
       ├──► typeLabel lookup                   │
       ├──► Speed pill                         │
       └──► Modal speed rows                   ┘
```

## 4. Rincian File yang Diubah

| File | Aksi | Ringkasan |
|---|---|---|
| `shared/src/commonMain/kotlin/com/ev/spklu/config/SpkluFilterConfig.kt` | **BUAT** | Source of truth: `chargeSpeeds`, `providerColors`, `colorForProvider()` |
| `iosApp/.../Views/FilterChipRowView.swift` | **REWRITE** | Baca dari shared; ekstrak `FilterPillButton`; hapus literal provider/speed; provider dari `availableProviders` |
| `iosApp/.../SpkluViewModel.swift` | **EDIT** | Tambah `availableProviders` computed property |
| `androidApp/.../SpkluMapScreen.kt` | **EDIT (opsional)** | `createCustomEvPinBitmapDescriptor` pakai `SpkluFilterConfig.colorForProvider()` |

**Tidak diubah**: `SpkluMapView.swift` (signature `FilterChipRowView` dipertahankan), `EvTheme.swift`, logika filter di ViewModel, backend.

## 5. Pertimbangan Interop Kotlin → Swift

- Object Kotlin `SpkluFilterConfig` ter-expose sebagai `SpkluFilterConfig.shared` di Swift (konvensi Kotlin/Native).
- Data class `ChargeSpeed` & `Rgb` ter-expose sebagai struct Swift dengan property.
- Fungsi `colorForProvider(name: String?): Rgb` ter-expose; `Rgb(r,g,b)` dipetakan ke `Color(.sRGB, red:green:blue:)` di helper Swift extension:
  ```swift
  extension Color {
      init(_ rgb: Rgb) {
          self.init(.sRGB, red: Double(rgb.r)/255, green: Double(rgb.g)/255, blue: Double(rgb.b)/255)
      }
  }
  ```
- Daftar `chargeSpeeds: List<ChargeSpeed>` ter-expose sebagai `[ChargeSpeed]` di Swift, dapat di-`ForEach`.

## 6. Out of Scope

- Mengubah logika filter backend atau query API.
- Mengubah tata letak visual filter (posisi, ukuran chip) — hanya menghilangkan repetisi, bukan redesain visual.
- Membuat opsi provider benar-benar dari API `/meta/filters` (dipilih pendekatan data-lokal dinamis; API metadata tidak dipakai untuk provider).
- Wajib mengubah Android (opsional; primary deliverable iOS).
- Pekerjaan terkait provider logo HVT (issue terpisah).

## 7. Verifikasi

1. **Build shared framework**: `./gradlew :shared:assembleDebug` (Android) + build iOS tidak error.
2. **iOS build**: kompilasi `FilterChipRowView.swift` & `SpkluViewModel.swift` tanpa error.
3. **Fungsional**:
   - Filter speed: pilih Medium/Fast/Ultra Fast → marker & list ter-filter benar (logika filter tidak diubah).
   - Filter provider: modal menampilkan provider aktual dari data (bukan hardcoded 12); provider baru muncul otomatis.
   - Warna: provider dikenali dapat warna kustom; provider tak dikenal dapat warna fallback deterministik (konsisten antar run).
   - Quick chips tetap berfungsi (toggle).
4. **Tidak ada regressi**: tampilan filter secara visual sama dengan sebelumnya (warna, ukuran, behavior toggle).
5. **Repetisi terhapus**: grep literal warna provider di FilterChipRowView → 0 (semua lewat config).

## 8. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Interop Kotlin/Native untuk `List<ChargeSpeed>` bermasalah di Swift | Pakai pola yang sudah terbukti (DTO lain sudah ter-expose); verifikasi dengan build awal |
| Warna fallback deterministik jelek secara estetika | Fallback pakai hash sederhana; kalau tidak memuaskan, dapat di-tweak di config saja (1 tempat) |
| `availableProviders` kosong saat data belum load | Modal/quick chips tampilkan state kosong atau "Semua" saja; tidak crash |
| Signature `FilterChipRowView` berubah break pemanggil | Dipertahankan identik; verifikasi `SpkluMapView.swift:298` tetap kompilasi |
