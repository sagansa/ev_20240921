# Filter UX Improvements: Speed Bug Fix + Provider Hierarchy + Modal Search

**Goal:** Fix the broken speed filter (data uses `standard`/`ultrafast` which the config doesn't recognize), reduce the 13-item provider list to a relevant Top-N with counts and a collapsible "Lainnya", and add a search field inside the filter modal — across shared Kotlin, iOS, and Android.

**Architecture:** Normalization lives in shared (`SpkluFilterConfig.normalizeSpeedId`), consumed at filter time (not at import, so no data migration). The provider hierarchy (top vs other) and counts are computed in shared (`SpkluFilterConfig.providerBuckets`) over the unfiltered locations, exposed to both platforms. Modal search is local UI state per platform.

**Tech Stack:** Kotlin Multiplatform (common), Swift (iOS), Jetpack Compose (Android). Tests via `kotlin.test`.

## Global Constraints

- Shared module framework name is `shared`; iOS accesses `SpkluFilterConfig.shared`, Android accesses `SpkluFilterConfig` directly.
- `availableProviders` / provider buckets must compute over UNFILTERED locations, sorted by count desc then name asc.
- Do NOT migrate existing data; normalization is applied at read/filter time so both stored and new data work.
- `onApplyFilter(typeCharge: String?, provider: String?) -> Unit` signature preserved.
- Indonesian UI labels are the convention.
- `type_charge` raw values in data: `medium`, `standard`, `fast`, `ultrafast` (also tolerate `ultra_fast`). Normalized canonical ids: `medium`, `fast`, `ultra_fast`. Mapping by watt evidence: `standard`(7-20kW)→`medium`; `ultrafast`(100kW+)→`ultra_fast`.
- This is a follow-up on branch `feat/filter-redesign-cross-platform`; reuse the existing `SpkluFilterConfig` + `availableProviders` plumbing.

---

### Task 1: Speed normalization in shared config

**Files:**
- Modify: `mobile/shared/src/commonMain/kotlin/com/ev/spklu/config/SpkluFilterConfig.kt`
- Test: `mobile/shared/src/commonTest/kotlin/com/ev/spklu/config/SpkluFilterConfigTest.kt`

**Interfaces:**
- Produces: `fun SpkluFilterConfig.normalizeSpeedId(raw: String?): String?` — maps raw type_charge to a canonical id in `chargeSpeeds` (`medium`/`fast`/`ultra_fast`), or `null` if unrecognized/blank.

- [ ] **Step 1: Add failing tests**

Append to `SpkluFilterConfigTest.kt`:

```kotlin
@Test
fun normalizeSpeedIdMapsKnownVariantsToCanonicalIds() {
    assertEquals("medium", SpkluFilterConfig.normalizeSpeedId("medium"))
    assertEquals("medium", SpkluFilterConfig.normalizeSpeedId("standard"))
    assertEquals("fast", SpkluFilterConfig.normalizeSpeedId("fast"))
    assertEquals("ultra_fast", SpkluFilterConfig.normalizeSpeedId("ultrafast"))
    assertEquals("ultra_fast", SpkluFilterConfig.normalizeSpeedId("ultra_fast"))
}

@Test
fun normalizeSpeedIdIsCaseInsensitiveAndHandlesNull() {
    assertEquals("medium", SpkluFilterConfig.normalizeSpeedId("Medium"))
    assertEquals("medium", SpkluFilterConfig.normalizeSpeedId("STANDARD"))
    assertEquals("ultra_fast", SpkluFilterConfig.normalizeSpeedId("UltraFast"))
    assertNull(SpkluFilterConfig.normalizeSpeedId(null))
    assertNull(SpkluFilterConfig.normalizeSpeedId(""))
    assertNull(SpkluFilterConfig.normalizeSpeedId("weird-unknown"))
}
```

- [ ] **Step 2: Run tests, confirm fail**

Run: `cd mobile && ./gradlew :shared:jvmTest --tests "com.ev.spklu.config.SpkluFilterConfigTest"`
Expected: FAIL (unresolved `normalizeSpeedId`).

- [ ] **Step 3: Implement**

Add to `SpkluFilterConfig` object in `SpkluFilterConfig.kt`:

```kotlin
/**
 * Map a raw type_charge value (as stored in data) to a canonical charge-speed id
 * matching one of [chargeSpeeds], or null if unrecognized/blank.
 *
 * Data variants observed by watt range:
 *  - "standard" (7-20 kW AC)  -> medium
 *  - "ultrafast"/"ultra_fast" (100 kW+ DC) -> ultra_fast
 * Applied at read time so no data migration is needed.
 */
fun normalizeSpeedId(raw: String?): String? {
    val key = raw?.trim()?.lowercase()?.replace(" ", "")
    if (key.isNullOrBlank()) return null
    return when (key) {
        "medium", "standard" -> "medium"
        "fast" -> "fast"
        "ultrafast", "ultra_fast" -> "ultra_fast"
        else -> null
    }
}
```

- [ ] **Step 4: Run tests, confirm pass**

Run: `cd mobile && ./gradlew :shared:jvmTest --tests "com.ev.spklu.config.SpkluFilterConfigTest"`
Expected: PASS (all, incl. new).

- [ ] **Step 5: Commit**

```bash
cd mobile
git add shared/src/commonMain/kotlin/com/ev/spklu/config/SpkluFilterConfig.kt \
        shared/src/commonTest/kotlin/com/ev/spklu/config/SpkluFilterConfigTest.kt
git commit -m "fix(shared): normalize speed ids (standard->medium, ultrafast->ultra_fast)"
```

---

### Task 2: Apply speed normalization at filter time in shared repository

**Why:** Android filters via the repository's `filterAndSortLocalCache`; iOS filters locally in the ViewModel. Both currently compare raw `typeCharge` to the selected (canonical) id with no normalization, so `ultra_fast` selection matches 0 rows and `standard` rows are unfilterable. Fix the comparison in shared so both platforms benefit identically. iOS uses the same shared comparison via its own local filter — add a shared helper and use it on both sides.

**Files:**
- Modify: `mobile/shared/src/commonMain/kotlin/com/ev/spklu/repository/SpkluRepository.kt`
- Modify: `mobile/shared/src/commonTest/kotlin/com/ev/spklu/repository/SpkluRepositoryProvidersTest.kt`

**Interfaces:**
- Produces: repository's `filterAndSortLocalCache` uses `SpkluFilterConfig.normalizeSpeedId(loc.typeCharge) == typeCharge` (the selected value is already canonical, since it comes from `chargeSpeeds`).

- [ ] **Step 1: Add failing test**

Append to `SpkluRepositoryProvidersTest.kt`:

```kotlin
@Test
fun filterMatchesSpeedAfterNormalization() {
    val repo = SpkluRepository()
    repo.seedOfflineCache(listOf(
        dto(1).copy(typeCharge = "standard"),       // -> medium
        dto(2).copy(typeCharge = "ultrafast"),      // -> ultra_fast
        dto(3).copy(typeCharge = "fast"),
        dto(4).copy(typeCharge = "medium"),
    ))
    val filtered = repo.getSpkluLocations(typeCharge = "ultra_fast").getOrThrow()
    assertEquals(1, filtered.size)
    assertEquals("ultrafast", filtered.first().typeCharge)
}
```
Note: `getSpkluLocations(typeCharge=...)` reads from the seeded cache (no forceRefresh). Verify the signature accepts `typeCharge` named arg (it does: `typeCharge: String? = null`).

- [ ] **Step 2: Run test, confirm fail**

Run: `cd mobile && ./gradlew :shared:jvmTest --tests "com.ev.spklu.repository.SpkluRepositoryProvidersTest"`
Expected: FAIL (0 matches because raw "ultrafast" != "ultra_fast").

- [ ] **Step 3: Apply normalization in filter**

In `SpkluRepository.filterAndSortLocalCache`, change the type-match line from:
```kotlin
val matchType = typeCharge.isNullOrBlank() || loc.typeCharge?.equals(typeCharge, ignoreCase = true) == true
```
to:
```kotlin
val matchType = typeCharge.isNullOrBlank() ||
        SpkluFilterConfig.normalizeSpeedId(loc.typeCharge) == typeCharge
```
Add import `import com.ev.spklu.config.SpkluFilterConfig`.

- [ ] **Step 4: Run test, confirm pass; run full shared suite**

Run: `cd mobile && ./gradlew :shared:allTests`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd mobile
git add shared/src/commonMain/kotlin/com/ev/spklu/repository/SpkluRepository.kt \
        shared/src/commonTest/kotlin/com/ev/spklu/repository/SpkluRepositoryProvidersTest.kt
git commit -m "fix(shared): filter speed by normalized id so standard/ultrafast match"
```

---

### Task 3: iOS — normalize speed filter in local filter

**Why:** iOS filters locally in `SpkluViewModel.filterLocations()` (it does NOT go through the repository for filtering), so the repository fix alone doesn't help iOS. Apply the same shared `normalizeSpeedId` there.

**Files:**
- Modify: `mobile/iosApp/iosApp/SpkluViewModel.swift`

- [ ] **Step 1: Apply normalization**

In `filterLocations()`, the current comparison is:
```swift
filtered = filtered.filter { $0.typeCharge?.lowercased() == type.lowercased() }
```
Change to use the shared normalizer (iOS accesses it as `SpkluFilterConfig.shared.normalizeSpeedId(raw:)` returning `String?`):
```swift
filtered = filtered.filter { SpkluFilterConfig.shared.normalizeSpeedId(raw: $0.typeCharge) == type }
```
(`type` here is `selectedTypeCharge`, already canonical.) Confirm the interop name by reading how `SpkluFilterConfig.shared.colorForProvider(name:)` is called in `SpkluFilterSheet.swift` (Task 3 of the prior plan) — `normalizeSpeedId(raw:)` follows the same pattern.

- [ ] **Step 2: Build iOS, confirm green**

Run: `cd mobile/iosApp && xcodebuild -project iosApp.xcodeproj -scheme iosApp -sdk iphonesimulator -destination 'platform=iOS Simulator,name=iPhone 17 Pro' -derivedDataPath build build`
Expected: BUILD SUCCEEDED.

- [ ] **Step 3: Commit**

```bash
cd mobile
git add iosApp/iosApp/SpkluViewModel.swift
git commit -m "fix(ios): normalize speed id in local filter"
```

---

### Task 4: Provider buckets in shared config (hierarchy + counts)

**Why:** The 13-item flat provider list is the UX problem. Provide a Top/Other split + counts, computed over unfiltered locations, so the modal can show a relevant Top-N and collapse the long tail.

**Files:**
- Modify: `mobile/shared/src/commonMain/kotlin/com/ev/spklu/config/SpkluFilterConfig.kt`
- Test: `mobile/shared/src/commonTest/kotlin/com/ev/spklu/config/SpkluFilterConfigTest.kt`

**Interfaces:**
- Produces:
  - `data class ProviderBucket(val name: String, val count: Int)`
  - `val SpkluFilterConfig.defaultTopProviderThreshold: Int = 10` (providers with >= this many locations are "top").
  - `fun List<SpkluLocationDto>.providerBuckets(threshold: Int = SpkluFilterConfig.defaultTopProviderThreshold): ProviderBuckets`
  - `data class ProviderBuckets(val top: List<ProviderBucket>, val other: List<ProviderBucket>)`

- [ ] **Step 1: Add failing tests**

Append to `SpkluFilterConfigTest.kt`:

```kotlin
@Test
fun providerBucketsSplitsByCountThreshold() {
    val locations = listOf(
        dto(1, "PLN Mobile"), dto(2, "PLN Mobile"), dto(3, "PLN Mobile"),
        dto(4, "Voltron"), dto(5, "Voltron"),
        dto(6, "Rare One"),
    )
    val buckets = locations.providerBuckets(threshold = 2)
    // PLN(3), Voltron(2) >= 2 -> top, sorted by count desc then name
    assertEquals(listOf("PLN Mobile", "Voltron"), buckets.top.map { it.name })
    assertEquals(listOf(3, 2), buckets.top.map { it.count })
    // Rare(1) < 2 -> other
    assertEquals(listOf("Rare One"), buckets.other.map { it.name })
    assertEquals(listOf(1), buckets.other.map { it.count })
}

@Test
fun providerBucketsEmptyWhenNoLocations() {
    val buckets = emptyList<SpkluLocationDto>().providerBuckets()
    assertTrue(buckets.top.isEmpty())
    assertTrue(buckets.other.isEmpty())
}
```
Note: the `dto(...)` helper in this test class already exists from the prior plan's Task 1 — reuse it. Confirm its signature (it takes `id` and `providerName`). Add `import com.ev.spklu.config.providerBuckets` and `import com.ev.spklu.config.ProviderBuckets` if needed; the extension + data class live in package `com.ev.spklu.config`.

- [ ] **Step 2: Run tests, confirm fail**

Run: `cd mobile && ./gradlew :shared:jvmTest --tests "com.ev.spklu.config.SpkluFilterConfigTest"`
Expected: FAIL (unresolved `providerBuckets`/`ProviderBuckets`).

- [ ] **Step 3: Implement**

Add to `SpkluFilterConfig.kt` (after `uniqueProviderNames`):

```kotlin
/** Minimum location count for a provider to be considered a "top" provider in the filter UI. */
const val DEFAULT_TOP_PROVIDER_THRESHOLD: Int = 10

/** One provider with its location count. */
data class ProviderBucket(val name: String, val count: Int)

/** Top (frequent) providers vs the long tail, computed over unfiltered locations. */
data class ProviderBuckets(
    val top: List<ProviderBucket>,
    val other: List<ProviderBucket>,
)

/**
 * Split provider names into top (>= threshold locations) and other (< threshold),
 * each sorted by count desc then name asc.
 */
fun List<SpkluLocationDto>.providerBuckets(
    threshold: Int = SpkluFilterConfig.DEFAULT_TOP_PROVIDER_THRESHOLD,
): ProviderBuckets {
    val counts = this.map { it.effectiveProviderName }
        .groupingBy { it }.eachCount()
        .entries
        .sortedWith(compareByDescending<Map.Entry<String, Int>> { it.value }.thenBy { it.key })
    return ProviderBuckets(
        top = counts.filter { it.value >= threshold }.map { ProviderBucket(it.key, it.value) },
        other = counts.filter { it.value < threshold }.map { ProviderBucket(it.key, it.value) },
    )
}
```
Note: `DEFAULT_TOP_PROVIDER_THRESHOLD` as a top-level `const val` (not inside the object) so the extension default arg can reference it. `ProviderBucket`/`ProviderBuckets` top-level data classes. Import `effectiveProviderName` in this file (already imported from the prior `uniqueProviderNames`).

- [ ] **Step 4: Run tests, confirm pass**

Run: `cd mobile && ./gradlew :shared:allTests`
Expected: PASS (all).

- [ ] **Step 5: Commit**

```bash
cd mobile
git add shared/src/commonMain/kotlin/com/ev/spklu/config/SpkluFilterConfig.kt \
        shared/src/commonTest/kotlin/com/ev/spklu/config/SpkluFilterConfigTest.kt
git commit -m "feat(shared): add providerBuckets for top/other provider hierarchy + counts"
```

---

### Task 5: Expose provider buckets on the repository

**Files:**
- Modify: `mobile/shared/src/commonMain/kotlin/com/ev/spklu/repository/SpkluRepository.kt`
- Modify: `mobile/shared/src/commonTest/kotlin/com/ev/spklu/repository/SpkluRepositoryProvidersTest.kt`

**Interfaces:**
- Produces: `val SpkluRepository.providerBuckets: ProviderBuckets` computed over `_cachedLocations` (unfiltered), using the default threshold.

- [ ] **Step 1: Add failing test**

Append to `SpkluRepositoryProvidersTest.kt`:

```kotlin
@Test
fun providerBucketsReflectsSeededCache() {
    val repo = SpkluRepository()
    repo.seedOfflineCache(listOf(
        dto(1, "PLN Mobile"), dto(2, "PLN Mobile"),
        dto(3, "Rare"),
    ))
    val buckets = repo.providerBuckets
    assertEquals(listOf("PLN Mobile"), buckets.top.map { it.name })
    assertEquals(listOf(2), buckets.top.map { it.count })
    assertEquals(listOf("Rare"), buckets.other.map { it.name })
}
```

- [ ] **Step 2: Run test, confirm fail**

Run: `cd mobile && ./gradlew :shared:jvmTest --tests "com.ev.spklu.repository.SpkluRepositoryProvidersTest"`
Expected: FAIL (unresolved `providerBuckets`).

- [ ] **Step 3: Implement**

In `SpkluRepository.kt`, add import `import com.ev.spklu.config.providerBuckets` and `import com.ev.spklu.config.ProviderBuckets`. Next to `availableProviders`, add:
```kotlin
/** Provider top/other split over the unfiltered cache, for the filter UI. */
val providerBuckets: ProviderBuckets
    get() = _cachedLocations.toList().providerBuckets()
```

- [ ] **Step 4: Run test, confirm pass; full suite**

Run: `cd mobile && ./gradlew :shared:allTests`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd mobile
git add shared/src/commonMain/kotlin/com/ev/spklu/repository/SpkluRepository.kt \
        shared/src/commonTest/kotlin/com/ev/spklu/repository/SpkluRepositoryProvidersTest.kt
git commit -m "feat(shared): expose providerBuckets from repository cache"
```

---

### Task 6: Android — buckets in UI state + modal shows Top/Other + counts + search

**Files:**
- Modify: `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/SpkluViewModel.kt`
- Modify: `mobile/androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterSheetContent.kt`

- [ ] **Step 1: Add buckets to UI state + populate**

In `SpkluViewModel.kt`:
- Add to `SpkluUiState`: `val providerBuckets: ProviderBuckets = ProviderBuckets(emptyList(), emptyList())` (import `com.ev.spklu.config.ProviderBuckets`).
- In `loadLocations` onSuccess copy and `seedOfflineLocations` copy: `providerBuckets = repository.providerBuckets`.

- [ ] **Step 2: Update sheet signature + content**

In `SpkluFilterSheetContent.kt`, change the provider parameter from `availableProviders: List<String>` to `providerBuckets: ProviderBuckets`. Update the Provider section:
- "Semua Provider" chip stays.
- Top section: `providerBuckets.top.forEach { b -> FilterChip(label = "${b.name} (${b.count})", ... color = colorForProvider(b.name)...) }`.
- Search field above the chips: `var providerQuery by remember { mutableStateOf("") }`. When `providerQuery` is non-blank, show ALL providers (top+other) whose name contains the query (case-insensitive), ignoring the top/other split. When blank, show top directly + a "Lainnya (N)" toggle that reveals `other`.
- Collapsible "Lainnya": `var showOther by remember { mutableStateOf(false) }`; a text-button row "Lainnya (${providerBuckets.other.size})" toggles it; when expanded, render `other.forEach`.

Add imports: `com.ev.spklu.config.ProviderBuckets`, `com.ev.spklu.config.ProviderBucket`, `androidx.compose.material3.OutlinedTextField` (or `TextField`), `androidx.compose.material.icons.Icons`/`Icons.Default.ExpandMore`/`ExpandLess` (use `KeyboardArrowDown`/`KeyboardArrowUp` if ExpandMore not available).

- [ ] **Step 3: Update call site in SpkluMapScreen**

In `SpkluMapScreen.kt`, change `availableProviders = uiState.availableProviders` to `providerBuckets = uiState.providerBuckets` at the `SpkluFilterSheetContent(...)` call.

- [ ] **Step 4: Build Android, confirm green**

Run: `cd mobile && ./gradlew :androidApp:assembleDebug`
Expected: BUILD SUCCESSFUL.

- [ ] **Step 5: Commit**

```bash
cd mobile
git add androidApp/src/main/java/com/ev/spklu/android/ui/SpkluViewModel.kt \
        androidApp/src/main/java/com/ev/spklu/android/ui/components/SpkluFilterSheetContent.kt \
        androidApp/src/main/java/com/ev/spklu/android/ui/screens/SpkluMapScreen.kt
git commit -m "feat(android): provider Top/Other hierarchy + counts + modal search"
```

---

### Task 7: iOS — buckets in ViewModel + modal shows Top/Other + counts + search

**Files:**
- Modify: `mobile/iosApp/iosApp/SpkluViewModel.swift`
- Modify: `mobile/iosApp/iosApp/Views/SpkluFilterSheet.swift`
- Modify: `mobile/iosApp/iosApp/Views/SpkluMapView.swift`

- [ ] **Step 1: Add buckets computed property to ViewModel**

iOS can't easily hold a `ProviderBuckets` (KMP interop struct) as a clean `[ProviderBucket]` to ForEach; compute an equivalent in Swift for the UI to avoid interop friction. Add to `SpkluViewModel`:

```swift
/// Provider names with counts, split into top (>= threshold) and other, from all loaded locations.
struct ProviderCount: Identifiable { let id = UUID(); let name: String; let count: Int }
struct ProviderBuckets { let top: [ProviderCount]; let other: [ProviderCount] }

var availableProviderBuckets: ProviderBuckets {
    let counts = Dictionary(grouping: allLocations.map { $0.effectiveProviderName }, by: { $0 })
        .mapValues { $0.count }
    let sorted = counts.sorted { (a, b) in
        if a.value != b.value { return a.value > b.value }
        return a.key < b.key
    }.map { ProviderCount(name: $0.key, count: $0.value) }
    let threshold = Int(SpkluFilterConfig.shared.defaultTopProviderThreshold)
    return ProviderBuckets(
        top: sorted.filter { $0.count >= threshold },
        other: sorted.filter { $0.count < threshold }
    )
}
```
Note: verify the interop name for the threshold constant — iOS sees top-level Kotlin `const val` as `SpkluFilterConfig.shared.defaultTopProviderThreshold` (a class property on the object). If that name differs, read the generated shared framework header / use `SpkluFilterConfig.shared.DEFAULT_TOP_PROVIDER_THRESHOLD`. Confirm at build.

- [ ] **Step 2: Update SpkluFilterSheet to take buckets**

In `SpkluFilterSheet.swift`, change the init from `availableProviders: [String]` to `providerBuckets: SpkluViewModel.ProviderBuckets`. Update the Provider section:
- "Semua" chip stays.
- `@State private var providerSearch = ""`, `@State private var showOther = false`.
- When `providerSearch` is non-blank: ForEach over `providerBuckets.top + providerBuckets.other` filtered by name contains search (case-insensitive); chips show `"name (count)"`, color via `colorForProvider`.
- When blank: ForEach `providerBuckets.top` as chips; then a "Lainnya (N)" toggle row that flips `showOther`; when on, ForEach `providerBuckets.other`.
- Add a small `TextField("Cari provider...", text: $providerSearch)` above the chip grid.

- [ ] **Step 3: Update call site in SpkluMapView**

In `SpkluMapView.swift`, change `availableProviders: viewModel.availableProviders` to `providerBuckets: viewModel.availableProviderBuckets` at the `FilterMenuButton(...)` call.

- [ ] **Step 4: Build iOS, confirm green**

Run: `cd mobile/iosApp && xcodebuild -project iosApp.xcodeproj -scheme iosApp -sdk iphonesimulator -destination 'platform=iOS Simulator,name=iPhone 17 Pro' -derivedDataPath build build`
Expected: BUILD SUCCEEDED.

- [ ] **Step 5: Commit**

```bash
cd mobile
git add iosApp/iosApp/SpkluViewModel.swift \
        iosApp/iosApp/Views/SpkluFilterSheet.swift \
        iosApp/iosApp/Views/SpkluMapView.swift
git commit -m "feat(ios): provider Top/Other hierarchy + counts + modal search"
```

---

### Task 8: Verification

**Files:** none.

- [ ] **Step 1: Full shared suite + Android build + iOS build**

```bash
cd mobile
./gradlew :shared:allTests
./gradlew :androidApp:assembleDebug
cd iosApp && xcodebuild -project iosApp.xcodeproj -scheme iosApp -sdk iphonesimulator -destination 'platform=iOS Simulator,name=iPhone 17 Pro' -derivedDataPath build build
```
Expected: all three green.

- [ ] **Step 2: Manual smoke notes (Android + iOS sim)**

Without launching emulators, confirm via code: the modal now shows Top providers with counts, a collapsible "Lainnya", a search field, and the speed filter matches `standard`/`ultrafast` rows after normalization.

- [ ] **Step 3: Final commit if cleanup needed**

```bash
cd mobile
git add -A && git commit -m "chore: cleanup after filter UX improvements" || echo "nothing to commit"
```

## Self-Review Notes

- **Speed bug (A):** Tasks 1-3. Normalization central + applied at filter time in both repo (Android path) and iOS local filter. `standard`→`medium`, `ultrafast`→`ultra_fast`, evidenced by watt ranges.
- **Provider hierarchy + counts (B):** Tasks 4-7. Top/Other split by `DEFAULT_TOP_PROVIDER_THRESHOLD=10`; with real data this yields 8 top (PLN..Toyota Lexus) + 5 other (Otopods/Mall/Stroom/EV Cuzz/RheCharge), shrinking the visible list from 13 to 8 + collapsed.
- **Modal search (C):** Tasks 6-7 (search field in the provider section). When searching, ignores the top/other split and shows all matches.
- **Data source:** buckets computed over UNFILTERED cache (repository) / `allLocations` (iOS), consistent with `availableProviders`.
- **Signature preserved:** `onApplyFilter(type, provider)` unchanged.
- **Threshold tunable:** single `DEFAULT_TOP_PROVIDER_THRESHOLD` const in shared.
- **Interop risk flagged:** iOS name for the threshold constant (`defaultTopProviderThreshold` vs `DEFAULT_TOP_PROVIDER_THRESHOLD`) — verify at Task 7 build.
