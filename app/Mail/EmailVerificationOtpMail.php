<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $name  Nama penerima untuk personalisasi email.
     * @param  string  $otp  Kode 6 digit.
     * @param  int  $expiresInMinutes  Masa berlaku kode.
     * @param  string|null  $verificationUrl  Link verifikasi sekali-tap (URL
     *   bertanda tangan Laravel, gaya email/verify/{id}/{hash}). Null = tampil
     *   kode OTP saja.
     */
    public function __construct(
        public string $name,
        public string $otp,
        public int $expiresInMinutes = 10,
        public ?string $verificationUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            // Link-based: OTP tidak lagi ditampilkan di email (UI mobile
            // sudah tidak menerima OTP), tapi tetap digenerate utk endpoint
            // deprecated /auth/verify-otp.
            subject: 'Verifikasi Email Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-otp',
        );
    }
}
