@extends('layouts.main')

@section('title', 'Pembicara Seminar EV - Sagansa')

@section('content')
    <section class="relative overflow-hidden bg-gradient-to-br from-ev-blue-800 via-ev-blue-400 to-ev-green-400">
        <div class="container relative px-6 py-24 mx-auto text-center lg:px-12">
            <span class="inline-flex items-center px-4 py-1 text-sm font-semibold tracking-wide uppercase rounded-full bg-ev-white/10 text-ev-white">
                Sagansa EV Speaker Series
            </span>
            <h1 class="mt-6 text-4xl font-extrabold text-white lg:text-5xl">
                Pembicara Seminar Kendaraan Listrik
            </h1>
            <p class="max-w-3xl mx-auto mt-6 text-lg leading-relaxed text-ev-white/90">
                Hadirkan sesi seminar, talkshow, atau keynote bertema kendaraan listrik bersama praktisi Sagansa.
                Kami membawakan insight strategis, data lapangan, dan pengalaman implementasi EV di Indonesia.
            </p>
            <div class="flex flex-col items-center justify-center gap-4 mt-10 sm:flex-row">
                <a href="#topik" class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-ev-blue-800 transition bg-white rounded-lg shadow hover:bg-ev-blue-100">
                    Topik Seminar
                </a>
                <a href="#hubungi" class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white transition border border-white rounded-lg hover:bg-white/10">
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>

    <section id="topik" class="py-16 bg-white">
        <div class="container px-6 mx-auto lg:px-12">
            <div class="max-w-3xl">
                <h2 class="text-3xl font-bold text-ev-blue-800">Topik Seminar Kendaraan Listrik</h2>
                <p class="mt-4 text-lg text-gray-600">
                    Sesi kami dapat menyesuaikan format seminar publik, kelas inspirasi kampus, hingga townhall perusahaan.
                    Berikut tema yang paling sering diminta organisasi dan komunitas.
                </p>
            </div>

            <div class="grid gap-8 mt-12 lg:grid-cols-3">
                <article class="h-full p-6 transition border rounded-2xl border-ev-blue-100 hover:shadow-xl">
                    <h3 class="text-xl font-semibold text-ev-blue-800">Fundamental EV</h3>
                    <p class="mt-3 text-gray-600">
                        Pengantar menyeluruh mengenai ekosistem kendaraan listrik: teknologi baterai, motor, charging,
                        hingga perkembangan pasar nasional dan global.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li>• Evolusi dan roadmap kendaraan listrik di Indonesia</li>
                        <li>• Komponen utama EV &amp; peran ekosistem pendukung</li>
                        <li>• Kebijakan dan insentif pemerintah</li>
                        <li>• Peluang bisnis lintas industri</li>
                    </ul>
                </article>

                <article class="h-full p-6 transition border rounded-2xl border-ev-blue-100 hover:shadow-xl">
                    <h3 class="text-xl font-semibold text-ev-blue-800">EV Specialist Track</h3>
                    <p class="mt-3 text-gray-600">
                        Membahas sisi teknis dan operasional: desain powertrain, integrasi charging, serta best practice
                        maintenance dari proyek lapangan Sagansa.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li>• Desain dan validasi sistem baterai &amp; power electronics</li>
                        <li>• Strategi thermal management dan efisiensi energi</li>
                        <li>• Lesson learned troubleshooting lapangan</li>
                        <li>• Standar keselamatan kerja teknisi EV</li>
                    </ul>
                </article>

                <article class="h-full p-6 transition border rounded-2xl border-ev-blue-100 hover:shadow-xl">
                    <h3 class="text-xl font-semibold text-ev-blue-800">Charging Infrastructure &amp; Operations</h3>
                    <p class="mt-3 text-gray-600">
                        Strategi membangun dan mengoperasikan SPKLU, termasuk kesiapan jaringan listrik, model bisnis,
                        dan kolaborasi multi-pihak.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li>• Audit lokasi &amp; requirement teknis</li>
                        <li>• Standar instalasi, proteksi, dan keselamatan</li>
                        <li>• Monitoring kinerja &amp; service level</li>
                        <li>• Studi kasus implementasi Sagansa</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="py-16 bg-blue-900">
        <div class="container px-6 mx-auto lg:px-12">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <h2 class="text-3xl font-bold text-white">Agenda Seminar Terbaru</h2>
                    <p class="mt-4 text-lg text-white/80">
                        Berikut contoh agenda yang sedang kami siapkan bersama mitra. Silakan hubungi kami untuk kolaborasi,
                        jadwal in-house, atau mengundang pembicara di kota Anda.
                    </p>
                </div>

                <div class="p-6 space-y-6 bg-white/10 rounded-2xl backdrop-blur">
                    <div class="p-5 bg-white rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 text-xs font-semibold text-white uppercase rounded-full bg-ev-green-400">
                                18 November 2024
                            </span>
                            <span class="text-sm text-ev-blue-800">Jakarta &amp; Live Streaming</span>
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-ev-blue-800">
                            Seminar Nasional: Strategi Implementasi SPKLU di Kawasan Komersial
                        </h3>
                        <p class="mt-3 text-sm text-gray-600">
                            Sesi panel dan keynote mengenai peluang bisnis serta struktur kemitraan untuk mempercepat
                            ketersediaan SPKLU di kawasan komersial.
                        </p>
                        <ul class="mt-4 text-sm text-gray-500">
                            <li>• Pembicara: Kementerian ESDM, PLN Icon Plus, Tim Sagansa Engineering</li>
                            <li>• Sesi khusus: Klinik perizinan &amp; konsultasi teknis 1-on-1</li>
                        </ul>
                    </div>

                    <div class="p-5 bg-white rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 text-xs font-semibold text-white uppercase rounded-full bg-ev-blue-400">
                                12 Desember 2024
                            </span>
                            <span class="text-sm text-gray-700">Bandung</span>
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-ev-blue-800">
                            Workshop Teknis: Retrofit &amp; Konversi Kendaraan Menjadi EV
                        </h3>
                        <p class="mt-3 text-sm text-gray-600">
                            Sesi praktik langsung untuk bengkel konversi, mencakup homologasi, pemilihan komponen,
                            dan prosedur keselamatan kerja.
                        </p>
                        <ul class="mt-4 text-sm text-gray-500">
                            <li>• Hands-on dengan modul baterai &amp; kontroler keluaran terbaru</li>
                            <li>• Sertifikat kompetensi mutu Sagansa Academy</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-gray-50">
        <div class="container px-6 mx-auto lg:px-12">
            <div class="grid gap-10 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <h2 class="text-3xl font-bold text-ev-blue-800">Mengapa Mengundang Sagansa?</h2>
                    <p class="mt-4 text-lg text-gray-600">
                        Lebih dari sekadar teori, kami membawa pengalaman implementasi proyek EV nyata di seluruh
                        Indonesia. Setiap sesi dirancang kolaboratif dengan studi kasus terkini.
                    </p>
                </div>

                <div class="grid gap-6 lg:col-span-2 sm:grid-cols-2">
                    <div class="h-full p-6 bg-white border rounded-2xl border-ev-blue-100">
                        <h3 class="text-xl font-semibold text-ev-blue-800">Mentor Industri</h3>
                        <p class="mt-3 text-gray-600">
                            Fasilitator berasal dari engineer Sagansa, akademisi, dan regulator yang aktif
                            dalam penyusunan standar kendaraan listrik.
                        </p>
                    </div>

                    <div class="h-full p-6 bg-white border rounded-2xl border-ev-blue-100">
                        <h3 class="text-xl font-semibold text-ev-blue-800">Insight Aktual</h3>
                        <p class="mt-3 text-gray-600">
                            Materi diperbarui secara berkala berdasarkan proyek lapangan, riset, dan perkembangan kebijakan terbaru.
                        </p>
                    </div>

                    <div class="h-full p-6 bg-white border rounded-2xl border-ev-blue-100">
                        <h3 class="text-xl font-semibold text-ev-blue-800">Kolaboratif</h3>
                        <p class="mt-3 text-gray-600">
                            Kami bekerja bersama panitia untuk menyesuaikan alur acara, format interaktif, dan deliverable yang diharapkan.
                        </p>
                    </div>

                    <div class="h-full p-6 bg-white border rounded-2xl border-ev-blue-100">
                        <h3 class="text-xl font-semibold text-ev-blue-800">Fleksibel</h3>
                        <p class="mt-3 text-gray-600">
                            Tersedia opsi offline maupun online, durasi 30–90 menit, dan dapat digabung dengan sesi panel diskusi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="hubungi" class="py-16 bg-white">
        <div class="container px-6 mx-auto lg:px-12">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-ev-blue-800">Hubungi Kami</h2>
                <p class="mt-4 text-lg text-gray-600">
                    Siap menghadirkan Sagansa sebagai pembicara? Hubungi kami melalui email atau WhatsApp untuk membahas
                    kebutuhan acara, topik, dan jadwal yang diinginkan.
                </p>
            </div>

            <div class="grid gap-8 mt-12 md:grid-cols-2">
                <div class="p-6 text-center border rounded-2xl border-ev-blue-100">
                    <h3 class="text-lg font-semibold text-ev-blue-800">Email</h3>
                    <p class="mt-2 text-gray-600">training@sagansa.id</p>
                    <a href="mailto:training@sagansa.id"
                        class="inline-flex items-center justify-center px-4 py-2 mt-4 text-sm font-semibold text-white rounded-lg bg-blue-700 hover:bg-blue-800">
                        Kirim Email
                    </a>
                </div>

                <div class="p-6 text-center border rounded-2xl border-ev-blue-100">
                    <h3 class="text-lg font-semibold text-ev-blue-800">WhatsApp</h3>
                    <p class="mt-2 text-gray-600">+62 8111 923 572</p>
                    <a href="https://wa.me/628111923572" target="_blank"
                        class="inline-flex items-center justify-center px-4 py-2 mt-4 text-sm font-semibold text-white rounded-lg bg-ev-green-400 hover:bg-ev-green-800">
                        Hubungi via WA
                    </a>
                </div>
            </div>

            <div class="grid gap-6 mt-16 lg:grid-cols-2">
                <div class="p-6 rounded-2xl bg-blue-50">
                    <h3 class="text-xl font-semibold text-ev-blue-800">Skema Pembiayaan &amp; Kerja Sama</h3>
                    <ul class="mt-4 space-y-3 text-gray-600">
                        <li>• Honorarium pembicara fleksibel dan dapat disesuaikan</li>
                        <li>• Panel diskusi bersama mitra industri &amp; regulator</li>
                        <li>• Kolaborasi dengan universitas, komunitas, dan korporasi</li>
                        <li>• Sesi internal perusahaan &amp; townhall edukasi EV</li>
                    </ul>
                </div>

                <div class="p-6 rounded-2xl bg-blue-50">
                    <h3 class="text-xl font-semibold text-ev-blue-800">FAQ Singkat</h3>
                    <ul class="mt-4 space-y-3 text-gray-600">
                        <li><strong>Bahasa penyampaian?</strong> Bahasa Indonesia, tersedia opsi bilingual.</li>
                        <li><strong>Durasi sesi?</strong> 30–90 menit menyesuaikan format acara.</li>
                        <li><strong>Apakah bisa offline &amp; online?</strong> Ya, kami melayani keduanya.</li>
                        <li><strong>Apakah materi dapat dikustom?</strong> Bisa, outline disusun bersama panitia.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
