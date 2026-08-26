<?php

namespace App\Domains\Common\ContentReport\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;

final class ContentReportStateForStaffPolicy
{
    public function viewAny(AccountStaff $actor, ?string $targetAlias = null): bool
    {
        $permission = $this->permissionFor($targetAlias, 'show');

        return $permission !== null && $actor->can($permission);
    }

    public function update(AccountStaff $actor, ?string $targetAlias = null): bool
    {
        $permission = $this->permissionFor($targetAlias, 'update');

        return $permission !== null && $actor->can($permission);
    }

    public function updateStatus(AccountStaff $actor, ?string $targetAlias = null): bool
    {
        $permission = $this->permissionFor($targetAlias, 'status');

        return $permission !== null && $actor->can($permission);
    }

    private function permissionFor(?string $targetAlias, string $ability): ?string
    {
        return match ($targetAlias) {
            ContentReportTargetRegistry::ALIAS_TALK,
            ContentReportTargetRegistry::ALIAS_TALK_COMMENT => match ($ability) {
                'show' => AccessPermissions::BEAULAB_REPORTED_TALK_SHOW,
                'update' => AccessPermissions::BEAULAB_REPORTED_TALK_UPDATE,
                'status' => AccessPermissions::BEAULAB_REPORTED_TALK_STATUS_UPDATE,
                default => null,
            },

            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW,
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW_COMMENT => match ($ability) {
                'show' => AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW,
                'update' => AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_UPDATE,
                'status' => AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_STATUS_UPDATE,
                default => null,
            },

            ContentReportTargetRegistry::ALIAS_HOSPITAL_EVALUATION => match ($ability) {
                'show' => AccessPermissions::BEAULAB_REPORTED_HOSPITAL_EVALUATION_SHOW,
                'update' => AccessPermissions::BEAULAB_REPORTED_HOSPITAL_EVALUATION_UPDATE,
                'status' => AccessPermissions::BEAULAB_REPORTED_HOSPITAL_EVALUATION_STATUS_UPDATE,
                default => null,
            },

            ContentReportTargetRegistry::ALIAS_CHAT_MESSAGE => match ($ability) {
                'show' => AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW,
                'update' => AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_UPDATE,
                'status' => AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_STATUS_UPDATE,
                default => null,
            },

            ContentReportTargetRegistry::ALIAS_HOSPITAL_VIDEO => match ($ability) {
                'show' => AccessPermissions::BEAULAB_REPORTED_VIDEO_SHOW,
                'update' => AccessPermissions::BEAULAB_REPORTED_VIDEO_UPDATE,
                'status' => AccessPermissions::BEAULAB_REPORTED_VIDEO_STATUS_UPDATE,
                default => null,
            },

            default => null,
        };
    }
}
