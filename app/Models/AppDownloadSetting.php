<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppDownloadSetting extends Model
{
    use UsesDefaultConnectionWhenTesting;
    use HasFactory;

    protected $connection = 'ev';

    protected $fillable = [
        'is_active',
        'is_closable',
        'title',
        'subtitle',
        'description',
        'badge_text',
        'android_enabled',
        'android_url',
        'android_button_text',
        'android_version',
        'android_notes',
        'ios_enabled',
        'ios_status',
        'ios_url',
        'ios_button_text',
        'ios_version',
        'ios_notes',
        'qr_code_enabled',
        'auto_popup_delay_ms',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_closable' => 'boolean',
        'android_enabled' => 'boolean',
        'ios_enabled' => 'boolean',
        'qr_code_enabled' => 'boolean',
        'auto_popup_delay_ms' => 'integer',
    ];

    /**
     * Get the single active configuration record or fallback default.
     */
    public static function current(): self
    {
        $setting = static::first();

        if (! $setting) {
            $setting = new static([
                'is_active' => true,
                'is_closable' => false,
                'title' => 'Aplikasi EV Charge ID Telah Hadir!',
                'subtitle' => 'Temukan lokasi SPKLU dan stasiun pengisian kendaraan listrik terdekat langsung dari ponsel Anda.',
                'description' => 'Akses peta persebaran lokasi SPKLU dari berbagai operator resmi di Indonesia dengan lebih cepat dan praktis melalui aplikasi mobile.',
                'badge_text' => 'Official Mobile App',
                'android_enabled' => true,
                'android_url' => 'https://play.google.com/store/apps/details?id=id.sagansa.ev',
                'android_button_text' => 'Temukan di Google Play',
                'android_version' => null,
                'android_notes' => null,
                'ios_enabled' => true,
                'ios_status' => 'coming_soon',
                'ios_url' => '',
                'ios_button_text' => 'Download di App Store',
                'ios_version' => null,
                'ios_notes' => null,
                'qr_code_enabled' => true,
                'auto_popup_delay_ms' => 300,
            ]);
        }

        return $setting;
    }
}
