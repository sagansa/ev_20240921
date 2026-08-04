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
     */
    public function __construct(
        public string $name,
        public string $otp,
        public int $expiresInMinutes = 10,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Verifikasi Email Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-otp',
        );
    }
}
