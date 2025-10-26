@extends('layouts.main')

@section('title', 'EV YouTube Collection - Educational Videos about Electric Vehicles')
@section('description', 'Browse our collection of educational and informational videos about electric vehicles, charging technology, and sustainable transportation in Indonesia.')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">EV YouTube Collection</h1>

    <!-- Search and Filter Section -->
    <div class="mb-8 p-4 bg-white rounded-lg shadow-md">
        <form method="GET" action="{{ route('youtube.index') }}">
            <div class="flex flex-col md:flex-row gap-4">
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
                    <button type="submit" class="px-6 py-2 bg-green text-white rounded-lg hover:bg-green-dark transition-colors">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Videos Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($videos as $video)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <a href="{{ route('youtube.show', $video->id) }}">
                    <img src="{{ $video->thumbnail_url ?: 'https://img.youtube.com/vi/' . $video->video_id . '/0.jpg' }}" 
                         alt="{{ $video->title }}" 
                         class="w-full h-48 object-cover">
                </a>
                
                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-2 line-clamp-2">
                        <a href="{{ route('youtube.show', $video->id) }}" class="text-gray-800 hover:text-green">
                            {{ $video->title }}
                        </a>
                    </h3>
                    
                    <p class="text-sm text-gray-600 mb-2">{{ $video->channel_name }}</p>
                    
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>{{ $video->category }}</span>
                        <span>{{ $video->view_count }} views</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 text-lg">No videos found matching your criteria.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $videos->links() }}
    </div>
</div>
@endsection