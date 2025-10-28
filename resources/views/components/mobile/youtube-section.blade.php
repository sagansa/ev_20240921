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
                    <div id="mobile-youtube-player" class="absolute inset-0 w-full h-full" data-video-id="{{ $evYoutubeVideoId }}"></div>
                    <div id="mobile-youtube-fallback" class="absolute inset-0 hidden flex flex-col items-center justify-center gap-4 bg-ev-gray-100 text-center px-4">
                        <p class="text-ev-gray-700 text-sm font-medium">Video tidak dapat diputar otomatis.</p>
                        <a href="https://www.youtube.com/watch?v={{ $evYoutubeVideoId }}"
                           class="inline-flex items-center px-4 py-2 bg-ev-blue-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-ev-blue-700 transition"
                           target="_blank" rel="noopener noreferrer">
                            Buka di YouTube
                        </a>
                    </div>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.evYoutube && document.getElementById('mobile-youtube-player')) {
                window.evYoutube.initPlayer({
                    playerId: 'mobile-youtube-player',
                    videoId: '{{ $evYoutubeVideoId }}',
                    fallbackId: 'mobile-youtube-fallback',
                });
            }
        });
    </script>
@endpush
