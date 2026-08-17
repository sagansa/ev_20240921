<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('ev')->create('app_download_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_closable')->default(false); // Default: mandatory/non-closable gate
            $table->string('title')->default('Aplikasi EV Charge ID Telah Hadir!');
            $table->string('subtitle')->default('Temukan lokasi SPKLU, info status charging realtime, dan panduan rute akurat langsung dari ponsel Anda.');
            $table->text('description')->nullable();
            $table->string('badge_text')->default('Official Mobile App');
            
            // Android Configuration
            $table->boolean('android_enabled')->default(true);
            $table->string('android_url')->default('https://play.google.com/store/apps/details?id=id.sagansa.ev');
            $table->string('android_button_text')->default('Download di Google Play');
            $table->string('android_version')->nullable()->default('v1.0.1 (Tersedia)');
            $table->string('android_notes')->nullable()->default('Mendukung Android 7.0+ ke atas');

            // iOS Configuration
            $table->boolean('ios_enabled')->default(true);
            $table->string('ios_status')->default('coming_soon'); // coming_soon | testflight | app_store
            $table->string('ios_url')->nullable()->default('');
            $table->string('ios_button_text')->default('Download di App Store');
            $table->string('ios_version')->nullable()->default('Segera Hadir');
            $table->string('ios_notes')->nullable()->default('Dalam tahap review App Store');

            // Additional features
            $table->boolean('qr_code_enabled')->default(true);
            $table->integer('auto_popup_delay_ms')->default(300);

            $table->timestamps();
        });

        // Insert default initial row
        DB::connection('ev')->table('app_download_settings')->insert([
            'id' => 1,
            'is_active' => true,
            'is_closable' => false,
            'title' => 'Aplikasi EV Charge ID Telah Hadir!',
            'subtitle' => 'Temukan lokasi SPKLU, info status charging realtime, dan panduan rute akurat langsung dari ponsel Anda.',
            'description' => 'Aplikasi mobile EV Charge ID kini telah tersedia untuk pengguna Android. Akses peta ribuan SPKLU se-Indonesia lebih cepat, hemat kuota, dan terhubung dengan navigasi GPS.',
            'badge_text' => 'Official Mobile App',
            'android_enabled' => true,
            'android_url' => 'https://play.google.com/store/apps/details?id=id.sagansa.ev',
            'android_button_text' => 'Download di Google Play',
            'android_version' => 'v1.0.1 (Tersedia)',
            'android_notes' => 'Mendukung Android 7.0+ ke atas',
            'ios_enabled' => true,
            'ios_status' => 'coming_soon',
            'ios_url' => '',
            'ios_button_text' => 'Download di App Store',
            'ios_version' => 'Segera Hadir',
            'ios_notes' => 'Dalam tahap review App Store',
            'qr_code_enabled' => true,
            'auto_popup_delay_ms' => 300,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('app_download_settings');
    }
};
