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

    private function permissionFor(?string $targetAlias, string $ability): ?string
    {
        return match ($targetAlias) {
            ContentReportTargetRegistry::ALIAS_TALK,
            ContentReportTargetRegistry::ALIAS_TALK_COMMENT => $ability === 'show'
                ? AccessPermissions::BEAULAB_REPORTED_TALK_SHOW
                : AccessPermissions::BEAULAB_REPORTED_TALK_UPDATE,

            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW,
            ContentReportTargetRegistry::ALIAS_HOSPITAL_REVIEW_COMMENT => $ability === 'show'
                ? AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW
                : AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_UPDATE,

            ContentReportTargetRegistry::ALIAS_HOSPITAL_EVALUATION => $ability === 'show'
                ? AccessPermissions::BEAULAB_REPORTED_HOSPITAL_EVALUATION_SHOW
                : AccessPermissions::BEAULAB_REPORTED_HOSPITAL_EVALUATION_UPDATE,

            ContentReportTargetRegistry::ALIAS_CHAT_MESSAGE => $ability === 'show'
                ? AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW
                : AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_UPDATE,

            ContentReportTargetRegistry::ALIAS_HOSPITAL_VIDEO => $ability === 'show'
                ? AccessPermissions::BEAULAB_REPORTED_VIDEO_SHOW
                : AccessPermissions::BEAULAB_REPORTED_VIDEO_UPDATE,

            default => null,
        };
    }
}
