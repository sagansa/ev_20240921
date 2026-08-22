<x-guest-layout>
    <div class="pt-4 bg-gray-100">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div>
                <x-authentication-card-logo />
            </div>

            <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white shadow-md overflow-hidden sm:rounded-lg prose">
                <h2>Syarat &amp; Ketentuan Penggunaan</h2>
                <p>Terakhir diperbarui: 22 Agustus 2026</p>

                <p>
                    Selamat datang di EV Charge ID. Dengan mengunduh, mengakses, atau menggunakan
                    aplikasi dan layanan EV Charge ID (&ldquo;Layanan&rdquo;), Anda menyetujui untuk terikat pada
                    Syarat &amp; Ketentuan ini. Lisensi penggunaan aplikasi itu sendiri tunduk pada
                    <a href="https://www.apple.com/legal/internet-services/itunes/dev/stdeula/" target="_blank" rel="noopener">Ketentuan Penggunaan (EULA) standar Apple</a>;
                    syarat di bawah ini mengatur penggunaan Layanan EV Charge ID secara keseluruhan.
                    Jika Anda tidak menyetujui syarat ini, mohon berhenti menggunakan Layanan.
                </p>

                <h3>1. Tentang Layanan</h3>
                <p>
                    EV Charge ID membantu pengendara kendaraan listrik di Indonesia menemukan dan
                    menggunakan stasiun pengisian kendaraan listrik umum (SPKLU). Informasi lokasi,
                    konektor, dan ketersediaan daya dikompilasi dari sumber publik — antara lain data
                    resmi PLN dan API publik Kementerian ESDM — melalui server kami.
                </p>
                <p>
                    Kami <strong>bukan</strong> operator pengisian daya, bukan penyedia listrik, dan
                    <strong>tidak memproses pembayaran pengisian</strong>. Transaksi pengisian daya
                    terjadi langsung antara Anda dan operator stasiun (PLN Icon Plus, Voltron, Shell
                    Recharge, Pertamina GES, dll.).
                </p>

                <h3>2. Akun Pengguna</h3>
                <p>
                    Anda dapat menjelajah peta dan detail stasiun tanpa akun. Fitur tertentu —
                    review, unggah foto, pencatatan sesi charging, favorit, dan sinkronisasi antar
                    perangkat — memerlukan akun yang dapat dibuat melalui email + OTP,
                    Sign in with Apple, atau Google Sign-In.
                </p>
                <p>
                    Anda bertanggung jawab menjaga kerahasiaan kredensial akun Anda dan untuk seluruh
                    aktivitas yang terjadi melalui akun tersebut. Anda dapat menghapus akun kapan
                    saja dari aplikasi (tab Akun &rarr; Hapus Akun). Penghapusan akun bersifat
                    permanen dan menghapus data pribadi Anda dari Layanan, sebagaimana dijelaskan di
                    Kebijakan Privasi.
                </p>

                <h3>3. Konten yang Anda Kirim (Review &amp; Foto)</h3>
                <p>
                    Anda dapat menulis review dan mengunggah foto untuk stasiun tempat Anda telah
                    mencatat sesi charging yang selesai. Anda menjamin bahwa Anda memiliki hak atas
                    konten tersebut dan bahwa kontennya tidak melanggar hukum, tidak menyesatkan,
                    tidak menyerang pihak lain, dan tidak memuat materi pribadi orang lain tanpa
                    izin mereka.
                </p>
                <p>
                    Dengan mengirimkan konten, Anda memberi kami lisensi non-eksklusif, bebas
                    royalti, dan dapat dialihkan ke dalam cakupan Layanan untuk menampilkan, menyimpan,
                    dan mendistribusikan konten tersebut. Kami berhak memoderasi atau menghapus
                    konten yang melanggar syarat ini. Konten yang tidak pantas dapat dilaporkan
                    kepada kami melalui halaman <a href="{{ route('contact') }}">kontak</a>.
                </p>

                <h3>4. Langganan &ldquo;Bebas Iklan&rdquo; (Berlangganan Otomatis)</h3>
                <p>
                    Aplikasi menawarkan langganan otomatis (auto-renewable subscription)
                    &ldquo;Bebas Iklan&rdquo; melalui Apple In-App Purchase:
                </p>
                <ul>
                    <li>Paket bulanan dan tahunan; harga sesuai yang tertera di App Store untuk wilayah Anda.</li>
                    <li>Langganan diperpanjang otomatis dan akun Anda ditagih melalui Apple paling lambat 24 jam sebelum akhir periode berjalan, kecuali Anda membatalkannya setidaknya 24 jam sebelum akhir periode.</li>
                    <li>Anda dapat berhenti berlangganan kapan saja melalui Pengaturan Apple ID &rarr; Langganan (Subscriptions), atau di app melalui halaman kelola langganan.</li>
                    <li>Pembayaran, penagihan, dan pengembalian dana diproses sepenuhnya oleh Apple sesuai kebijakan Apple; kami tidak menyimpan detail pembayaran Anda.</li>
                    <li>Saat langganan aktif, iklan disembunyikan di seluruh aplikasi. Jika langganan berakhir atau dibatalkan, iklan kembali ditampilkan.</li>
                </ul>

                <h3>5. Iklan</h3>
                <p>
                    Versi gratis Layanan didukung oleh iklan dari Google AdMob. Iklan dapat
                    dipersonalisasi bergantung pada izin pelacakan (App Tracking Transparency) dan/atau
                    persetujuan iklan yang Anda berikan di perangkat Anda. Detail lebih lanjut tersedia
                    di <a href="{{ route('privacy.short') }}">Kebijakan Privasi</a>. Anda dapat
                    menghilangkan seluruh iklan dengan berlangganan paket &ldquo;Bebas Iklan&rdquo;.
                </p>

                <h3>6. Akurasi Data &amp; Batasan Tanggung Jawab</h3>
                <p>
                    Data stasiun dan ketersediaan charger dapat berubah sewaktu-waktu dan bisa saja
                    tidak akurat atau tidak lengkap. Selalu verifikasi ketersediaan serta harga
                    pengisian langsung kepada operator sebelum melakukan perjalanan. Perhitungan
                    biaya pada catatan sesi charging bersifat estimasi berdasarkan tarif resmi PLN
                    dan pengaturan Anda.
                </p>
                <p>
                    Layanan disediakan &ldquo;sebagaimana adanya&rdquo; tanpa jaminan apa pun. Sejauh
                    diizinkan oleh hukum yang berlaku, kami tidak bertanggung jawab atas kerugian
                    tidak langsung, insidental, atau konsekuensial yang timbul dari penggunaan
                    Layanan.
                </p>

                <h3>7. Perubahan Syarat</h3>
                <p>
                    Kami dapat mengubah Syarat &amp; Ketentuan ini dari waktu ke waktu. Versi terbaru
                    selalu tersedia di halaman ini dengan tanggal pembaruan di bagian atas. Perubahan
                    yang material akan diinformasikan di dalam aplikasi. Melanjutkan penggunaan
                    Layanan setelah perubahan berlaku berarti Anda menyetujui versi terbaru.
                </p>

                <h3>8. Hukum yang Berlaku</h3>
                <p>
                    Syarat &amp; Ketentuan ini tunduk pada hukum Republik Indonesia.
                </p>

                <h3>9. Kontak</h3>
                <p>
                    Pertanyaan mengenai syarat ini dapat disampaikan melalui halaman
                    <a href="{{ route('contact') }}">kontak</a> di situs kami.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
