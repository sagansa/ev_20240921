# Redesign Filter SPKLU (Cross-Platform) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the duplicated multi-trigger filter UI on iOS and Android with a single filter icon button that opens a modal sheet, sourced from a shared Kotlin `SpkluFilterConfig` single-source-of-truth.

**Architecture:** A new `SpkluFilterConfig` object in the KMP `shared` module exposes charge-speed options, a provider color map with a deterministic fallback, and a provider-list builder. Both platforms consume it. The 3-element filter bar (provider pill + speed pill + filter icon) and the quick-chips strip are deleted; one `FilterIconButton` is added to the existing top search bar. Provider options in the modal are derived dynamically from loaded locations so new/deleted providers appear without an app update.

**Tech Stack:** Kotlin Multiplatform (common module), Kotlin/Native interop to Swift, Jetpack Compose (Android), SwiftUI (iOS). Tests via `kotlin.test` in `commonTest`.

## Global Constraints

- Shared module framework name is `shared`; iOS imports via `import shared`. Kotlin objects expose as `<Name>.shared` in Swift.
- Colors are RGB triplets (`Rgb(r,g,b)`); both platforms convert to their native Color type via a thin helper. Do NOT use platform color literals in view files.
- `effectiveProviderName` is an extension property on `SpkluLocationDto` (defined in `shared/.../model/SpkluModels.kt:49`). Provider matching throughout the app is case-insensitive `contains`.
- Do not change backend, filter query logic, or the `onApplyFilter(typeCharge: String?, provider: String?) -> Unit` callback signature.
- Indonesian UI labels remain in Indonesian (existing convention).
- Every task ends with a green build/test + a commit.

## File Structure

**Create:**
- `mobile/shared/src/commonMain/kotlin/com/ev/spklu/config/SpkluFilterConfig.kt` — source of truth: `ChargeSpeed`, `Rgb`, `chargeSpeeds`, `providerColors`, `colorForProvider()`.
- `mobile/shared/src/commonTest/kotlin/com/ev/spklu/config/SpkluFilterConfigTest.kt` — unit tests for config.
- `mobile/iosApp/iosApp/Views/FilterMenuButton.swift` — single icon button + modal sheet (replaces deleted file).
- `mobile/iosApp/iosApp/Views/SpkluFilterSheet.swift` — the modal sheet content (provider + speed sections), split for focus.
- `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/FilterIconButton.kt` — single Compose icon button.
- `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterSheetContent.kt` — the Compose modal sheet content, split out from the old `SpkluFilterRow.kt`.

**Modify:**
- `mobile/shared/src/commonMain/kotlin/com/ev/spklu/repository/SpkluRepository.kt` — add `availableProviders` builder over unfiltered cache.
- `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/SpkluViewModel.kt` — expose `availableProviders` from repository.
- `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/screens/SpkluMapScreen.kt` — remove `SpkluFilterBar` call; add `FilterIconButton` to the top search Row.
- `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/theme/Color.kt` (or existing theme file) — add `Color(_rgb: Rgb)`-equivalent helper if needed (see Task 6).
- `mobile/iosApp/iosApp/SpkluViewModel.swift` — add `availableProviders` computed property.
- `mobile/iosApp/iosApp/Views/SpkluMapView.swift` — replace `FilterChipRowView(...)` with `FilterMenuButton(...)` in the top bar.

**Delete:**
- `mobile/iosApp/iosApp/Views/FilterChipRowView.swift` — entire file (447 lines).
- `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterRow.kt` — entire file (replaced by `FilterIconButton.kt` + `SpkluFilterSheetContent.kt`).

---

### Task 1: Create shared `SpkluFilterConfig` (source of truth)

**Files:**
- Create: `mobile/shared/src/commonMain/kotlin/com/ev/spklu/config/SpkluFilterConfig.kt`
- Test: `mobile/shared/src/commonTest/kotlin/com/ev/spklu/config/SpkluFilterConfigTest.kt`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `data class Rgb(val r: Int, val g: Int, val b: Int)`
  - `data class ChargeSpeed(val id: String, val shortLabel: String, val fullLabel: String, val subtitle: String, val colorRgb: Rgb)`
  - `object SpkluFilterConfig { val chargeSpeeds: List<ChargeSpeed>; val providerColors: Map<String, Rgb>; fun colorForProvider(name: String?): Rgb }`
  - `fun List<SpkluLocationDto>.uniqueProviderNames(): List<String>` extension (added in this file or `SpkluModels.kt`) — dedup + sort by count desc then name asc. Used by Task 2/5/7.

- [ ] **Step 1: Write the failing test**

Create `mobile/shared/src/commonTest/kotlin/com/ev/spklu/config/SpkluFilterConfigTest.kt`:

