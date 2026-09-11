<?php

declare(strict_types=1);

namespace App\Models;

class UserRoleModel extends \dcardenasl\Ci4ApiCore\Models\BaseAuditableModel
{
    protected $table         = 'user_roles';
    protected $primaryKey    = 'user_id';
    protected $returnType    = 'array';
    protected $useAutoIncrement = false;
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id', 'role_id', 'assigned_at', 'assigned_by_user_id'];

    public function pairExists(int $userId, int $roleId): bool
    {
        return $this->where('user_id', $userId)->where('role_id', $roleId)->countAllResults() > 0;
    }

    public function assign(int $userId, int $roleId, ?int $assignedBy = null): void
    {
        $this->insert([
            'user_id'             => $userId,
            'role_id'             => $roleId,
            'assigned_at'         => date('Y-m-d H:i:s'),
            'assigned_by_user_id' => $assignedBy,
        ]);
    }

    /**
     * @param list<int> $roleIds
     */
    public function assignMany(int $userId, array $roleIds, ?int $assignedBy = null): void
    {
        if ($roleIds === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = array_map(static fn (int $roleId): array => [
            'user_id'             => $userId,
            'role_id'             => $roleId,
            'assigned_at'         => $now,
            'assigned_by_user_id' => $assignedBy,
        ], $roleIds);

        $this->insertBatch($rows);
    }

    public function remove(int $userId, int $roleId): void
    {
        $this->where('user_id', $userId)->where('role_id', $roleId)->delete();
    }

    /**
     * @param list<int> $roleIds
     */
    public function removeMany(int $userId, array $roleIds): void
    {
        if ($roleIds === []) {
            return;
        }

        $this->where('user_id', $userId)->whereIn('role_id', $roleIds)->delete();
    }

    /**
     * @return list<int>
     */
    public function getRoleIdsForUser(int $userId): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->select('role_id')->where('user_id', $userId)->findAll();

        return array_values(array_map(static fn (array $row): int => (int) $row['role_id'], $rows));
    }

    /**
     * @return list<array{id:int, code:string, name:string, description:string|null, is_system:int}>
     */
    public function getRolesForUser(int $userId): array
    {
        $query = $this->builder()
            ->select('roles.id, roles.code, roles.name, roles.description, roles.is_system')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->orderBy('roles.name', 'ASC')
            ->get();

        $rows = $query === false ? [] : $query->getResultArray();

        return array_values(array_map(static fn (array $row): array => [
            'id'          => (int) $row['id'],
            'code'        => (string) $row['code'],
            'name'        => (string) $row['name'],
            'description' => $row['description'] !== null ? (string) $row['description'] : null,
            'is_system'   => (int) $row['is_system'],
        ], $rows));
    }

    public function userHasPermissionCode(int $userId, string $permissionCode): bool
    {
        $query = $this->builder()
            ->select('1', false)
            ->join('role_permissions', 'role_permissions.role_id = user_roles.role_id')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('user_roles.user_id', $userId)
            ->where('permissions.code', $permissionCode)
            ->limit(1)
            ->get();

        return $query !== false && $query->getRowArray() !== null;
    }

    public function userHasRoleCode(int $userId, string $roleCode): bool
    {
        return $this->builder()
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.code', $roleCode)
            ->countAllResults() > 0;
    }

    /**
     * @return list<string>
     */
    public function getPermissionCodesForUser(int $userId): array
    {
        $query = $this->builder()
            ->select('permissions.code')
            ->distinct()
            ->join('role_permissions', 'role_permissions.role_id = user_roles.role_id')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('user_roles.user_id', $userId)
            ->orderBy('permissions.code', 'ASC')
            ->get();

        $rows = $query === false ? [] : $query->getResultArray();

        return array_values(array_unique(array_map(static fn (array $row): string => (string) $row['code'], $rows)));
    }

    /**
     * @return list<string>
     */
    public function getPermissionCodesForUserAndApplication(int $userId, int $applicationId): array
    {
        $query = $this->builder()
            ->select('permissions.code')
            ->distinct()
            ->join('role_permissions', 'role_permissions.role_id = user_roles.role_id')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('user_roles.user_id', $userId)
            ->where('permissions.application_id', $applicationId)
            ->orderBy('permissions.code', 'ASC')
            ->get();

        $rows = $query === false ? [] : $query->getResultArray();

        return array_values(array_unique(array_map(static fn (array $row): string => (string) $row['code'], $rows)));
    }
}
