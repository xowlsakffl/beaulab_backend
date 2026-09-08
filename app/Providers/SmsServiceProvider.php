<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Common\Sms\Contracts\SmsProvider;
use App\Domains\Common\Sms\Providers\DisabledSmsProvider;
use App\Domains\Common\Sms\Providers\LogSmsProvider;
use Illuminate\Support\ServiceProvider;

final class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsProvider::class, static function (): SmsProvider {
            if (! (bool) config('sms.enabled', false)) {
                return new DisabledSmsProvider;
            }

            return match (mb_strtolower((string) config('sms.provider', 'log'))) {
                'log' => app()->environment(['local', 'testing']) ? new LogSmsProvider : new DisabledSmsProvider,
                default => new DisabledSmsProvider,
            };
        });
    }
}
