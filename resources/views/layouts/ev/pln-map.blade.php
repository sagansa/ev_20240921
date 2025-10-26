@extends('layouts.main')

@section('title', 'Lokasi Charging Station EV di Indonesia | Peta Stasiun Pengisian Kendaraan Listrik')

@section('additional_head')
    <meta name="description"
        content="Temukan lokasi charging station kendaraan listrik terdekat di Indonesia. Peta interaktif stasiun pengisian EV dengan informasi real-time lokasi anda, tipe charging, kapasitas, dan provider.">
    <meta name="keywords"
        content="charging station EV, SPKLU, stasiun pengisian kendaraan listrik, peta charger EV, lokasi charging station Indonesia, EV charger map">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Dataset",
        "name": "Peta Lokasi Charging Station EV di Indonesia",
        "description": "Database lengkap lokasi charging station kendaraan listrik di Indonesia dengan informasi detail provider, tipe charger, dan kategori lokasi.",
        "keywords": ["charging station", "SPKLU", "EV charger", "stasiun pengisian listrik", "kendaraan listrik", "electric vehicle"],
        "url": "{{ url()->current() }}",
        "provider": {
            "@type": "PT Sagansa Engineering Indonesia",
            "name": "EV Charging Network Indonesia"
        }
    }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endsection

@section('content')
    <div class="relative w-full h-screen bg-white">
        <x-map.interactive 
            :locations="$plnLocations"
            :providers="$providers"
            :charging-types="$chargingTypes"
            :location-categories="$locationCategories"
        />
    </div>
    
    <x-mobile.youtube-section :ev-youtube-video-id="$evYoutubeVideoId ?? null" />
@endsection