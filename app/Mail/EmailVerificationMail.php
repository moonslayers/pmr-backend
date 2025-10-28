<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationToken;
    public $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $verificationToken)
    {
        $this->user = $user;
        $this->verificationToken = $verificationToken;

        // Generar URL de verificación
        $frontendBaseUrl = config('app.frontend_url', 'http://localhost:4200');
        $this->verificationUrl = "{$frontendBaseUrl}/#/verify-email?token={$verificationToken}";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirma tu correo electrónico - PMR',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.email_verification',
            with: [
                'user' => $this->user,
                'verificationToken' => $this->verificationToken,
                'verificationUrl' => $this->verificationUrl,
                'expiresAt' => now()->addHour()->format('d/m/Y H:i'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}