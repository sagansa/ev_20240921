<?php

namespace Tests\Feature;

use App\Models\AppDownloadSetting;
use Tests\TestCase;

/**
 * Popup download app di halaman utama (components/app-download-modal).
 * Tombol store harus aktif berdasarkan keberadaan URL, bukan status dropdown.
 */
class AppDownloadModalTest extends TestCase
{
    private function renderModal(array $attributes = []): string
    {
        $setting = new AppDownloadSetting(array_merge([
            'is_active' => true,
            'is_closable' => true,
            'title' => 'Aplikasi EV Charge ID Telah Hadir!',
            'subtitle' => 'Subtitle test',
            'badge_text' => 'Official Mobile App',
            'android_enabled' => true,
            'android_url' => 'https://play.google.com/store/apps/details?id=id.sagansa.ev',
            'ios_enabled' => true,
            'ios_status' => 'coming_soon',
            'ios_url' => '',
            'qr_code_enabled' => true,
            'whatsapp_number' => '08111923572',
        ], $attributes));

        return view('components.app-download-modal', ['appDownloadSetting' => $setting])->render();
    }

    public function test_tombol_ios_aktif_bila_link_terisi_walau_status_coming_soon(): void
    {
        $html = $this->renderModal([
            'ios_status' => 'coming_soon',
            'ios_url' => 'https://testflight.apple.com/join/abc123',
        ]);

        $this->assertStringContainsString('href="https://testflight.apple.com/join/abc123"', $html);
        $this->assertStringNotContainsString('<div class="ev-store-btn-disabled">', $html);
    }

    public function test_tombol_ios_aktif_bila_link_terisi_dan_status_app_store(): void
    {
        $html = $this->renderModal([
            'ios_status' => 'app_store',
            'ios_url' => 'https://apps.apple.com/id/app/ev/id123456',
        ]);

        $this->assertStringContainsString('href="https://apps.apple.com/id/app/ev/id123456"', $html);
        $this->assertStringNotContainsString('<div class="ev-store-btn-disabled">', $html);
    }

    public function test_tombol_ios_disabled_bila_link_kosong(): void
    {
        $html = $this->renderModal([
            'ios_status' => 'coming_soon',
            'ios_url' => '',
        ]);

        $this->assertStringContainsString('<div class="ev-store-btn-disabled">', $html);
        $this->assertStringContainsString('Segera Hadir di', $html);
    }

    public function test_tombol_store_berada_di_atas_judul_popup(): void
    {
        $html = $this->renderModal([
            'ios_url' => 'https://apps.apple.com/id/app/ev/id123456',
        ]);

        // Anchor string yang hanya muncul di body HTML (bukan di blok <style>).
        $buttonsPos = strpos($html, 'class="ev-store-btn"');
        $headerPos = strpos($html, 'Aplikasi EV Charge ID Telah Hadir!');

        $this->assertNotFalse($buttonsPos);
        $this->assertNotFalse($headerPos);
        $this->assertLessThan($headerPos, $buttonsPos);
    }
}
