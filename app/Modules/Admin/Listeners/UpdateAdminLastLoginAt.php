<?php

namespace App\Modules\Admin\Listeners;

use App\Domains\Admin\Models\Admin;
use Illuminate\Auth\Events\Login;

final class UpdateAdminLastLoginAt
{
    public function handle(Login $event): void
    {
        if (($event->guard ?? null) !== 'admin') {
            return;
        }

        if (!($event->user instanceof Admin)) {
            return;
        }

        $event->user->forceFill([
            'last_login_at' => now(),
        ])->saveQuietly();
    }
}
