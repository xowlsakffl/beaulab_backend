<?php

namespace App\Domains\AccountStaff\Dto\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Support\StaffPermissionCatalog;

final class StaffManagementDto
{
    public static function fromModel(AccountStaff $staff, bool $detail = false): array
    {
        $data = $staff->only(['id', 'name', 'nickname', 'phone', 'email', 'department', 'job_title', 'status']);
        $data['role'] = $staff->roles->first()?->name;
        $data['version'] = (int) $staff->lock_version;
        $data['created_at'] = $staff->created_at?->toISOString();
        $data['updated_at'] = $staff->updated_at?->toISOString();
        if ($detail) {
            $data['permissions'] = StaffPermissionCatalog::activeNames($staff->getAllPermissions()->pluck('name')->sort()->values()->all());
        }

        return $data;
    }
}
