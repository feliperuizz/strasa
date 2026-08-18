<?php

namespace App\Mail;

use App\Models\CompanyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de convite. Implementa ShouldQueue: vai para a fila (database) e é
 * processada pelo worker disparado via cron (schedule:run) no cPanel.
 */
class CompanyInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CompanyInvitation $invitation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Você foi convidado para '.$this->invitation->company->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation',
            with: [
                'acceptUrl' => route('invitations.accept', $this->invitation->token),
                'companyName' => $this->invitation->company->name,
                'inviterName' => $this->invitation->inviter?->name,
            ],
        );
    }
}
