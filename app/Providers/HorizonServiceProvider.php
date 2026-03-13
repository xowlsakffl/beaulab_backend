<?php

namespace App\Providers;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Horizon::auth(function (Request $request): bool {
            $user = $request->user('tool_staff');

            return $user instanceof AccountStaff
                && Gate::forUser($user)->allows('viewTool');
        });

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    protected function gate(): void
    {
        // InternalToolServiceProvider 에서 viewTool Gate를 공통 정의한다.
    }
}
