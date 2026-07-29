# Redesign Filter SPKLU (Cross-Platform: iOS + Android)

**Tanggal**: 2026-07-30
**Status**: Draft (menunggu review user)
**Scope**: Mobile — iOS + Android + shared Kotlin

## 1. Latar Belakang & Masalah

UI filter SPKLU di kedua platform memiliki **repetisi parah**: banyak trigger yang membuka menu yang sama, dan data filter di-hardcode berkali-kali.

### Repetisi yang ditemukan

**Repetisi trigger (di setiap platform)**: saat ini ada **3 elemen UI** yang semua membuka sheet filter yang sama:
- iOS (`FilterChipRowView.swift`): Provider Pill + Speed Pill + slider icon → semua `showFilterSheet = true`. Plus quick chips strip dengan chip "Semua/Fast/Ultra/PLN/Voltron/Lainnya".
- Android (`SpkluFilterRow.kt`): Provider Pill + Speed Pill + FilterList icon → semua `onOpenFilterSheet()`.

**Repetisi data (di setiap platform)**:
1. Opsi kecepatan (type_charge) didefinisikan 3×: `typeLabel` switch, quick chips, dan modal rows.
2. Daftar provider didefinisikan 2×: quick chips (hardcoded) + modal array.
3. Warna provider didefinisikan 3×: quick chips, modal array, dan di Android `createCustomEvPinBitmapDescriptor` (lintas-platform).

**Repetisi lintas-platform**: daftar provider, opsi speed, dan warna provider duplikat antara iOS dan Android.

### Akar masalah
Tidak ada single source of truth, dan terlalu banyak trigger menuju fungsi yang sama.

## 2. Keputusan Desain

User memutuskan: **1 tombol filter saja di kanan yang memunculkan menu (sheet modal)**. Ini menghilangkan:
- Semua trigger ganda (provider pill, speed pill, quick chips strip).
- Bar filter 3-elemen dihapus seluruhnya.

Diganti dengan **satu icon button filter** yang ditempatkan di sebelah kanan top bar (area search bar).

Prinsip source of truth: **"Dinamis dari data + warna statis dengan fallback"**

| Aspek | Sumber | Menangani provider baru? |
|---|---|---|
| Opsi provider di filter | Dinamis dari data aktual (unik, sorted by count) | ✅ Otomatis |
| Warna provider | Statis map di shared Kotlin + fallback deterministik | ⚠️ Fallback sampai ditambah |
| Opsi speed | Statis di shared Kotlin (medium/fast/ultra_fast) | N/A (stabil) |

## 3. Arsitektur

### 3.1 Komponen baru: `shared/.../config/SpkluFilterConfig.kt`

Source of truth lintas-platform.

```kotlin
package com.ev.spklu.config

import kotlin.math.abs

object SpkluFilterConfig {

    /** Opsi kecepatan charging. Urutan = urutan tampil. */
    val chargeSpeeds: List<ChargeSpeed> = listOf(
        ChargeSpeed("medium", "Medium", "Medium (AC 22kW)", "Pengisian standar AC 7kW - 22kW", Rgb(59, 130, 246)),
        ChargeSpeed("fast", "Fast DC", "Fast (DC 50kW)", "Pengisian cepat DC 25kW - 60kW", Rgb(245, 158, 11)),
        ChargeSpeed("ultra_fast", "Ultra Fast", "Ultra Fast (DC 200kW+)", "Pengisian ultra cepat DC 100kW+", Rgb(16, 185, 129)),
    )

    /** Warna kustom untuk provider utama. Key HARUS lowercase. */
    val providerColors: Map<String, Rgb> = mapOf(
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

    /** Warna provider: kustom jika ada, fallback deterministik dari hash nama. */
    fun colorForProvider(name: String?): Rgb {
        val key = name?.trim()?.lowercase() ?: return Rgb(0, 191, 165)
        providerColors[key]?.let { return it }
        val hash = abs(key.hashCode())
        return Rgb(hash % 256, (hash / 256) % 256, (hash / 65536) % 256)
    }
}

data class ChargeSpeed(
    val id: String,
    val shortLabel: String,
    val fullLabel: String,
    val subtitle: String,
    val colorRgb: Rgb,
)

@Suppress("unused") // dipakai via Kotlin/Native interop
data class Rgb(val r: Int, val g: Int, val b: Int)
```

