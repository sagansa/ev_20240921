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
            'android_pre_release_flow' => false,
            'support_email' => null,
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

    public function test_tombol_store_berada_di_bawah_judul_popup(): void
    {
        $html = $this->renderModal([
            'ios_url' => 'https://apps.apple.com/id/app/ev/id123456',
        ]);

        // Anchor string yang hanya muncul di body HTML (bukan di blok <style>).
        $buttonsPos = strpos($html, 'class="ev-store-btn"');
        $headerPos = strpos($html, 'Aplikasi EV Charge ID Telah Hadir!');

        $this->assertNotFalse($buttonsPos);
        $this->assertNotFalse($headerPos);
        $this->assertGreaterThan($headerPos, $buttonsPos);
    }

    public function test_alur_2_opsi_tampil_bila_pre_release_aktif(): void
    {
        $html = $this->renderModal([
            'android_pre_release_flow' => true,
            'support_email' => 'tester@evcharge.id',
            'android_url' => 'https://play.google.com/apps/test/abc123',
        ]);

        // Tombol Play Store tidak lagi langsung navigasi, melainkan membuka step 2.
        $this->assertStringContainsString('data-pre-release="true"', $html);
        $this->assertStringContainsString('id="evAppStepAndroid"', $html);

        // Opsi 1: langkah Internal App Sharing + tombol link IAS.
        $this->assertStringContainsString('Internal App Sharing', $html);
        $this->assertStringContainsString('7 kali', $html);
        $this->assertStringContainsString('https://play.google.com/apps/test/abc123', $html);

        // Opsi 2: mailto ke email kontak dengan subject closed testing.
        $this->assertStringContainsString('mailto:tester@evcharge.id', $html);
        $this->assertStringContainsString('closed%20testing', $html);
    }

    public function test_tombol_play_langsung_ke_store_saat_sudah_production(): void
    {
        $html = $this->renderModal();

        $this->assertStringNotContainsString('data-pre-release="true"', $html);
        $this->assertStringNotContainsString('id="evAppStepAndroid"', $html);
        $this->assertStringContainsString('id="evAppStepStores"', $html);
    }

    public function test_opsi_email_fallback_whatsapp_bila_email_kontak_kosong(): void
    {
        config(['admin_notify.email' => '']);

        $html = $this->renderModal([
            'android_pre_release_flow' => true,
            'support_email' => null,
        ]);

        $this->assertStringNotContainsString('mailto:', $html);
        $this->assertStringContainsString('Minta Akses via WhatsApp', $html);
        $this->assertStringContainsString('wa.me/628111923572', $html);
    }
}