```kotlin
package com.ev.spklu.config

import com.ev.spklu.model.SpkluLocationDto
import com.ev.spklu.model.uniqueProviderNames
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertTrue

class SpkluFilterConfigTest {

    @Test
    fun chargeSpeedsContainsTheThreeExpectedIdsInOrder() {
        val ids = SpkluFilterConfig.chargeSpeeds.map { it.id }
        assertEquals(listOf("medium", "fast", "ultra_fast"), ids)
    }

    @Test
    fun chargeSpeedsHaveNonEmptyLabelsAndColor() {
        SpkluFilterConfig.chargeSpeeds.forEach { speed ->
            assertTrue(speed.shortLabel.isNotBlank(), "shortLabel blank for ${speed.id}")
            assertTrue(speed.fullLabel.isNotBlank(), "fullLabel blank for ${speed.id}")
            assertTrue(speed.subtitle.isNotBlank(), "subtitle blank for ${speed.id}")
            assertNotNull(speed.colorRgb)
        }
    }

    @Test
    fun colorForProviderReturnsCustomColorForKnownProviderCaseInsensitive() {
        val lower = SpkluFilterConfig.colorForProvider("pln mobile")
        val mixed = SpkluFilterConfig.colorForProvider("PLN Mobile")
        assertEquals(lower, mixed)
    }

    @Test
    fun colorForProviderReturnsDefaultTealForNullName() {
        val rgb = SpkluFilterConfig.colorForProvider(null)
        assertEquals(Rgb(0, 191, 165), rgb)
    }

    @Test
    fun colorForProviderReturnsDeterministicColorForUnknownProvider() {
        val first = SpkluFilterConfig.colorForProvider("BrandNewProvider XYZ")
        val second = SpkluFilterConfig.colorForProvider("BrandNewProvider XYZ")
        assertEquals(first, second, "fallback color must be deterministic")
    }

    @Test
    fun uniqueProviderNamesDedupesAndSortsByCountThenName() {
        val locations = listOf(
            dto(providerName = "Voltron"),
            dto(providerName = "PLN Mobile"),
            dto(providerName = "PLN Mobile"),
            dto(providerName = null), // falls back to default
        )
        val names = locations.uniqueProviderNames()
        // PLN Mobile appears twice -> first; then Voltron; default last (alphabetic among ties/count)
        assertEquals("PLN Mobile", names.first())
        assertTrue(names.contains("Voltron"))
    }

    private fun dto(providerName: String?): SpkluLocationDto = SpkluLocationDto(
        id = 0L,
        namaLokasi = "x",
        typeCharge = null,
        providerName = providerName,
        provider = null,
    )
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd mobile && ./gradlew :shared:allTests --tests "com.ev.spklu.config.SpkluFilterConfigTest"`
Expected: FAIL — unresolved reference `SpkluFilterConfig`, `Rgb`, `uniqueProviderNames`.

- [ ] **Step 3: Create the config file**

Create `mobile/shared/src/commonMain/kotlin/com/ev/spklu/config/SpkluFilterConfig.kt`:

```kotlin
package com.ev.spklu.config

import com.ev.spklu.model.SpkluLocationDto
import com.ev.spklu.model.effectiveProviderName
import kotlin.math.abs

/**
 * Single source of truth for SPKLU filter configuration shared across iOS & Android.
 */
object SpkluFilterConfig {

    /** Charging speed options. Order defines display order. Stable set. */
    val chargeSpeeds: List<ChargeSpeed> = listOf(
        ChargeSpeed(
            id = "medium",
            shortLabel = "Medium",
            fullLabel = "Medium (AC 22kW)",
            subtitle = "Pengisian standar AC 7kW - 22kW",
            colorRgb = Rgb(59, 130, 246),
        ),
        ChargeSpeed(
            id = "fast",
            shortLabel = "Fast DC",
            fullLabel = "Fast (DC 50kW)",
            subtitle = "Pengisian cepat DC 25kW - 60kW",
            colorRgb = Rgb(245, 158, 11),
        ),
        ChargeSpeed(
            id = "ultra_fast",
            shortLabel = "Ultra Fast",
            fullLabel = "Ultra Fast (DC 200kW+)",
            subtitle = "Pengisian ultra cepat DC 100kW+",
            colorRgb = Rgb(16, 185, 129),
        ),
    )

    /**
     * Custom colors for known providers. Keys MUST be lowercase for case-insensitive matching.
     */
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

    /**
     * Provider color: custom if known, else a deterministic fallback derived from the name hash,
     * else a default teal for null/blank names.
     */
    fun colorForProvider(name: String?): Rgb {
        val key = name?.trim()?.lowercase()
        if (key.isNullOrBlank()) return Rgb(0, 191, 165)
        providerColors[key]?.let { return it }
        val hash = abs(key.hashCode())
        return Rgb(hash % 256, (hash / 256) % 256, (hash / 65536) % 256)
    }
}

/** RGB triplet. Exposed to Swift/Compose via interop. */
data class Rgb(val r: Int, val g: Int, val b: Int)

/** One charging-speed option in the filter. */
data class ChargeSpeed(
    val id: String,
    val shortLabel: String,
    val fullLabel: String,
    val subtitle: String,
    val colorRgb: Rgb,
)

/**
 * Unique provider names from a list of locations, sorted by occurrence count desc then name asc,
 * so the most common providers surface first. New providers appear automatically without code changes.
 */
fun List<SpkluLocationDto>.uniqueProviderNames(): List<String> =
    this.map { it.effectiveProviderName }
        .groupingBy { it }
        .eachCount()
        .entries
        .sortedWith(compareByDescending<Map.Entry<String, Int>> { it.value }.thenBy { it.key })
        .map { it.key }
```

Note: `uniqueProviderNames` is defined in package `com.ev.spklu.config`, but it extends `SpkluLocationDto`. Import the extension in the test as `com.ev.spklu.config.uniqueProviderNames` (NOT `com.ev.spklu.model.uniqueProviderNames` as the test stub above shows — **correct the test import** to `com.ev.spklu.config.uniqueProviderNames`).

- [ ] **Step 4: Run test to verify it passes**

Run: `cd mobile && ./gradlew :shared:allTests --tests "com.ev.spklu.config.SpkluFilterConfigTest"`
Expected: PASS (6 tests).

