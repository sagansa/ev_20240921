# Setup Google + Apple Sign-In (untuk Developer)

Panduan setup credentials di console. **Kerjakan ini sebelum Task 7 (Android) & Task 8 (iOS)**. Kode sudah dirancang untuk membaca credentials dari `.env` (backend) dan `strings.xml`/target config (mobile).

> ⚠️ Jangan commit nilai secret asli. Pakai placeholder di `.env.example`; isi nilai asli hanya di `.env` lokal (yang sudah di-`.gitignore`).

---

## A. Google Cloud Console

Buka https://console.cloud.google.com.

### 1. Buat / pilih project
- Project name: `EV Charge ID` (boleh pakai project yg sudah ada).

### 2. OAuth consent screen
- APIs & Services → **OAuth consent screen**.
- User type: **External** (atau Internal kalau punya Google Workspace).
- App information:
  - App name: `EV Charge ID`
  - User support email: email Anda
  - App logo: (opsional)
- App domain: situs `ev.sagansa.id` (kalau sudah deploy).
- Authorized domains: `sagansa.id`.
- Developer contact information: email Anda.
- Scopes: `userinfo.email`, `userinfo.profile`, `openid`.
- Test users: tambahkan email Anda saat masih status "Testing" (sebelum publish).

### 3. Credentials → buat 3 OAuth Client ID

**a) Web Client** (WAJIB — dipakai backend utk verify audience)
- Application type: **Web application**.
- Authorized JavaScript origins: `https://ev.sagansa.id`, `http://localhost:8000` (dev).
- Authorized redirect URIs: `https://ev.sagansa.id/auth/google/callback`, `http://localhost:8000/auth/google/callback`.
- **Catat:**
  - `GOOGLE_CLIENT_ID` (format `xxxx.apps.googleusercontent.com`) → isi ke backend `.env`.
  - `GOOGLE_CLIENT_SECRET` → (opsional, hanya kalau pakai server flow; utk ID-token verify tak wajib).

**b) Android Client** (dipakai app Android)
- Application type: **Android**.
- Package name: `id.sagansa.ev` (cek `androidApp/src/main/AndroidManifest.xml`).
- SHA-1 certificate fingerprint:
  - Debug: `keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android | grep SHA1`
  - Release: dari keystore release Anda.
- **Catat:** Android Client ID (per-platform) — dipakai di `strings.xml` / Credential Manager config.

**c) iOS Client** (opsional utk Google Sign-In iOS — tapi kita pakai Apple di iOS, jadi bisa skip).
- Kalau tetap mau Google di iOS: bundle id `id.sagansa.ev`, iOS URL scheme (reversed client id).

### 4. Yang perlu Anda kasih ke saya (setelah setup)
- **`GOOGLE_CLIENT_ID`** (Web Client ID) → backend `.env`.
- **Android Client ID** → `androidApp/src/main/res/values/strings.xml` (`google_web_client_id`).

---

## B. Apple Developer (butuh akun $99)

Buka https://developer.apple.com.

### 1. Enable "Sign in with Apple" pada App ID
- Certificates, Identifiers & Profiles → **Identifiers**.
- Pilih app id `id.sagansa.ev` (atau buat baru kalau belum).
- Capabilities → centang **Sign In with Apple** → Save.

### 2. Buat Service ID (utk backend verify Apple identity token)
- Identifiers → **+** → **Services IDs**.
- Description: `EV Charge ID Web Auth`.
- Identifier: `id.sagansa.ev.auth` (atau mirror bundle id). **Catat = `APPLE_SERVICE_ID`**.
- Enable **Sign In with Apple** → Configure:
  - Primary App ID: `id.sagansa.ev`.
  - Domains: `ev.sagansa.id`.
  - Return URLs: `https://ev.sagansa.id/auth/apple/callback`.

### 3. Buat Key (.p8) utk backend verify
- Certificates, Identifiers & Profiles → **Keys** → **+**.
- Name: `EVChargeAuthKey`.
- Enable **Sign In with Apple** → Configure → Primary App ID `id.sagansa.ev`.
- Register → **Download** file `.p8` → simpan aman (hanya sekali bisa download).
- **Catat:**
  - **Key ID** → `APPLE_KEY_ID`
  - **Team ID** (lihat di membership / kanan atas) → `APPLE_TEAM_ID`
  - **Private Key file** (`.p8`) → simpan di server, set path `APPLE_PRIVATE_KEY_PATH`.

### 4. iOS capability
- Di Xcode target → Signing & Capabilities → **+ Capability** → **Sign in with Apple**.
- (Butuh Team/akun Apple terpilih.)

### 5. Yang perlu Anda kasih ke saya (setelah setup)
- `APPLE_TEAM_ID`, `APPLE_KEY_ID`, `APPLE_SERVICE_ID` → backend `.env`.
- File `.p8` → upload ke server, set path `APPLE_PRIVATE_KEY_PATH` di `.env`.

---

## C. Privacy Policy URL

Google consent screen & Apple butuh **privacy policy URL publik**.
- Buat halaman simpel: `https://ev.sagansa.id/privacy` (atau route web di backend Laravel).
- Isi minimal: data yg dikumpul (email, nama, avatar dari provider), tujuan (login & profil), kontak.

---

## D. Template `.env` (backend)

```env
# Google Sign-In
GOOGLE_CLIENT_ID=<web client id dari Google Console>

# Apple Sign-In (backend verify)
APPLE_TEAM_ID=<10 char team id>
APPLE_KEY_ID=<10 char key id>
APPLE_SERVICE_ID=<service id, mis. id.sagansa.ev.auth>
APPLE_PRIVATE_KEY_PATH=/path/ke/AuthKey_XXXXXX.p8
```

## E. Status checklist

- [ ] Google OAuth consent screen
- [ ] Google Web Client ID → backend `.env GOOGLE_CLIENT_ID`
- [ ] Google Android Client ID + SHA-1 → `strings.xml`
- [ ] Apple App ID: Sign in with Apple enabled
- [ ] Apple Service ID (`APPLE_SERVICE_ID`)
- [ ] Apple Key (.p8) + Key ID + Team ID
- [ ] Privacy policy URL publik
- [ ] Akun $99 Apple aktif (utk iOS device + App Store submit)

Setelah checklist di atas selesai, beri tahu — saya lanjutkan Task 7 (Android) & Task 8 (iOS).
