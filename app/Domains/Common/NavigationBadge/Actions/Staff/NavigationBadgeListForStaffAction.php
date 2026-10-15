<?php

declare(strict_types=1);

namespace App\Domains\Common\NavigationBadge\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Support\StaffNotificationTypeRegistry;

final class NavigationBadgeListForStaffAction
{
    public function __construct(private readonly StaffNotificationTypeRegistry $registry) {}

    public function execute(AccountStaff $actor): array
    {
        return ['badges' => $this->registry->badgeCounts($actor)];
    }
}