- [ ] **Step 5: Build shared framework to confirm interop compiles**

Run: `cd mobile && ./gradlew :shared:assembleDebug`
Expected: BUILD SUCCESSFUL.

- [ ] **Step 6: Commit**

```bash
cd mobile
git add shared/src/commonMain/kotlin/com/ev/spklu/config/SpkluFilterConfig.kt \
        shared/src/commonTest/kotlin/com/ev/spklu/config/SpkluFilterConfigTest.kt
git commit -m "feat(shared): add SpkluFilterConfig as cross-platform filter source of truth"
```

---

### Task 2: Expose `availableProviders` from the shared repository

**Why here:** Both ViewModels need the unfiltered provider list. Computing it once in the shared `SpkluRepository` (over its unfiltered `_cachedLocations`) avoids duplication and avoids the iOS/Android filter-state divergence (Android refilters via API, iOS holds `allLocations`). The repository cache is the single correct source.

**Files:**
- Modify: `mobile/shared/src/commonMain/kotlin/com/ev/spklu/repository/SpkluRepository.kt`

**Interfaces:**
- Consumes: `SpkluFilterConfig`/`uniqueProviderNames` from Task 1; `_cachedLocations: MutableList<SpkluLocationDto>`.
- Produces: `SpkluRepository.availableProviders: List<String>` (computed over `_cachedLocations`, unfiltered).

- [ ] **Step 1: Write the failing test**

Create `mobile/shared/src/commonTest/kotlin/com/ev/spklu/repository/SpkluRepositoryProvidersTest.kt`:

```kotlin
package com.ev.spklu.repository

import com.ev.spklu.model.SpkluLocationDto
import kotlin.test.Test
import kotlin.test.assertEquals

class SpkluRepositoryProvidersTest {

    @Test
    fun availableProvidersReflectsSeededCacheUnfiltered() {
        val repo = SpkluRepository()
        repo.seedOfflineCache(listOf(
            dto(1, "PLN Mobile"), dto(2, "PLN Mobile"), dto(3, "Voltron"),
        ))
        // even though cache is the unfiltered source, availableProviders lists both
        assertEquals(listOf("PLN Mobile", "Voltron"), repo.availableProviders)
    }

    @Test
    fun availableProvidersIsEmptyWhenCacheEmpty() {
        val repo = SpkluRepository()
        assertEquals(emptyList(), repo.availableProviders)
    }

    private fun dto(id: Long, provider: String?): SpkluLocationDto = SpkluLocationDto(
        id = id, namaLokasi = "x", typeCharge = null, providerName = provider, provider = null,
    )
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd mobile && ./gradlew :shared:allTests --tests "com.ev.spklu.repository.SpkluRepositoryProvidersTest"`
Expected: FAIL — unresolved reference `availableProviders`.

- [ ] **Step 3: Add the property to `SpkluRepository`**

In `SpkluRepository.kt`, add the import at the top:
```kotlin
import com.ev.spklu.config.uniqueProviderNames
```
Then add this property to the class (e.g. right after `_cachedLocations` declaration, before `seedOfflineCache`):
```kotlin
/** Provider names present in the unfiltered cache, sorted by count then name. Empty if cache empty. */
val availableProviders: List<String>
    get() = _cachedLocations.toList().uniqueProviderNames()
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd mobile && ./gradlew :shared:allTests --tests "com.ev.spklu.repository.SpkluRepositoryProvidersTest"`
Expected: PASS (2 tests).

- [ ] **Step 5: Re-run all shared tests**

