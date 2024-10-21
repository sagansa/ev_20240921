@extends('layouts.main')

@section('title', 'Products - EV Charger')

@section('content')
    <div class="p-8">
        <h2 class="mb-6 text-3xl font-bold text-ev-blue-800">EV Products</h2>
        @if (isset($products) && $products->count() > 0)
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach ($products as $product)
                    <div class="overflow-hidden transition duration-300 rounded-lg shadow-lg bg-ev-white hover:shadow-xl">
                        <img src="{{ $product->image ?? 'https://via.placeholder.com/300x200' }}" alt="{{ $product->name }}"
                            class="object-cover w-full h-48">
                        <div class="p-6">
                            <h3 class="mb-2 text-xl font-bold text-ev-blue-800">{{ $product->name }}</h3>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="p-4 rounded-lg text-ev-gray-800 bg-ev-gray-100">Tidak ada produk yang tersedia saat ini.</p>
        @endif
    </div>
@endsection
