@extends('layouts.main')

@section('title', 'EV YouTube Collection - Educational Videos about Electric Vehicles')
@section('description', 'Browse our collection of educational and informational videos about electric vehicles, charging technology, and sustainable transportation in Indonesia.')

@section('content')
<div class="container px-4 py-8 mx-auto">
    <h1 class="mb-8 text-3xl font-bold text-center text-gray-800">Insights & Stories</h1>

    <!-- Search and Filter Section -->
    <div class="p-4 mb-8 bg-white rounded-lg shadow-md">
        <form method="GET" action="{{ route('youtube.index') }}">
            <div class="flex flex-col gap-4 md:flex-row">
                <div class="flex-1">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search videos..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green">
                </div>

                <div>
                    <select name="category" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button type="submit" class="px-6 py-2 text-white transition-colors rounded-lg bg-green hover:bg-green-dark">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Videos Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        @forelse($videos as $video)
            <div class="overflow-hidden transition-shadow bg-white rounded-lg shadow-md hover:shadow-lg">
                <a href="{{ route('youtube.show', $video->id) }}">
                    <img src="{{ $video->thumbnail_url ?: 'https://img.youtube.com/vi/' . $video->video_id . '/0.jpg' }}"
                         alt="{{ $video->title }}"
                         class="object-cover w-full h-48">
                </a>

                <div class="p-4">
                    <h3 class="mb-2 text-lg font-semibold line-clamp-2">
                        <a href="{{ route('youtube.show', $video->id) }}" class="text-gray-800 hover:text-green">
                            {{ $video->title }}
                        </a>
                    </h3>

                    <p class="mb-2 text-sm text-gray-600">{{ $video->channel_name }}</p>

                    <div class="flex justify-between text-xs text-gray-500">
                        <span>{{ $video->category }}</span>
                        <span>{{ $video->view_count }} views</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-12 text-center col-span-full">
                <p class="text-lg text-gray-500">No videos found matching your criteria.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $videos->links() }}
    </div>
</div>
@endsection
