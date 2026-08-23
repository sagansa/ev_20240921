<?php

namespace App\Mail;

use App\Models\Tester;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TesterRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tester $tester,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tester Baru Terdaftar — ' . $this->tester->email,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tester-registered');
    }
}
