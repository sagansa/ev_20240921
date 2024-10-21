<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EV Charger')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('additional_head')
</head>

<body class="flex flex-col min-h-screen bg-ev-white text-ev-gray-800">
    <nav class="fixed top-0 z-50 w-full bg-ev-blue-800 bg-opacity-90">
        <div class="container px-4 mx-auto">
            <div class="flex items-center justify-between py-4">
                <a href="{{ route('home') }}" class="text-xl font-bold text-ev-white">Sagansa - EV Charger</a>

                <button class="lg:hidden text-ev-white" onclick="toggleMenu()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>

                <ul class="items-center justify-center flex-1 hidden space-x-12 lg:flex">
                    <li><a href="{{ route('map') }}"
                            class="transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs('map') ? 'font-bold text-ev-green-400' : '' }}">Map</a>
                    </li>
                    <li><a href="{{ route('chargers') }}"
                            class="transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs('chargers') ? 'font-bold text-ev-green-400' : '' }}">Chargers</a>
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

                <ul class="items-center hidden space-x-4 lg:flex">
                    @auth
                        <li><a href="{{ route('filament.admin.auth.login') }}"
                                class="transition duration-300 text-ev-white hover:text-ev-green-400 {{ request()->routeIs('dashboard') ? 'font-bold text-ev-green-400' : '' }}">Apps</a>
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
                <li><a href="{{ route('map') }}"
                        class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('map') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Map</a>
                </li>
                <li><a href="{{ route('chargers') }}"
                        class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('chargers') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Chargers</a>
                </li>
                <li><a href="{{ route('providers') }}"
                        class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('providers') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Providers</a>
                </li>
                <li><a href="{{ route('products') }}"
                        class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('products') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Products</a>
                </li>
                @auth
                    <li><a href="{{ route('filament.admin.auth.login') }}"
                            class="block px-4 py-2 text-ev-white hover:bg-ev-blue-700 {{ request()->routeIs('dashboard') ? 'font-bold bg-ev-blue-700 text-ev-green-400' : '' }}">Apps</a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="block w-full px-4 py-2 text-left text-ev-white hover:bg-ev-blue-700">Logout</button>
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
    </script>
</body>

</html>
