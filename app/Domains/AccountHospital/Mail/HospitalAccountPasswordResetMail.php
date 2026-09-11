<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class HospitalAccountPasswordResetMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(private readonly string $hospitalName, private readonly string $resetUrl, private readonly int $expireMinutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[뷰랩] 병의원 계정 비밀번호 재설정');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.hospital-account-password-reset', with: [
            'hospitalName' => $this->hospitalName, 'resetUrl' => $this->resetUrl, 'expireMinutes' => $this->expireMinutes,
        ]);
    }
}