### 3.2 iOS: `FilterChipRowView.swift` → dihapus, diganti

**Dihapus seluruhnya**: `FilterChipRowView`, `QuickChipView`, `SpkluFilterModalSheetView`, quick chips strip, 2 dropdown pill, slider icon, literal provider/speed, `typeLabel` switch. (447 baris → turun drastis.)

**Baru**: `FilterMenuButton` — satu icon button yang membuka modal sheet.

```swift
struct FilterMenuButton: View {
    let selectedTypeCharge: String?
    let selectedProvider: String?
    let availableProviders: [String]
    let onApplyFilter: (String?, String?) -> Void

    @State private var showSheet = false

    private var isFilterActive: Bool {
        selectedTypeCharge != nil || selectedProvider != nil
    }

    var body: some View {
        Button { showSheet = true } label: {
            ZStack {
                Image(systemName: "line.3.horizontal.decrease.circle.fill")
                    .font(.system(size: 18, weight: .semibold))
                    .foregroundColor(isFilterActive ? .black : .white)
                    .padding(12)
                    .background(isFilterActive ? EvTheme.primaryGreen : Color.white.opacity(0.15))
                    .background(.ultraThinMaterial)
                    .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
                    .shadow(color: .black.opacity(0.15), radius: 6, x: 0, y: 3)
                if isFilterActive {
                    Circle().fill(Color.orange).frame(width: 8, height: 8).offset(x: 13, y: -13)
                }
            }
        }
        .sheet(isPresented: $showSheet) {
            SpkluFilterSheet(/* baca dari shared config + availableProviders */)
                .presentationDetents([.medium, .large])
                .presentationDragIndicator(.visible)
        }
    }
}
```

`SpkluFilterSheet` (modal) hanya satu, membaca:
- Speed options dari `SpkluFilterConfig.shared.chargeSpeeds`
- Provider options dari `availableProviders` (dinamis dari ViewModel)
- Warna dari `SpkluFilterConfig.shared.colorForProvider(name:)`

### 3.3 Android: `SpkluFilterRow.kt` → disederhanakan

**Dihapus**: `SpkluFilterBar` (3-elemen), quick chips, literal `providersList`, literal `types`. Modal `SpkluFilterModalSheet` dipertahankan tapi di-refactor membaca shared config + `availableProviders`.

**Baru**: `FilterIconButton` composable (1 ikon) menggantikan `SpkluFilterBar`.

### 3.4 ViewModel (kedua platform): expose `availableProviders`

```kotlin
// Android SpkluViewModel
val availableProviders: List<String> get() =
    allLocations.map { it.effectiveProviderName }
        .groupingBy { it }.eachCount()
        .entries.sortedWith(compareByDescending<MutableMap.MutableEntry<String, Int>> { it.value }.thenBy { it.key })
        .map { it.key }
```

```swift
// iOS SpkluViewModel — computed property equivalen (unik, sorted by count)
var availableProviders: [String] { /* sama logic */ }
```

### 3.5 Placement "di kanan"

Bar atas saat ini: `[search bar (weight 1f)] [refresh icon]`.
Tombol filter baru ditempatkan **di kanan search bar**, bersama refresh. Layout jadi:
`[search bar] [filter icon] [refresh icon]`

- iOS: filter icon di trailing search row (sudah ada di `SpkluMapView` top bar).
- Android: tambah `FilterIconButton` ke Row di `SpkluMapScreen.kt:380`, hapus pemanggilan `SpkluFilterBar` di baris 421.

### 3.6 Alur data

```
[search bar]  [filter icon] → sheet  →  [refresh]
                    │
                    ▼
        SpkluFilterSheet (modal)
            ├─ speed: SpkluFilterConfig.chargeSpeeds (statik)
            └─ provider: availableProviders (dinamis dari data)
                              └─ warna: SpkluFilterConfig.colorForProvider()
```

