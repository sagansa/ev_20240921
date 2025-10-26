@extends('layouts.main')

@section('title', 'Map - EV Charger')

@section('additional_head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endsection

@section('content')
    <div class="relative w-full h-screen bg-white">
        <x-map.interactive 
            :locations="$chargerLocations"
            :providers="$providers"
            :charging-types="$chargingTypes"
            :location-categories="$locationCategories"
        />
    </div>
    
    <x-mobile.youtube-section :ev-youtube-video-id="$evYoutubeVideoId ?? null" />
@endsection