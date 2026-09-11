<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class HospitalAccountEmailVerificationMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(private readonly string $code, private readonly int $expireMinutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[뷰랩] 병의원 계정 이메일 인증번호');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.hospital-account-email-verification', with: [
            'code' => $this->code, 'expireMinutes' => $this->expireMinutes,
        ]);
    }
}
