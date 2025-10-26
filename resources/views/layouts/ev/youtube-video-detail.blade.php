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
            <div class="relative pt-[56.25%]"> <!-- 16:9 Aspect Ratio -->
                <iframe 
                    class="absolute top-0 left-0 w-full h-full"
                    src="https://www.youtube.com/embed/{{ $video->video_id }}"
                    title="{{ $video->title }}"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
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