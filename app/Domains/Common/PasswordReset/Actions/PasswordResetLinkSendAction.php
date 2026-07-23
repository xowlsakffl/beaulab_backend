<?php

namespace App\Domains\Common\PasswordReset\Actions;

use App\Domains\Common\PasswordReset\Mail\PasswordResetLinkMail;
use App\Domains\Common\PasswordReset\Queries\PasswordResetTokenQuery;
use App\Domains\Common\PasswordReset\Support\PasswordResetActor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class PasswordResetLinkSendAction
{
    public function __construct(
        private readonly PasswordResetTokenQuery $tokenQuery,
    ) {}

    /**
     * @param  array{email:string}  $payload
     * @return array{message:string}
     */
    public function execute(string $actor, array $payload): array
    {
        $email = $payload['email'];
        $account = PasswordResetActor::findAccountByEmail($actor, $email);
        $message = $this->genericMessage();

        if (! $account || ! PasswordResetActor::canResetPassword($account)) {
            Log::info('비밀번호 재설정 링크 요청: 대상 없음 또는 비활성 계정', [
                'actor' => $actor,
                'email_hash' => hash('sha256', $email),
            ]);

            return ['message' => $message];
        }

        $throttleSeconds = PasswordResetActor::throttleSeconds($actor);
        if ($this->tokenQuery->recentlyCreated($actor, $email, $throttleSeconds)) {
            return ['message' => $message];
        }

        $token = Str::random(64);
        $this->tokenQuery->store($actor, $email, $token);

        $resetUrl = PasswordResetActor::resetUrl($actor, $email, $token);
        $expireMinutes = PasswordResetActor::expireMinutes($actor);

        $mail = (new PasswordResetLinkMail(
            actorLabel: PasswordResetActor::label($actor),
            resetUrl: $resetUrl,
            expireMinutes: $expireMinutes,
        ))
            ->onConnection((string) config('password_reset.mail.connection', 'redis'))
            ->onQueue((string) config('password_reset.mail.queue', 'mail'));

        Mail::to($email)->queue($mail);

        Log::info('비밀번호 재설정 링크 발송', [
            'actor' => $actor,
            'account_id' => $account->getKey(),
        ]);

        return ['message' => $message];
    }

    private function genericMessage(): string
    {
        return '입력한 이메일로 가입된 계정이 있다면 비밀번호 재설정 링크가 발송됩니다.';
    }
}
