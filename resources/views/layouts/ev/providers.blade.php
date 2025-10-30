@extends('layouts.main')

@section('title', 'Providers - EV Charger')

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $resolveProviderImage = static function ($provider): string {
        $rawImage = trim((string) data_get($provider, 'image', ''));

        if ($rawImage === '') {
            return asset('images/no-image.png');
        }

        if (Str::startsWith($rawImage, ['http://', 'https://'])) {
            return $rawImage;
        }

        $normalized = preg_replace('#^(storage/|public/)+#', '', ltrim($rawImage, '/'));

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->url($normalized);
        }

        if (file_exists(public_path('storage/' . $normalized))) {
            return asset('storage/' . $normalized);
        }

        if (file_exists(public_path($normalized))) {
            return asset($normalized);
        }

        return asset('images/no-image.png');
    };

    $fallbackImage = asset('images/no-image.png');
@endphp

@section('content')
    <div class="p-4 lg:p-8">
        <h2 class="mb-6 text-3xl font-bold text-ev-blue-800">Providers</h2>

        <!-- Form pencarian -->
        <form action="{{ route('providers') }}" method="GET" class="mb-4 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <input type="text" name="search" id="search" placeholder="Find provider..."
                    value="{{ request('search') }}"
                    class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>
            <div class="flex justify-end">
                <button type="button" id="clearFilter"
                    class="px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Clear Filter
                </button>
            </div>
        </form>

        @if ($providers->count() > 0)
            <!-- Tampilan desktop -->
            <div class="hidden lg:block">
                <div class="flow-root mt-8">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead>
                                    <tr>
                                        <th scope="col"
                                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">
                                            Image
                                        </th>
                                        <th scope="col"
                                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">
                                            <a href="{{ route('providers', ['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                                class="inline-flex group">
                                                Name
                                                <span
                                                    class="flex-none ml-2 text-gray-400 rounded group-hover:visible group-focus:visible">
                                                    @if (request('sort') == 'name')
                                                        @if (request('direction') == 'asc')
                                                            &#x25B2;
                                                        @else
                                                            &#x25BC;
                                                        @endif
                                                    @else
                                                        &#x25B2;
                                                    @endif
                                                </span>
                                            </a>
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Links
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Price
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Admin Fee
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Tax
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Contact
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Email
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($providers as $provider)
                                        <tr>
                                            @php
                                                $providerImageUrl = $resolveProviderImage($provider);
                                            @endphp
                                            <td
                                                class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 whitespace-nowrap sm:pl-0">
                                                <img src="{{ $providerImageUrl }}"
                                                    alt="{{ $provider->name }}"
                                                    loading="lazy"
                                                    class="object-cover w-12 h-12 rounded-full"
                                                    onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                                            </td>
                                            <td
                                                class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 whitespace-nowrap sm:pl-0">
                                                {{ $provider->name }}
                                                <span
                                                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ $provider->charger_locations_count }} loc
                                                </span>
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-500">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if ($provider->web)
                                                        <a href="{{ $provider->web }}" target="_blank" title="Website">
                                                            <img src="{{ asset('svg/website-ui-web-svgrepo-com.svg') }}"
                                                                alt="Website" class="w-auto h-6">
                                                        </a>
                                                    @endif
                                                    @if ($provider->google)
                                                        <a href="{{ $provider->google }}" target="_blank"
                                                            title="Get it on Google Play">
                                                            <img src="{{ asset('svg/Google_Play_Store_badge_EN.svg') }}"
                                                                alt="Get it on Google Play" class="w-auto h-8">
                                                        </a>
                                                    @endif
                                                    @if ($provider->ios)
                                                        <a href="{{ $provider->ios }}" target="_blank"
                                                            title="Download on the App Store">
                                                            <img src="{{ asset('svg/Download_on_the_App_Store_Badge.svg') }}"
                                                                alt="Download on the App Store" class="w-auto h-8">
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                {{ $provider->price ? $provider->price : 'N/A' }}
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                {{ $provider->admin_fee ? $provider->admin_fee : 'N/A' }}
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                {{ $provider->tax ? $provider->tax : 'N/A' }}
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                @if ($provider->contact)
                                                    <a href="tel:{{ $provider->contact }}"
                                                        class="text-ev-blue-600 hover:text-ev-blue-800">
                                                        {{ $provider->contact }}
                                                    </a>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                @if ($provider->email)
                                                    <a href="mailto:{{ $provider->email }}"
                                                        class="text-ev-blue-600 hover:text-ev-blue-800">
                                                        {{ $provider->email }}
                                                    </a>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tampilan mobile dan tablet -->
            <div class="lg:hidden">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    @foreach ($providers as $provider)
                        <div class="p-4 bg-white rounded-lg shadow">
                            <div class="flex min-w-0 gap-x-4">
                                @php
                                    $providerImageUrl = $resolveProviderImage($provider);
                                @endphp
                                <img src="{{ $providerImageUrl }}" alt="{{ $provider->name }}"
                                    loading="lazy"
                                    class="flex-none object-cover w-12 h-12 rounded-full"
                                    onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                                <div class="flex-auto min-w-0">
                                    <p class="text-sm font-semibold leading-6 text-gray-900">
                                        {{ $provider->name }}
                                        <span
                                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $provider->charger_locations_count }} loc
                                        </span>
                                    </p>
                                    <p class="mt-1 text-xs leading-5 text-gray-500 truncate">
                                        {{ $provider->email ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-2">
                                <p class="text-sm text-gray-600"><span class="font-semibold">Contact:</span>
                                    {{ $provider->contact ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-600"><span class="font-semibold">Price:</span>
                                    {{ $provider->price ? $provider->price : 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-600"><span class="font-semibold">Admin Fee:</span>
                                    {{ $provider->admin_fee ? $provider->admin_fee : 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-600"><span class="font-semibold">Tax:</span>
                                    {{ $provider->tax ? $provider->tax : 'N/A' }}</p>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    @if ($provider->web)
                                        <a href="{{ $provider->web }}" target="_blank" title="Website">
                                            <img src="{{ asset('svg/website-ui-web-svgrepo-com.svg') }}" alt="Website"
                                                class="w-auto h-6">
                                        </a>
                                    @endif
                                    @if ($provider->google)
                                        <a href="{{ $provider->google }}" target="_blank" title="Get it on Google Play">
                                            <img src="{{ asset('svg/Google_Play_Store_badge_EN.svg') }}"
                                                alt="Get it on Google Play" class="w-auto h-8">
                                        </a>
                                    @endif
                                    @if ($provider->ios)
                                        <a href="{{ $provider->ios }}" target="_blank"
                                            title="Download on the App Store">
                                            <img src="{{ asset('svg/Download_on_the_App_Store_Badge.svg') }}"
                                                alt="Download on the App Store" class="w-auto h-8">
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p class="text-gray-600">Tidak ada provider yang tersedia saat ini.</p>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const searchInput = document.querySelector('input[name="search"]');
            const clearFilterButton = document.getElementById('clearFilter');

            // Fungsi untuk mengirim form
            function submitForm() {
                form.submit();
            }

            // Tambahkan debounce untuk searchInput
            let debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(submitForm,
                    300); // Menunggu 300ms setelah user berhenti mengetik
            });

            clearFilterButton.addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah form submit default
                // Reset filter
                searchInput.value = '';

                // Submit form untuk me-refresh halaman dengan filter yang sudah di-reset
                submitForm();
            });
        });

        function toggleDescription(button) {
            const fullDescription = button.nextElementSibling;
            if (fullDescription.classList.contains('hidden')) {
                fullDescription.classList.remove('hidden');
                button.textContent = 'Read less';
            } else {
                fullDescription.classList.add('hidden');
                button.textContent = 'Read more';
            }
        }
    </script>
@endpush
