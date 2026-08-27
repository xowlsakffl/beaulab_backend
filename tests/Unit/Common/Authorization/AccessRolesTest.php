<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Authorization;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use PHPUnit\Framework\TestCase;

final class AccessRolesTest extends TestCase
{
    public function test_admin_can_edit_resources_but_cannot_update_statuses(): void
    {
        $roles = AccessRoles::mapByGuard()[AccessPermissions::GUARD_STAFF];
        $adminPermissions = $roles[AccessRoles::BEAULAB_ADMIN];
        $statusPermissions = $this->staffStatusPermissions();

        self::assertContains(AccessPermissions::BEAULAB_HOSPITAL_CREATE, $adminPermissions);
        self::assertContains(AccessPermissions::BEAULAB_HOSPITAL_UPDATE, $adminPermissions);
        self::assertContains(AccessPermissions::BEAULAB_HOSPITAL_EVENT_UPDATE, $adminPermissions);
        self::assertSame([], array_values(array_intersect($adminPermissions, $statusPermissions)));
    }

    public function test_super_admin_has_every_status_update_permission(): void
    {
        $roles = AccessRoles::mapByGuard()[AccessPermissions::GUARD_STAFF];
        $superAdminPermissions = $roles[AccessRoles::BEAULAB_SUPER_ADMIN];

        self::assertEqualsCanonicalizing(
            $this->staffStatusPermissions(),
            array_values(array_intersect($superAdminPermissions, $this->staffStatusPermissions())),
        );
    }

    public function test_admin_note_write_permission_is_limited_to_writable_roles(): void
    {
        $rolesByGuard = AccessRoles::mapByGuard();

        self::assertContains(
            AccessPermissions::COMMON_ADMIN_NOTE_WRITE,
            $rolesByGuard[AccessPermissions::GUARD_STAFF][AccessRoles::BEAULAB_ADMIN],
        );
        self::assertNotContains(
            AccessPermissions::COMMON_ADMIN_NOTE_WRITE,
            $rolesByGuard[AccessPermissions::GUARD_STAFF][AccessRoles::BEAULAB_STAFF],
        );
        self::assertContains(
            AccessPermissions::COMMON_ADMIN_NOTE_WRITE,
            $rolesByGuard[AccessPermissions::GUARD_HOSPITAL][AccessRoles::HOSPITAL_OWNER],
        );
        self::assertContains(
            AccessPermissions::COMMON_ADMIN_NOTE_WRITE,
            $rolesByGuard[AccessPermissions::GUARD_BEAUTY][AccessRoles::BEAUTY_OWNER],
        );
        self::assertNotContains(
            AccessPermissions::COMMON_ADMIN_NOTE_WRITE,
            $rolesByGuard[AccessPermissions::GUARD_BEAUTY][AccessRoles::BEAUTY_STAFF],
        );
    }

    /**
     * @return list<string>
     */
    private function staffStatusPermissions(): array
    {
        return array_values(array_filter(
            AccessPermissions::byGuard()[AccessPermissions::GUARD_STAFF],
            static fn (string $permission): bool => str_ends_with($permission, '.status_update'),
        ));
    }
}
