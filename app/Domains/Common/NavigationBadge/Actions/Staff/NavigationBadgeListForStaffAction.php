<?php

declare(strict_types=1);

namespace App\Domains\Common\NavigationBadge\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\NavigationBadge\Queries\Staff\NavigationBadgeListForStaffQuery;

final class NavigationBadgeListForStaffAction
{
    /**
     * @var array<string, string>
     */
    private const PATH_PERMISSIONS = [
        '/hospital-manage/hospitals' => AccessPermissions::BEAULAB_HOSPITAL_SHOW,
        '/hospital-manage/doctors' => AccessPermissions::BEAULAB_DOCTOR_SHOW,
        '/hospital-manage/hospital-entries' => AccessPermissions::BEAULAB_HOSPITAL_ENTRY_SHOW,
        '/customer-db-manage/events' => AccessPermissions::BEAULAB_HOSPITAL_EVENT_DB_SHOW,
        '/customer-db-manage/real-models' => AccessPermissions::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW,
        '/ads-manage/events' => AccessPermissions::BEAULAB_HOSPITAL_EVENT_SHOW,
        '/ads-manage/event-ads' => AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_SHOW,
        '/reported-post-manage/surgery-reviews' => AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW,
        '/reported-post-manage/treatment-reviews' => AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW,
        '/reported-post-manage/talks' => AccessPermissions::BEAULAB_REPORTED_TALK_SHOW,
        '/reported-post-manage/hospital-evaluations' => AccessPermissions::BEAULAB_REPORTED_HOSPITAL_EVALUATION_SHOW,
        '/reported-post-manage/chats' => AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW,
    ];

    public function __construct(
        private readonly NavigationBadgeListForStaffQuery $query,
    ) {}

    /**
     * @return array{badges: array<string, array{count: int, has_new: bool}>}
     */
    public function execute(AccountStaff $actor): array
    {
        $paths = [];

        foreach (self::PATH_PERMISSIONS as $path => $permission) {
            if (! $actor->can($permission)) {
                continue;
            }

            $paths[] = $path;
        }

        $counts = $this->query->counts($paths);
        $badges = [];

        foreach ($paths as $path) {
            $count = (int) ($counts[$path] ?? 0);
            $badges[$path] = [
                'count' => $count,
                'has_new' => $count > 0,
            ];
        }

        return [
            'badges' => $badges,
        ];
    }
}
