<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvitationMail extends Mailable
{
    public function __construct(public Invitation $invitation, public string $url)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Undangan ' . $this->invitation->event->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitation');
    }
}
