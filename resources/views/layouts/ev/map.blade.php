@extends('layouts.main')

@section('body_class', 'map-page')
@section('content_classes', 'flex-grow pt-0 md:pt-0 overflow-hidden')

@section('title', 'Map - EV Charger')

@section('additional_head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endsection

@section('content')
    <div class="relative w-full bg-white">
        <x-map.interactive 
            :locations="$chargerLocations"
            :providers="$providers"
            :charging-types="$chargingTypes"
            :location-categories="$locationCategories"
            map-type="community"
        />
    </div>
    
    <x-mobile.youtube-section :ev-youtube-video-id="$evYoutubeVideoId ?? null" />
@endsection
