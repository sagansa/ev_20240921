@props(['evYoutubeVideoId' => null])

@if ($evYoutubeVideoId)
    <div id="mobile-youtube-section" class="bg-ev-gray-100 py-8 md:hidden">
        <div class="container px-4 mx-auto max-w-6xl">
            <h2 class="text-2xl font-bold text-ev-blue-800 text-center mb-6">Belajar Kendaraan Listrik</h2>
            <div class="text-center">
                <p class="mb-4 text-ev-gray-800">
                    Tonton video pembelajaran berikut untuk memahami perkembangan kendaraan listrik dan
                    infrastruktur pengisian daya di Indonesia.
                </p>
                <div class="relative w-full overflow-hidden rounded-xl shadow-xl mx-auto" style="max-width: 560px; padding-top: 56.25%;">
                    <iframe class="absolute inset-0 w-full h-full"
                        src="https://www.youtube.com/embed/{{ $evYoutubeVideoId }}" title="Pembelajaran EV"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen loading="lazy"></iframe>
                </div>
                <div class="mt-6">
                    <a href="{{ route('youtube.index') }}" class="inline-block px-6 py-3 font-bold text-white bg-ev-green-500 rounded-full hover:bg-ev-green-600 transition duration-300">
                        Lihat Semua Video
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif