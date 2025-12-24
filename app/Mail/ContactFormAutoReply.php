<?php

namespace App\Mail;

use App\Models\TallcmsContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormAutoReply extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public TallcmsContactSubmission $submission
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for contacting ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form-auto-reply',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
