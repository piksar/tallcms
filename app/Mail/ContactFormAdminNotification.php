<?php

namespace App\Mail;

use App\Models\TallcmsContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public TallcmsContactSubmission $submission
    ) {}

    public function envelope(): Envelope
    {
        // Sanitize subject to prevent header injection
        $senderName = $this->submission->name
            ? str_replace(["\r", "\n"], '', $this->submission->name)
            : 'Someone';

        return new Envelope(
            subject: "New Contact Form Submission from {$senderName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form-admin',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
