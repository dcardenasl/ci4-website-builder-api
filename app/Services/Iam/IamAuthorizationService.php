<?php

declare(strict_types=1);

namespace App\Services\Iam;

use App\Services\System\SecurityAuditLogger;
use Config\Database;
use dcardenasl\Ci4ApiCore\Services\Iam\AbstractIamAuthorizationService;

/**
 * Hierarchical authorization rules for IAM operations.
 *
 * Inherits the policy logic (assertNotSelf, isSuperAdmin, actorPermissions,
 * assertCanGrantPermissions/Roles, assertCanModifyRole, assertCanActOnSubject,
 * assertSuperAdmin) from `AbstractIamAuthorizationService`. Provides the
 * three storage hooks that bind the abstract logic to the starter's
 * `roles` / `permissions` / `role_permissions` tables.
 *
 * Constants are exposed for callers that want to refer to canonical
 * permission codes by name rather than literal strings.
 */
class IamAuthorizationService extends AbstractIamAuthorizationService
{
    public const SUPERADMIN_PERMISSION  = 'iam.superadmin-access';
    public const ADMIN_PERMISSION       = 'iam.admin-access';
    public const DEFAULT_APPLICATION_ID = 1;

    public function __construct(
        EffectivePermissionsResolver $resolver,
        SecurityAuditLogger $audit,
    ) {
        parent::__construct($resolver, $audit);
    }

    protected function superAdminPermission(): string
    {
        return self::SUPERADMIN_PERMISSION;
    }

    protected function defaultApplicationId(): int
    {
        return self::DEFAULT_APPLICATION_ID;
    }

    protected function loadRoleSystemFlag(int $roleId): bool
    {
        $db   = Database::connect();
        $row  = $db->table('roles')->where('id', $roleId)->select('is_system')->get();
        $data = $row === false ? null : $row->getRowArray();

        return $data !== null && (int) ($data['is_system'] ?? 0) === 1;
    }

    /**
     * @param array<int, int> $permissionIds
     * @return list<string>
     */
    protected function resolvePermissionCodes(array $permissionIds): array
    {
        $db    = Database::connect();
        $query = $db->table('permissions')
            ->whereIn('id', $permissionIds)
            ->select('code')
            ->get();

        if ($query === false) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (array $r) => (string) $r['code'],
            $query->getResultArray()
        )));
    }

    /**
     * @param array<int, int> $roleIds
     * @return list<string>
     */
    protected function resolveRolePermissionCodes(array $roleIds): array
    {
        $db    = Database::connect();
        $query = $db->table('role_permissions rp')
            ->select('p.code')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->whereIn('rp.role_id', $roleIds)
            ->get();

        if ($query === false) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (array $r) => (string) $r['code'],
            $query->getResultArray()
        )));
    }
}
