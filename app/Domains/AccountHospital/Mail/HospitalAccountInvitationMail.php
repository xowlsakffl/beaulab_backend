<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class HospitalAccountInvitationMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        private readonly string $hospitalName,
        private readonly string $invitationUrl,
        private readonly int $expireHours,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[뷰랩] 병의원 관리자 계정 생성 안내',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.hospital-account-invitation',
            with: [
                'hospitalName' => $this->hospitalName,
                'invitationUrl' => $this->invitationUrl,
                'expireHours' => $this->expireHours,
            ],
        );
    }
}