Run: `cd mobile && ./gradlew :shared:allTests`
Expected: BUILD SUCCESSFUL (all tests, including Task 1's).

- [ ] **Step 6: Commit**

```bash
cd mobile
git add shared/src/commonMain/kotlin/com/ev/spklu/repository/SpkluRepository.kt \
        shared/src/commonTest/kotlin/com/ev/spklu/repository/SpkluRepositoryProvidersTest.kt
git commit -m "feat(shared): expose availableProviders from SpkluRepository cache"
```

---

### Task 3: iOS — replace `FilterChipRowView` with `FilterMenuButton` + `SpkluFilterSheet`

**Files:**
- Create: `mobile/iosApp/iosApp/Views/FilterMenuButton.swift`
- Create: `mobile/iosApp/iosApp/Views/SpkluFilterSheet.swift`
- Delete: `mobile/iosApp/iosApp/Views/FilterChipRowView.swift`

**Interfaces:**
- Consumes: `SpkluFilterConfig.shared` (chargeSpeeds, colorForProvider), `[String]` availableProviders from ViewModel (Task 5), `EvTheme.primaryGreen`.
- Produces: `FilterMenuButton` with signature:
  ```swift
  FilterMenuButton(
      selectedTypeCharge: String?,
      selectedProvider: String?,
      availableProviders: [String],
      onApplyFilter: @escaping (String?, String?) -> Void
  )
  ```
  This replaces the old `FilterChipRowView(...)` call site (handled in Task 4).

- [ ] **Step 1: Create the Rgb→Color helper + modal sheet**

Create `mobile/iosApp/iosApp/Views/SpkluFilterSheet.swift`:

```swift
import SwiftUI
import shared

/// Converts the shared RGB triplet into SwiftUI Color.
extension Color {
    init(_ rgb: Rgb) {
        self.init(.sRGB,
                  red: Double(rgb.r) / 255.0,
                  green: Double(rgb.g) / 255.0,
                  blue: Double(rgb.b) / 255.0)
    }
}

/// Single modal filter sheet: Provider (dynamic) + Charging Speed (static from shared config).
struct SpkluFilterSheet: View {
    @Environment(\.dismiss) private var dismiss

    let selectedTypeCharge: String?
    let selectedProvider: String?
    let availableProviders: [String]
    let onApplyFilter: (String?, String?) -> Void

    @State private var tempTypeCharge: String?
    @State private var tempProvider: String?

    init(selectedTypeCharge: String?, selectedProvider: String?, availableProviders: [String], onApplyFilter: @escaping (String?, String?) -> Void) {
        self.selectedTypeCharge = selectedTypeCharge
        self.selectedProvider = selectedProvider
        self.availableProviders = availableProviders
        self.onApplyFilter = onApplyFilter
        _tempTypeCharge = State(initialValue: selectedTypeCharge)
        _tempProvider = State(initialValue: selectedProvider)
    }

    private let columns = [GridItem(.adaptive(minimum: 105), spacing: 10)]

    var body: some View {
        NavigationView {
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    // MARK: Provider section (dynamic)
                    VStack(alignment: .leading, spacing: 10) {
                        Text("⚡ Provider / Operator Pengelola")
                            .font(.headline)
                            .foregroundColor(.primary)

                        LazyVGrid(columns: columns, spacing: 10) {
                            Button(action: { tempProvider = nil }) {
                                providerChipLabel("Semua", isSelected: tempProvider == nil, color: EvTheme.primaryGreen)
                            }
                            ForEach(availableProviders, id: \.self) { prov in
                                let isSelected = tempProvider == prov
                                Button(action: { tempProvider = isSelected ? nil : prov }) {
                                    providerChipLabel(prov, isSelected: isSelected,
                                                      color: Color(SpkluFilterConfig.shared.colorForProvider(name: prov)))
                                }
                            }
                        }
                    }

                    Divider()

                    // MARK: Speed section (static from shared config)
                    VStack(alignment: .leading, spacing: 10) {
                        Text("🔌 Kecepatan Charging")
                            .font(.headline)
                            .foregroundColor(.primary)

                        VStack(spacing: 8) {
                            speedRow(nil, title: "Semua Kecepatan", subtitle: "Tampilkan semua tipe pengisian", color: EvTheme.primaryGreen)
                            ForEach(SpkluFilterConfig.shared.chargeSpeeds, id: \.id) { speed in
                                speedRow(speed.id, title: speed.fullLabel, subtitle: speed.subtitle, color: Color(speed.colorRgb))
                            }
                        }
                    }

                    Spacer().frame(height: 10)

                    Button(action: {
                        onApplyFilter(tempTypeCharge, tempProvider)
                        dismiss()
                    }) {
                        Text("Terapkan Filter SPKLU")
                            .font(.headline).fontWeight(.bold)
                            .foregroundColor(.black)
                            .frame(maxWidth: .infinity).padding(.vertical, 14)
                            .background(EvTheme.primaryGreen)
                            .cornerRadius(14)
                    }
                }
                .padding(20)
            }
            .navigationTitle("Filter SPKLU")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    if tempProvider != nil || tempTypeCharge != nil {
                        Button("Reset") { tempProvider = nil; tempTypeCharge = nil }
                            .foregroundColor(.red)
                    }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button(action: { dismiss() }) {
                        Image(systemName: "xmark.circle.fill").foregroundColor(.gray)
                    }
                }
            }
        }
    }

    private func providerChipLabel(_ text: String, isSelected: Bool, color: Color) -> some View {
        HStack {
            Text(text).font(.system(size: 13, weight: isSelected ? .bold : .medium)).lineLimit(1)
            if isSelected { Spacer(); Image(systemName: "checkmark").font(.caption) }
        }
        .padding(.horizontal, 12).padding(.vertical, 10)
        .background(isSelected ? color : Color(.secondarySystemBackground))
        .foregroundColor(isSelected ? .white : .primary)
        .cornerRadius(12)
    }

    private func speedRow(_ key: String?, title: String, subtitle: String, color: Color) -> some View {
        let isSelected = tempTypeCharge == key
        return Button(action: { tempTypeCharge = key }) {
            HStack {
                VStack(alignment: .leading, spacing: 2) {
                    Text(title).font(.system(size: 14, weight: isSelected ? .bold : .semibold))
                    Text(subtitle).font(.caption)
                        .foregroundColor(isSelected ? .white.opacity(0.8) : .secondary)
                }
                Spacer()
                if isSelected { Image(systemName: "checkmark.circle.fill").font(.title3) }
            }
            .padding(12)
            .background(isSelected ? color : Color(.secondarySystemBackground))
            .foregroundColor(isSelected ? .white : .primary)
            .cornerRadius(12)
        }
    }
}
```

- [ ] **Step 2: Create the single filter button**

Create `mobile/iosApp/iosApp/Views/FilterMenuButton.swift`:

```swift
import SwiftUI

/// One filter icon button placed in the top bar. Opens the modal sheet.
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
                    .overlay(
                        RoundedRectangle(cornerRadius: 14, style: .continuous)
                            .stroke(isFilterActive ? EvTheme.primaryGreen : Color.white.opacity(0.2), lineWidth: 1)
                    )
                    .shadow(color: Color.black.opacity(0.15), radius: 6, x: 0, y: 3)
                if isFilterActive {
                    Circle()
                        .fill(Color.orange)
                        .frame(width: 8, height: 8)
                        .offset(x: 13, y: -13)
                }
            }
        }
        .sheet(isPresented: $showSheet) {
            SpkluFilterSheet(
                selectedTypeCharge: selectedTypeCharge,
                selectedProvider: selectedProvider,
                availableProviders: availableProviders,
                onApplyFilter: onApplyFilter
            )
            .presentationDetents([.medium, .large])
            .presentationDragIndicator(.visible)
        }
    }
}
```

- [ ] **Step 3: Delete the old file**

Delete: `mobile/iosApp/iosApp/Views/FilterChipRowView.swift`

- [ ] **Step 4: Build the iOS target to confirm the new files compile**

Run (from `iosApp` dir, or via the scheme): `xcodebuild -workspace iosApp.xcworkspace -scheme iosApp -sdk iphonesimulator build`
Expected: The two new files compile. NOTE: `SpkluMapView.swift` still references the deleted `FilterChipRowView`, so the build is EXPECTED to fail at that file until Task 4. Confirm the only errors are the `FilterChipRowView` references in `SpkluMapView.swift`, not errors in the new files.

- [ ] **Step 5: Commit**

```bash
cd mobile
git add iosApp/iosApp/Views/FilterMenuButton.swift \
        iosApp/iosApp/Views/SpkluFilterSheet.swift
git rm iosApp/iosApp/Views/FilterChipRowView.swift
git commit -m "feat(ios): replace FilterChipRowView with single FilterMenuButton + shared-driven sheet"
```

---

### Task 4: iOS — wire `FilterMenuButton` into `SpkluMapView`

**Files:**
- Modify: `mobile/iosApp/iosApp/Views/SpkluMapView.swift` (around line 298 — the old `FilterChipRowView(...)` call)

**Interfaces:**
- Consumes: `FilterMenuButton` (Task 3), `viewModel.availableProviders` (Task 5), `viewModel.selectFilterType`/`selectProviderFilter`.
- Produces: a building top bar that compiles.

- [ ] **Step 1: Locate the call site and surrounding top bar**

Open `SpkluMapView.swift`. Around line 298 there is:
```swift
FilterChipRowView(
    selectedTypeCharge: viewModel.selectedTypeCharge,
    selectedProvider: viewModel.selectedProvider,
    onSelectFilter: { viewModel.selectFilterType($0) },
    onSelectProvider: { viewModel.selectProviderFilter($0) }
)
```
Find the enclosing top bar HStack that holds the search field (read the ~30 lines above line 298 to see the exact layout — it is a `VStack`/`HStack` with a search `TextField` and refresh button).

- [ ] **Step 2: Replace the call with FilterMenuButton placed in the search row**

Delete the `FilterChipRowView(...)` block. Inside the search-row `HStack` (the one with the search field and refresh button), add the `FilterMenuButton` between the search field and the refresh button so the row reads: `[search field] [FilterMenuButton] [refresh button]`. The button uses `onApplyFilter` which maps to both ViewModel selectors:

```swift
FilterMenuButton(
    selectedTypeCharge: viewModel.selectedTypeCharge,
    selectedProvider: viewModel.selectedProvider,
    availableProviders: viewModel.availableProviders,
    onApplyFilter: { type, provider in
        viewModel.selectFilterType(type)
        viewModel.selectProviderFilter(provider)
    }
)
```

Note: `selectFilterType(_:)` and `selectProviderFilter(_:)` are toggle-style (passing the same value again clears it). Because the modal uses temp state and calls `onApplyFilter` once with the final selection, passing `type`/`provider` directly sets the absolute value — which is the desired behavior here (the sheet's Reset sets both to nil and Apply commits that). This matches the old modal's `onApplyFilter` semantics.

- [ ] **Step 3: Build iOS and confirm success**

Run: `xcodebuild -workspace iosApp.xcworkspace -scheme iosApp -sdk iphonesimulator build`
Expected: BUILD SUCCESSFUL (no `FilterChipRowView` references remain).

- [ ] **Step 4: Commit**

```bash
cd mobile
git add iosApp/iosApp/Views/SpkluMapView.swift
git commit -m "feat(ios): wire FilterMenuButton into SpkluMapView top bar"
```

---

### Task 5: iOS — add `availableProviders` to `SpkluViewModel`

**Files:**
- Modify: `mobile/iosApp/iosApp/SpkluViewModel.swift` (inside `class SpkluViewModel`)

**Interfaces:**
- Consumes: `allLocations: [SpkluLocationModel]` (already a stored property) and `SpkluLocationModel.effectiveProviderName`.
- Produces: `var availableProviders: [String]` computed property.

- [ ] **Step 1: Add the computed property**

In `SpkluViewModel.swift`, add inside the class (near the other computed/published filter state, e.g. after `selectedProvider`):

```swift
/// Unique provider names from all loaded locations, sorted by count desc then name asc.
/// Drives the dynamic provider list in the filter sheet.
var availableProviders: [String] {
    let names = allLocations.map { $0.effectiveProviderName }
    var counts: [String: Int] = [:]
    for n in names { counts[n, default: 0] += 1 }
    return counts.keys.sorted { (a, b) -> Bool in
        if counts[a]! != counts[b]! { return counts[a]! > counts[b]! }
        return a < b
    }
}
```

- [ ] **Step 2: Build iOS to confirm**

Run: `xcodebuild -workspace iosApp.xcworkspace -scheme iosApp -sdk iphonesimulator build`
Expected: BUILD SUCCESSFUL.

- [ ] **Step 3: Commit**

```bash
cd mobile
git add iosApp/iosApp/SpkluViewModel.swift
git commit -m "feat(ios): expose availableProviders in SpkluViewModel"
```

---

### Task 6: Android — `Rgb`→Color helper + `FilterIconButton` + sheet content

**Files:**
- Modify: `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/theme/Color.kt` (verify it exists; if the theme colors live elsewhere, add the helper there — see Step 1)
- Create: `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/FilterIconButton.kt`
- Create: `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterSheetContent.kt`
- Delete: `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterRow.kt`

**Interfaces:**
- Consumes: `com.ev.spklu.config.SpkluFilterConfig`, `com.ev.spklu.config.Rgb`, theme colors (`EvGreenPrimary`, `ChargerMediumColor`, etc.), `uiState.availableProviders` (Task 7).
- Produces:
  - `fun Rgb.toComposeColor(): Color` (or `Color(_ rgb: Rgb)` factory)
  - `@Composable fun FilterIconButton(isActive: Boolean, onClick: () -> Unit)`
  - `@Composable fun SpkluFilterSheetContent(selectedTypeCharge: String?, selectedProvider: String?, availableProviders: List<String>, onApplyFilter: (String?, String?) -> Unit, onDismiss: () -> Unit)`

- [ ] **Step 1: Add the Rgb→Color helper**

First check where Android theme colors live:
Run: `grep -rn "val EvGreenPrimary\|val ChargerMediumColor" mobile/androidApp/src/main/java`
Open that file. Add an extension at the bottom:
```kotlin
import androidx.compose.ui.graphics.Color
import com.ev.spklu.config.Rgb

fun Rgb.toComposeColor(): Color = Color(r / 255f, g / 255f, b / 255f)
```
(Adjust the import/package to match the file. `Rgb` comes from the shared module which androidApp already depends on.)

- [ ] **Step 2: Create the sheet content composable**

Create `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterSheetContent.kt`:

```kotlin
package com.ev.spklu.android.ui.components

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.ev.spklu.android.ui.theme.EvGreenPrimary
import com.ev.spklu.android.ui.theme.toComposeColor
import com.ev.spklu.config.SpkluFilterConfig

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SpkluFilterSheetContent(
    selectedTypeCharge: String?,
    selectedProvider: String?,
    availableProviders: List<String>,
    onApplyFilter: (typeCharge: String?, provider: String?) -> Unit,
    onDismiss: () -> Unit
) {
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    var tempTypeCharge by remember { mutableStateOf(selectedTypeCharge) }
    var tempProvider by remember { mutableStateOf(selectedProvider) }

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = sheetState,
        containerColor = MaterialTheme.colorScheme.surface
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 12.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    "⚙️ Filter Lokasi SPKLU",
                    style = MaterialTheme.typography.titleLarge.copy(fontWeight = FontWeight.Bold),
                    color = MaterialTheme.colorScheme.onSurface
                )
                if (tempProvider != null || tempTypeCharge != null) {
                    OutlinedButton(
                        onClick = { tempProvider = null; tempTypeCharge = null },
                        contentPadding = PaddingValues(horizontal = 10.dp, vertical = 4.dp),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Icon(Icons.Default.Refresh, contentDescription = null, modifier = Modifier.height(14.dp))
                        Spacer(Modifier.width(4.dp))
                        Text("Reset", fontSize = 12.sp)
                    }
                }
            }
            Spacer(Modifier.height(16.dp))

            // Provider section (dynamic)
            Text(
                "⚡ Provider / Operator Pengelola:",
                style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.Bold)
            )
            Spacer(Modifier.height(8.dp))
            androidx.compose.foundation.layout.FlowRow(
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                FilterChip(
                    selected = tempProvider == null,
                    onClick = { tempProvider = null },
                    label = { Text("Semua Provider", fontSize = 12.sp) }
                )
                availableProviders.forEach { prov ->
                    val isSelected = tempProvider == prov
                    FilterChip(
                        selected = isSelected,
                        onClick = { tempProvider = if (isSelected) null else prov },
                        label = { Text(prov, fontSize = 12.sp, maxLines = 1) },
                        leadingIcon = if (isSelected) {
                            { Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.height(14.dp)) }
                        } else null,
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = SpkluFilterConfig.sharedColorForProvider(prov).toComposeColor(),
                            selectedLabelColor = Color.Black
                        )
                    )
                }
            }
            Spacer(Modifier.height(20.dp))

            // Speed section (static from shared config)
            Text(
                "🔌 Kecepatan Charging (Tipe Daya):",
                style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.Bold)
            )
            Spacer(Modifier.height(8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(
                    selected = tempTypeCharge == null,
                    onClick = { tempTypeCharge = null },
                    label = { Text("Semua", fontSize = 11.sp, maxLines = 1) },
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = MaterialTheme.colorScheme.primary,
                        selectedLabelColor = MaterialTheme.colorScheme.onPrimary
                    ),
                    modifier = Modifier.weight(1f)
                )
                SpkluFilterConfig.sharedChargeSpeeds().forEach { speed ->
                    val isSelected = tempTypeCharge == speed.id
                    FilterChip(
                        selected = isSelected,
                        onClick = { tempTypeCharge = if (isSelected) null else speed.id },
                        label = { Text(speed.shortLabel, fontSize = 11.sp, maxLines = 1) },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = speed.colorRgb.toComposeColor(),
                            selectedLabelColor = MaterialTheme.colorScheme.onPrimary
                        ),
                        modifier = Modifier.weight(1f)
                    )
                }
            }
            Spacer(Modifier.height(24.dp))

            Button(
                onClick = { onApplyFilter(tempTypeCharge, tempProvider); onDismiss() },
                colors = ButtonDefaults.buttonColors(containerColor = EvGreenPrimary),
                shape = RoundedCornerShape(12.dp),
                modifier = Modifier.fillMaxWidth().height(48.dp)
            ) {
                Text("Terapkan Filter SPKLU", fontWeight = FontWeight.Bold, color = Color.Black, fontSize = 15.sp)
            }
            Spacer(Modifier.height(16.dp))
        }
    }
}
```

> ⚠️ **Interop name check (do this before running):** Kotlin object `SpkluFilterConfig` is accessed from Kotlin/JVM (androidApp) as `SpkluFilterConfig` (NOT `.shared`). Its members `chargeSpeeds` and `colorForProvider(...)` are accessed directly: `SpkluFilterConfig.chargeSpeeds`, `SpkluFilterConfig.colorForProvider(prov)`. The `.shared` / `.sharedColorForProvider` / `.sharedChargeSpeeds()` forms above are WRONG for Kotlin — **correct them** to:
> - `SpkluFilterConfig.colorForProvider(prov).toComposeColor()`
> - `SpkluFilterConfig.chargeSpeeds.forEach { speed -> ... }`
> The `.shared` accessor syntax is the **Swift** form only.

- [ ] **Step 3: Create the FilterIconButton composable**

Create `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/FilterIconButton.kt`:

```kotlin
package com.ev.spklu.android.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import com.ev.spklu.android.ui.theme.EvGreenPrimary

@Composable
fun FilterIconButton(
    isActive: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Surface(
        shape = RoundedCornerShape(14.dp),
        color = if (isActive) EvGreenPrimary else MaterialTheme.colorScheme.surface.copy(alpha = 0.95f),
        contentColor = if (isActive) Color.Black else MaterialTheme.colorScheme.onSurface,
        modifier = modifier.size(48.dp)
    ) {
        Box(
            contentAlignment = Alignment.Center,
            modifier = Modifier.padding(0.dp).background(Color.Transparent)
        ) {
            Icon(
                Icons.Default.FilterList,
                contentDescription = "Filter",
                modifier = Modifier.size(22.dp)
            )
        }
    }
}
```
Wire the click: wrap usage in `Box(modifier = Modifier.clickable { onClick() })` at the call site (Task 8), OR accept `onClick` and apply via `.clickable` directly on the Surface modifier. Prefer adding `.clickable { onClick() }` to the `Surface(modifier = modifier.size(48.dp).clickable { onClick() })` and import `androidx.compose.foundation.clickable`. Update Step 3 accordingly.

- [ ] **Step 4: Delete the old file**

Delete: `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterRow.kt`

- [ ] **Step 5: Build Android app (expect errors in SpkluMapScreen.kt until Task 8)**

Run: `cd mobile && ./gradlew :androidApp:assembleDebug`
Expected: Build FAILS only on `SpkluMapScreen.kt` referencing the deleted `SpkluFilterBar`/`SpkluFilterModalSheet`. Confirm the new files themselves have no errors. Fix the corrected Kotlin-access syntax from the Step 2 note before moving on.

- [ ] **Step 6: Commit**

```bash
cd mobile
git add androidApp/src/main/java/com/ev/spklu/android/ui/components/FilterIconButton.kt \
        androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterSheetContent.kt
# also add the theme file modified in step 1
git rm androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterRow.kt
git commit -m "feat(android): add FilterIconButton + shared-driven filter sheet, remove SpkluFilterRow"
```

---

### Task 7: Android — expose `availableProviders` in `SpkluViewModel`

**Files:**
- Modify: `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/SpkluViewModel.kt`

**Interfaces:**
- Consumes: `SpkluRepository.availableProviders` (Task 2).
- Produces: `SpkluUiState` carries `availableProviders: List<String>`; ViewModel populates it on load.

- [ ] **Step 1: Add field to the UI state data class**

In `SpkluUiState` (top of `SpkluViewModel.kt`), add:
```kotlin
data class SpkluUiState(
    // ... existing fields ...
    val availableProviders: List<String> = emptyList(),
)
```

- [ ] **Step 2: Populate it on load**

In `loadLocations`, after a successful result set `_uiState.value`, copy `availableProviders` from the repository:
```kotlin
result.fold(
    onSuccess = { list ->
        _uiState.value = _uiState.value.copy(
            locations = filterLocationsLocally(list),
            availableProviders = repository.availableProviders,
            isLoading = false
        )
    },
    // onFailure unchanged
)
```
Also populate it in `seedOfflineLocations` for the offline path:
```kotlin
fun seedOfflineLocations(offlineList: List<SpkluLocationDto>) {
    if (offlineList.isNotEmpty()) {
        repository.seedOfflineCache(offlineList)
        _uiState.value = _uiState.value.copy(
            locations = filterLocationsLocally(offlineList),
            availableProviders = repository.availableProviders
        )
    }
}
```

- [ ] **Step 3: Build Android to confirm**

Run: `cd mobile && ./gradlew :androidApp:assembleDebug`
Expected: Still fails only on `SpkluMapScreen.kt` (Task 8). The ViewModel itself compiles.

- [ ] **Step 4: Commit**

```bash
cd mobile
git add androidApp/src/main/java/com/ev/spklu/android/ui/SpkluViewModel.kt
git commit -m "feat(android): expose availableProviders in SpkluUiState"
```

---

### Task 8: Android — wire `FilterIconButton` into `SpkluMapScreen`

**Files:**
- Modify: `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/screens/SpkluMapScreen.kt`

**Interfaces:**
- Consumes: `FilterIconButton` + `SpkluFilterSheetContent` (Task 6), `uiState.availableProviders` (Task 7), existing `showFilterSheet` state and `viewModel.onFilterSelected`.

- [ ] **Step 1: Remove the old filter bar call**

In `SpkluMapScreen.kt`, delete the block at ~line 421:
```kotlin
SpkluFilterBar(
    selectedTypeCharge = uiState.selectedTypeCharge,
    selectedProvider = uiState.selectedProvider,
    onOpenFilterSheet = { showFilterSheet = true },
    modifier = Modifier.padding(top = 8.dp)
)
```
Also remove the now-unused imports for `SpkluFilterBar`.

- [ ] **Step 2: Add FilterIconButton to the top search Row**

In the top-bar `Row` at ~line 380 (the one holding `OutlinedTextField` + refresh `IconButton`), insert the `FilterIconButton` between the search field and the refresh button:
```kotlin
FilterIconButton(
    isActive = uiState.selectedTypeCharge != null || uiState.selectedProvider != null,
    onClick = { showFilterSheet = true },
    modifier = Modifier.padding(start = 8.dp)
)
```
Add imports:
```kotlin
import com.ev.spklu.android.ui.components.FilterIconButton
import com.ev.spklu.android.ui.components.SpkluFilterSheetContent
```

- [ ] **Step 3: Replace the modal sheet presentation**

Find the block at ~line 472 that presented the old `SpkluFilterModalSheet`. Replace with:
```kotlin
if (showFilterSheet) {
    SpkluFilterSheetContent(
        selectedTypeCharge = uiState.selectedTypeCharge,
        selectedProvider = uiState.selectedProvider,
        availableProviders = uiState.availableProviders,
        onApplyFilter = { type, provider -> viewModel.onFilterSelected(type, provider) },
        onDismiss = { showFilterSheet = false }
    )
}
```
Remove the old `SpkluFilterModalSheet` import.

- [ ] **Step 4: Build Android app**

Run: `cd mobile && ./gradlew :androidApp:assembleDebug`
Expected: BUILD SUCCESSFUL.

- [ ] **Step 5: Commit**

```bash
cd mobile
git add androidApp/src/main/java/com/ev/spklu/android/ui/screens/SpkluMapScreen.kt
git commit -m "feat(android): wire FilterIconButton into SpkluMapScreen top bar"
```

---

### Task 9: Verification — builds + manual smoke + repetition check

**Files:** none (verification only).

- [ ] **Step 1: Clean build all targets**

Run: `cd mobile && ./gradlew clean :shared:allTests :androidApp:assembleDebug`
Expected: BUILD SUCCESSFUL, all shared tests green.

- [ ] **Step 2: Build iOS**

Run: `xcodebuild -workspace mobile/iosApp/iosApp.xcworkspace -scheme iosApp -sdk iphonesimulator build`
Expected: BUILD SUCCESSFUL.

- [ ] **Step 3: Repetition check — no filter literals remain in view files**

Run each and confirm near-zero hits (only theme/Color.kt may have base color defs):
```bash
cd mobile
grep -rn "PLN Mobile\|Voltron" iosApp/iosApp androidApp/src/main/java | grep -v "shared/"
# Expected: only inside shared/config/SpkluFilterConfig.kt; nothing in iOS/Android views.
grep -rn '"medium"\|"ultra_fast"\|"fast"' iosApp/iosApp androidApp/src/main/java | grep -v "shared/"
# Expected: nothing in iOS/Android views (config is the only source).
```
If any hits remain in view files, remove them (they should already be gone).

- [ ] **Step 4: Manual smoke (Android, run on emulator)**

Launch the app on an emulator. Verify:
- Top bar shows `[search] [filter icon] [refresh]` — only ONE filter trigger.
- Tap filter icon → sheet opens with a Provider section (lists providers actually present in data, e.g. PLN Mobile, Voltron, ...) and a Speed section (Medium / Fast DC / Ultra Fast).
- Select "Fast DC" + "Voltron" → Apply → markers/list reflect the filter.
- Reset → Apply → all markers return.
- A provider not in the color map (if any in your dataset) still shows with a non-white chip color (fallback).

- [ ] **Step 5: Manual smoke (iOS, run on simulator)**

Same checks as Step 4 on the iOS simulator.

- [ ] **Step 6: Final commit if any cleanup was made**

```bash
cd mobile
git add -A
git commit -m "chore: cleanup leftover filter literals after redesign"  # only if step 3 found anything
```

## Self-Review Notes

- **Spec coverage**: (1) single filter button — Tasks 3,4,6,8. (2) source-of-truth shared config — Task 1. (3) dynamic provider list — Tasks 2,5,7. (4) provider color fallback — Task 1 (tested). (5) cross-platform — both Android & iOS wired. ✓
- **Interop correctness flagged inline**: `.shared` is Swift-only; Kotlin/JVM uses `SpkluFilterConfig` directly (Task 6 Step 2 note + Task 6 Step 3 build gate).
- **Type consistency**: `availableProviders: List<String>` / `[String]` consistent across Tasks 2,5,7 and consumers (3,6,8). `onApplyFilter(typeCharge: String?, provider: String?)` signature preserved everywhere.
- **Known follow-up (out of scope)**: Android marker pin colors (`createCustomEvPinBitmapDescriptor`) still hardcode provider colors — could consume `SpkluFilterConfig.colorForProvider` later for full consistency, but excluded per spec §6.