## 4. Rincian File

| File | Aksi | Ringkasan |
|---|---|---|
| `shared/.../config/SpkluFilterConfig.kt` | **BUAT** | Source of truth: `chargeSpeeds`, `providerColors`, `colorForProvider()` |
| `iosApp/.../Views/FilterChipRowView.swift` | **HAPUS + BUAT FilterMenuButton.swift** | Hapus 447 baris; ganti 1 icon button + 1 sheet (baca shared) |
| `iosApp/.../SpkluViewModel.swift` | **EDIT** | Tambah `availableProviders` |
| `iosApp/.../Views/SpkluMapView.swift` | **EDIT** | Ganti `FilterChipRowView(...)` → `FilterMenuButton(...)` di top bar |
| `androidApp/.../components/SpkluFilterRow.kt` | **REWRITE** | Hapus `SpkluFilterBar`; buat `FilterIconButton`; refactor modal baca shared config |
| `androidApp/.../screens/SpkluMapScreen.kt` | **EDIT** | Hapus `SpkluFilterBar(...)` baris 421; tambah `FilterIconButton` ke top Row baris 380 |
| `androidApp/.../ui/SpkluViewModel.kt` | **EDIT** | Tambah `availableProviders` |

**Tidak diubah**: logika filter (`filterLocations`/`selectFilterType`/`selectProviderFilter`), backend, signature `onApplyFilter` di modal (tetap `(type?, provider?) -> Void`).

## 5. Pertimbangan Interop Kotlin → Swift

- `SpkluFilterConfig` → `SpkluFilterConfig.shared` di Swift.
- `ChargeSpeed` & `Rgb` → struct Swift dengan property (`r`/`g`/`b`, `id`/`fullLabel`/dll).
- `colorForProvider(name:)` ter-expose; helper Swift:
  ```swift
  extension Color { init(_ rgb: Rgb) { self.init(.sRGB, red: Double(rgb.r)/255, green: Double(rgb.g)/255, blue: Double(rgb.b)/255) } }
  ```
- `chargeSpeeds: List<ChargeSpeed>` → `[ChargeSpeed]`, dapat `ForEach`.

## 6. Out of Scope

- Mengubah logika filter backend / query API.
- Menggunakan API `/meta/filters` (provider dari data lokal).
- Mengubah marker pin Android `createCustomEvPinBitmapDescriptor` (warna provider di marker tetap hardcode saat ini — bisa follow-up terpisah; fokus sekarang filter UI). *Catatan: idealnya juga pakai config, tapi dipisah agar scope terkendali.*
- Issue terpisah: provider logo HVT.

## 7. Verifikasi

1. **Build**: `./gradlew :shared:assembleDebug` + build iOS + build Android, semua tanpa error.
2. **Tidak ada repetisi trigger**: hanya 1 ikon filter di kedua platform.
3. **Fungsional**:
   - Tap ikon filter → sheet muncul dengan section Provider (dinamis dari data) + Speed (statik).
   - Pilih provider/speed → apply → marker & list ter-filter benar.
   - Reset → kembali "Semua".
   - Provider baru otomatis muncul; provider tak dikenal dapat warna fallback deterministik.
4. **Tidak ada regressi**: search bar & refresh tetap berfungsi; tampilan modal familiar.
5. **Grep**: literal warna/provider/speed di view file → 0 (semua via config).

## 8. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Interop `List<ChargeSpeed>` bermasalah di Swift | Pola DTO sudah terbukti (SpkluLocationDto dll); verifikasi build awal |
| `availableProviders` kosong saat belum load | Sheet tampilkan state "Semua" saja; tidak crash |
| Pemanggil `SpkluFilterBar`/`FilterChipRowView` di screen lain | Grep dulu semua referensi sebelum hapus; sesuaikan |
| Layout top bar sempit setelah +1 ikon | Pakai ukuran ikon konsisten; verifikasi visual di kedua platform |
