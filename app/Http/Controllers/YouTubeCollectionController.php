<?php

namespace App\Http\Controllers;

use App\Models\YouTubeCollection;
use Illuminate\Http\Request;

class YouTubeCollectionController extends Controller
{
    public function index(Request $request)
    {
        $query = YouTubeCollection::query();

        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search !== '') {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%');
            });
        }

        $query->where('is_active', true);

        $videos = $query->orderBy('published_at', 'desc')->paginate(12);

        $categories = YouTubeCollection::select('category')
            ->where('is_active', true)
            ->distinct()
            ->pluck('category');

        return view('layouts.ev.youtube-collection', compact('videos', 'categories'));
    }

    public function show($id)
    {
        $video = YouTubeCollection::where('is_active', true)->findOrFail($id);
        return view('layouts.ev.youtube-video-detail', compact('video'));
    }

    // Admin functions
    public function create()
    {
        return view('layouts.ev.youtube-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'video_id' => 'required|string|max:255|unique:youtube_collections,video_id',
            'description' => 'nullable|string',
            'thumbnail_url' => 'nullable|url',
            'channel_name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'view_count' => 'nullable|integer|min:0',
            'published_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        YouTubeCollection::create($request->all());

        return redirect()->route('youtube.index')->with('success', 'Video added successfully.');
    }

    public function edit($id)
    {
        $video = YouTubeCollection::findOrFail($id);
        return view('layouts.ev.youtube-edit', compact('video'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'video_id' => 'required|string|max:255|unique:youtube_collections,video_id,' . $id,
            'description' => 'nullable|string',
            'thumbnail_url' => 'nullable|url',
            'channel_name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'view_count' => 'nullable|integer|min:0',
            'published_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $video = YouTubeCollection::findOrFail($id);
        $video->update($request->all());

        return redirect()->route('youtube.index')->with('success', 'Video updated successfully.');
    }

    public function destroy($id)
    {
        $video = YouTubeCollection::findOrFail($id);
        $video->delete();

        return redirect()->route('youtube.index')->with('success', 'Video deleted successfully.');
    }
}