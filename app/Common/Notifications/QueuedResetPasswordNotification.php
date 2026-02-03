<?php


namespace App\Common\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class QueuedResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable)
    {
        Log::info('QueuedResetPasswordNotification toMail()', [
            'mail_default' => config('mail.default'),
            'smtp_host' => config('mail.mailers.smtp.host'),
            'smtp_port' => config('mail.mailers.smtp.port'),
            'to' => $notifiable->email ?? null,
        ]);

        return parent::toMail($notifiable);
    }

    // 필요하면 여기서 queue/connection 지정:
    // public string $connection = 'redis';
    // public string $queue = 'mail';
}
