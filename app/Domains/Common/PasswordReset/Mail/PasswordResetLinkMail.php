<?php

namespace App\Domains\Common\PasswordReset\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PasswordResetLinkMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        private readonly string $actorLabel,
        private readonly string $resetUrl,
        private readonly int $expireMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[뷰랩] {$this->actorLabel} 비밀번호 재설정 안내",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.password-reset-link',
            with: [
                'actorLabel' => $this->actorLabel,
                'resetUrl' => $this->resetUrl,
                'expireMinutes' => $this->expireMinutes,
            ],
        );
    }
}
