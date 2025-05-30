<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EV Charger')</title>
    <link href="{{ asset('build/assets/app-Cy5zEKbb.css ') }}" rel="stylesheet">
    <link href="{{ asset('build/assets/app-z-Rg4TxU.js ') }}" rel="stylesheet">
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    @yield('additional_head')
    @livewireStyles
    <link rel="icon" href="{{ asset('images/logo-files/favicon-32x32.png') }}" type="image/png">
</head>

<body class="flex flex-col min-h-screen bg-ev-white text-ev-gray-800">
    <nav class="fixed top-0 z-50 w-full bg-opacity-90 bg-ev-blue-800">
        <div class="container px-4 mx-auto">
            <div class="flex justify-between items-center py-4">
                <a href="{{ route('home') }}" class="flex items-center text-xl font-bold text-ev-white">
                    <img src="{{ asset('images/logo-files/logo.png') }}" alt="Sagansa EV Logo" class="mr-2 h-8">
                    Sagansa - EV
                </a>

                <button class="lg:hidden text-ev-white" onclick="toggleMenu()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>

                <ul class="hidden flex-1 justify-center items-center space-x-12 lg:flex">
                    <li class="relative group">
                        <button onclick="toggleMapDesktopDropdown()"
                            class="flex items-center transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs(['pln-map', 'map']) ? 'font-bold text-ev-green-400' : '' }}">
                            Maps
                            <svg class="ml-1 w-4 h-4 transition-transform duration-200 transform" id="map-desktop-arrow"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="map-desktop-dropdown"
                            class="hidden absolute py-2 mt-2 w-48 rounded-md shadow-xl bg-ev-blue-800">
                            <a href="{{ route('pln-map') }}"
                                class="block px-4 py-2 text-sm text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('pln-map') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                                PLN Map
                            </a>
                            <a href="{{ route('map') }}"
                                class="block px-4 py-2 text-sm text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('map') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                                Community Map
                            </a>
                        </div>
                    </li>
                    <li class="relative group">
                        <button onclick="toggleDesktopDropdown()"
                            class="flex items-center transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs(['chargers', 'pln-chargers']) ? 'font-bold text-ev-green-400' : '' }}">
                            Chargers
                            <svg class="ml-1 w-4 h-4 transition-transform duration-200 transform" id="desktop-arrow"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="desktop-dropdown"
                            class="hidden absolute py-2 mt-2 w-48 rounded-md shadow-xl bg-ev-blue-800">
                            <a href="{{ route('chargers') }}"
                                class="block px-4 py-2 text-sm text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('chargers') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                                Community Chargers
                            </a>
                            <a href="{{ route('pln-chargers') }}"
                                class="block px-4 py-2 text-sm text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('pln-chargers') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                                PLN Chargers
                            </a>
                        </div>
                    </li>
                    <li><a href="{{ route('providers') }}"
                            class="transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs('providers') ? 'font-bold text-ev-green-400' : '' }}">Providers</a>
                    </li>
                    <li><a href="{{ route('products') }}"
                            class="transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs('products') ? 'font-bold text-ev-green-400' : '' }}">Products</a>
                    </li>
                    <li><a href="{{ route('contact') }}"
                            class="transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs('contact') ? 'font-bold text-ev-green-400' : '' }}">Contact
                            Us</a></li>
                </ul>

                <ul class="hidden items-center space-x-4 lg:flex">
                    @auth
                        <li><a href="{{ route('filament.admin.auth.login') }}"
                                class="transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs('filament.admin.auth.login') ? 'font-bold text-ev-green-400' : '' }}">Apps</a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="transition duration-300 text-ev-white hover:text-ev-green-400">Logout</button>
                            </form>
                        </li>
                    @else
                        <li><a href="{{ route('filament.admin.auth.login') }}"
                                class="transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs('login') ? 'font-bold text-ev-green-400' : '' }}">Login</a>
                        </li>
                        <li><a href="{{ route('filament.admin.auth.register') }}"
                                class="transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs('register') ? 'font-bold text-ev-green-400' : '' }}">Register</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
        <div id="mobile-menu" class="hidden lg:hidden">
            <ul class="py-2 bg-ev-blue-800">
                <li class="relative">
                    <button onclick="toggleMapMobileDropdown()"
                        class="flex items-center justify-between w-full px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs(['pln-map', 'map']) ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                        Maps
                        <svg class="ml-1 w-4 h-4 transition-transform duration-200 transform" id="map-mobile-arrow"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div id="map-mobile-dropdown" class="hidden px-4 bg-ev-blue-900">
                        <a href="{{ route('pln-map') }}"
                            class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('pln-map') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                            PLN Map
                        </a>
                        <a href="{{ route('map') }}"
                            class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('map') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                            Community Map
                        </a>
                    </div>
                </li>
                <li class="relative">
                    <button onclick="toggleMobileDropdown()"
                        class="flex items-center justify-between w-full px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs(['chargers', 'pln-chargers']) ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                        Chargers
                        <svg class="ml-1 w-4 h-4 transition-transform duration-200 transform" id="mobile-arrow"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div id="mobile-dropdown" class="hidden px-4 bg-ev-blue-900">
                        <a href="{{ route('chargers') }}"
                            class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('pln-map') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                            PLN Chargers
                        </a>
                        <a href="{{ route('pln-chargers') }}"
                            class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('map') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">
                            Community Chargers
                        </a>
                    </div>
                </li>
                <li><a href="{{ route('providers') }}"
                        class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('providers') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Providers</a>
                </li>
                <li><a href="{{ route('products') }}"
                        class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('products') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Products</a>
                </li>
                <li><a href="{{ route('contact') }}"
                        class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('contact') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Contact
                        Us</a>
                </li>

                <!-- Garis pemisah -->
                <li class="my-2 border-t border-ev-blue-700"></li>

                @auth
                    <li><a href="{{ route('filament.admin.auth.login') }}"
                            class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('filament.admin.auth.login') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Apps</a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="block px-4 py-2 w-full text-left text-ev-white hover:bg-ev-blue-700">Logout</button>
                        </form>
                    </li>
                @else
                    <li><a href="{{ route('filament.admin.auth.login') }}"
                            class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('login') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Login</a>
                    </li>
                    <li><a href="{{ route('filament.admin.auth.register') }}"
                            class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('register') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Register</a>
                    </li>
                @endauth
            </ul>
        </div>
    </nav>

    <div class="flex-grow pt-16 md:pt-16">
        @yield('content')
    </div>

    @stack('scripts')

    <script>
        function toggleMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }

        function toggleDesktopDropdown() {
            const dropdown = document.getElementById('desktop-dropdown');
            const arrow = document.getElementById('desktop-arrow');
            dropdown.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        }

        function toggleMobileDropdown() {
            const dropdown = document.getElementById('mobile-dropdown');
            const arrow = document.getElementById('mobile-arrow');
            dropdown.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        }

        function toggleMapDesktopDropdown() {
            const dropdown = document.getElementById('map-desktop-dropdown');
            const arrow = document.getElementById('map-desktop-arrow');
            dropdown.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        }

        function toggleMapMobileDropdown() {
            const dropdown = document.getElementById('map-mobile-dropdown');
            const arrow = document.getElementById('map-mobile-arrow');
            dropdown.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        }

        // Update click outside handler
        document.addEventListener('click', function(event) {
            const desktopDropdown = document.getElementById('desktop-dropdown');
            const mobileDropdown = document.getElementById('mobile-dropdown');
            const mapDesktopDropdown = document.getElementById('map-desktop-dropdown');
            const mapMobileDropdown = document.getElementById('map-mobile-dropdown');

            const desktopButton = event.target.closest('.group button');
            const mobileButton = event.target.closest('.relative button');

            if (!desktopButton && !desktopDropdown.contains(event.target)) {
                desktopDropdown.classList.add('hidden');
                document.getElementById('desktop-arrow').classList.remove('rotate-180');
            }

            if (!mobileButton && !mobileDropdown.contains(event.target)) {
                mobileDropdown.classList.add('hidden');
                document.getElementById('mobile-arrow').classList.remove('rotate-180');
            }

            if (!desktopButton && !mapDesktopDropdown.contains(event.target)) {
                mapDesktopDropdown.classList.add('hidden');
                document.getElementById('map-desktop-arrow').classList.remove('rotate-180');
            }

            if (!mobileButton && !mapMobileDropdown.contains(event.target)) {
                mapMobileDropdown.classList.add('hidden');
                document.getElementById('map-mobile-arrow').classList.remove('rotate-180');
            }
        });
    </script>
</body>

</html>
