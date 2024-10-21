@extends('layouts.main')

@section('title', 'Home - EV Charger')

@section('content')
    <section id="contact" class="py-16 bg-cream">
        <div class="container px-4 mx-auto lg:px-8">
            <div class="container grid grid-cols-1 gap-8 mx-auto sm:grid-cols-2 lg:grid-cols-4">
                <!-- Sagansa and Home link -->
                <div>
                    <a href="#home" class="text-green hover:underline">
                        <h2 class="mb-4 text-2xl font-bold text-green">SAGANSA</h2>
                    </a>
                </div>

                <!-- Media Sosial -->
                <div>
                    <h3 class="mb-4 text-xl font-semibold text-green">Media Sosial</h3>

                    <div class="flex space-x-4">
                        <a href="https://www.youtube.com/dityoenggar" target="_blank" class="text-gray-700 hover:text-green">
                            <img src="svg/youtube-color.svg" alt="Youtube" class="h-8">
                        </a>
                        <a href="https://www.instagram.com/sabiowear" target="_blank"
                            class="text-gray-700 hover:text-green">
                            <img src="svg/instagram-color.svg" alt="Instagram" class="h-8">
                        </a>
                        <a href="https://www.facebook.com/dityo.enggar" target="_blank"
                            class="text-gray-700 hover:text-green">
                            <img src="svg/facebook-color.svg" alt="Facebook" class="h-8">
                        </a>
                        <a href="https://www.x.com/@8632dit" target="_blank" class="text-gray-700 hover:text-green">
                            <img src="svg/twitter-color.svg" alt="X" class="h-8">
                        </a>
                        <a href="https://www.linkedin.com/in/dityo-enggar-452594251/" target="_blank"
                            class="text-gray-700 hover:text-green">
                            <img src="svg/linkedin-color.svg" alt="LinkedIn" class="h-8">
                        </a>
                    </div>

                </div>

                <!-- Kontak -->
                <div>
                    <h3 class="mb-4 text-xl font-semibold text-green">Kontak</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <a href="https://wa.me/628111923572" target="_blank"
                                class="flex items-center text-gray-700 hover:text-green">
                                <img src="svg/whatsapp-color.svg" alt="Whatsapp" class="h-8">
                                <div class="mx-2">+62 8111 923 572</div>
                            </a>
                        </li>
                        <li class="flex items-center">
                            <a href="mailto:admin@sagansa.id" class="flex items-center text-gray-700 hover:text-green">
                                <img src="svg/email-color.svg" alt="Email" class="h-8">
                                <div class="mx-2">admin@sagansa.id</div>
                            </a>
                        </li>
                        <li class="flex items-center">
                            <a href="https://maps.app.goo.gl/zhCcVgpfJX19kpJ8A"
                                class="flex items-center text-gray-700 hover:text-green">
                                <img src="svg/maps-color.svg" alt="Address" class="h-8">
                                <div class="mx-2">
                                    <p class="font-bold">PT Sagansa Engineering Indonesia</p>
                                    <p>Apartement mediterania Garden Residence I tower B, Daerah Khusus Ibukota Jakarta
                                        11480</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Online Shop -->
                <div>
                    <h3 class="mb-4 text-xl font-semibold text-green">Official Store</h3>
                    <div class="flex space-x-4">
                        <a href="https://www.tokopedia.com/daehwa-1" target="_blank" class="text-gray-700 hover:text-green">
                            <img src="svg/Tokopedia_Mascot.png" alt="Tokopedia" class="h-8">
                        </a>
                        <a href="https://shopee.co.id/sabiowear10" target="_blank" class="text-gray-700 hover:text-green">
                            <img src="svg/shopee-color.svg" alt="Shopee" class="h-8">
                        </a>
                        {{-- <a href="https://www.bukalapak.com" target="_blank" class="text-gray-700 hover:text-green">
                        <img src="svg/bukalapak.svg" alt="Bukalapak" class="h-8">
                    </a>
                    <a href="https://www.lazada.co.id" target="_blank" class="text-gray-700 hover:text-green">
                        <img src="svg/lazada.svg" alt="Lazada" class="h-8"> --}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
