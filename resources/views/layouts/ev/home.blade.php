@extends('layouts.main')

@section('title', 'Home - EV Charger')

@section('content')
    <div
        class="flex items-center justify-center min-h-[calc(100vh-4rem)] bg-gradient-to-br from-ev-blue-400 to-ev-green-400">
        <div class="max-w-2xl px-4 py-8 text-center">
            <h1 class="mb-8 text-4xl font-bold text-ev-white">Welcome to Sagansa</h1>
            <div class="space-y-6">
                <p class="text-xl text-ev-white">
                    Find the nearest charging station for your EV.
                </p>
                <p class="text-xl text-ev-white">
                    <span class="block mb-2 italic font-semibold">"You can contribute"</span>
                    to inform other users about charging stations in your nearest area or during your travels.
                </p>
                <p class="text-xl text-ev-white">
                    Also, you can record your consumption of electricity when charging your EV.
                </p>
            </div>
            <div class="flex justify-center mt-12 space-x-4">
                <a href="{{ route('map') }}"
                    class="px-6 py-3 font-semibold transition duration-300 rounded-lg bg-ev-white text-ev-blue-800 hover:bg-ev-blue-100">
                    Find Chargers
                </a>
                <a href="{{ route('products') }}"
                    class="px-6 py-3 font-semibold transition duration-300 rounded-lg bg-ev-white text-ev-blue-800 hover:bg-ev-blue-100">
                    Our Products
                </a>
            </div>
        </div>
    </div>
@endsection
