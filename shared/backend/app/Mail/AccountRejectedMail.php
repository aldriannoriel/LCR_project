<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $reason)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Registration Status Update',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account_rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

