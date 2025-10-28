@extends('layouts.main')

@section('title', $video->title . ' - EV YouTube Collection')
@section('description', Str::limit(strip_tags($video->description), 160))

@section('additional_head')
    <!-- Schema.org markup for Google -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "VideoObject",
        "name": "{{ $video->title }}",
        "description": "{{ strip_tags($video->description) }}",
        "thumbnailUrl": "{{ $video->thumbnail_url ?: 'https://img.youtube.com/vi/' . $video->video_id . '/0.jpg' }}",
        "uploadDate": "{{ $video->published_at ? $video->published_at->format('Y-m-d\TH:i:sP') : '' }}",
        "duration": "PT1M",
        "embedUrl": "https://www.youtube.com/embed/{{ $video->video_id }}",
        "interactionStatistic": {
            "@type": "InteractionCounter",
            "interactionType": "https://schema.org/WatchAction",
            "userInteractionCount": {{ $video->view_count }}
        }
    }
    </script>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- YouTube Video Player -->
            <div class="relative pt-[56.25%]" id="youtube-player-wrapper-{{ $video->id }}">
                <div 
                    id="youtube-player-{{ $video->id }}"
                    class="absolute inset-0 w-full h-full"
                    data-video-id="{{ $video->video_id }}"
                    role="presentation"
                ></div>
                <div 
                    id="youtube-player-fallback-{{ $video->id }}"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-ev-gray-100 text-center px-6 hidden"
                >
                    <p class="text-ev-gray-700 font-medium">Video tidak dapat diputar langsung di situs ini.</p>
                    <a href="https://www.youtube.com/watch?v={{ $video->video_id }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center px-4 py-2 bg-ev-blue-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-ev-blue-700 transition">
                        Buka di YouTube
                    </a>
                </div>
            </div>

            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $video->title }}</h1>
                
                <div class="flex flex-wrap items-center justify-between mb-4 text-sm text-gray-600">
                    <div>
                        <span class="font-medium">{{ $video->channel_name }}</span>
                    </div>
                    
                    <div class="flex space-x-4">
                        <span>{{ $video->view_count }} views</span>
                        <span>{{ $video->published_at ? $video->published_at->format('M d, Y') : 'N/A' }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <span class="inline-block px-3 py-1 bg-green bg-opacity-10 text-green rounded-full text-sm">
                        {{ $video->category }}
                    </span>
                </div>

                <div class="prose text-gray-700">
                    <p>{{ $video->description }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('youtube.index') }}" class="inline-block px-6 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
                Back to Collection
            </a>
        </div>
    </div>
</div>
@endsection

@once
    @push('scripts')
        <script>
            window.evYoutube = window.evYoutube || {
                ready: false,
                queue: [],
                loading: false,
            };

            window.evYoutube.loadApi = function () {
                if (window.evYoutube.loading) return;
                window.evYoutube.loading = true;
                if (document.getElementById('youtube-iframe-api')) {
                    return;
                }
                const tag = document.createElement('script');
                tag.id = 'youtube-iframe-api';
                tag.src = 'https://www.youtube.com/iframe_api';
                document.head.appendChild(tag);
                window.onYouTubeIframeAPIReady = function () {
                    window.evYoutube.ready = true;
                    window.evYoutube.queue.forEach(fn => fn());
                    window.evYoutube.queue = [];
                };
            };

            window.evYoutube.initPlayer = function ({ playerId, videoId, fallbackId }) {
                const createPlayer = () => {
                    try {
                        const player = new YT.Player(playerId, {
                            videoId,
                            playerVars: {
                                rel: 0,
                                modestbranding: 1,
                                playsinline: 1,
                                origin: window.location.origin,
                            },
                            events: {
                                onError: () => window.evYoutube.showFallback(fallbackId),
                            },
                        });
                        return player;
                    } catch (error) {
                        console.error('YouTube player init error', error);
                        window.evYoutube.showFallback(fallbackId);
                    }
                };

                if (window.evYoutube.ready && window.YT && window.YT.Player) {
                    createPlayer();
                } else {
                    window.evYoutube.queue.push(createPlayer);
                    window.evYoutube.loadApi();
                }
            };

            window.evYoutube.showFallback = function (fallbackId) {
                const fallback = document.getElementById(fallbackId);
                if (fallback) {
                    fallback.classList.remove('hidden');
                }
            };
        </script>
    @endpush
@endonce

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.evYoutube) {
                window.evYoutube.initPlayer({
                    playerId: 'youtube-player-{{ $video->id }}',
                    videoId: '{{ $video->video_id }}',
                    fallbackId: 'youtube-player-fallback-{{ $video->id }}',
                });
            }
        });
    </script>
@endpush
