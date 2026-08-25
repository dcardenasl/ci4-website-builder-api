<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\RoleEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class RoleModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $returnType = RoleEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['application_id', 'code', 'name', 'description', 'is_system'];

    /** @var array<int, string> */
    protected array $searchableFields = ['code', 'name'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'application_id', 'is_system', 'code'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'application_id', 'code', 'name', 'is_system'];

    protected $validationRules = [
        'application_id' => 'permit_empty|integer',
        'code' => 'required|string|max_length[100]',
        'name' => 'required|string|max_length[100]',
        'description' => 'permit_empty|string',
        'is_system' => 'permit_empty|in_list[0,1]',
    ];

    public function findIdByCode(string $code): ?int
    {
        /** @var RoleEntity|null $role */
        $role = $this->select('id')->where('code', $code)->first();

        return $role !== null ? (int) $role->id : null;
    }

    /**
     * @param list<int> $ids
     * @return list<string>
     */
    public function findCodesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var list<RoleEntity> $roles */
        $roles = $this->select('code')->whereIn('id', $ids)->findAll();

        return array_values(array_map(static fn (RoleEntity $role): string => (string) $role->code, $roles));
    }

    public function existsById(int $id): bool
    {
        return $this->select('id')->find($id) !== null;
    }

    public function isSystemRole(int $id): bool
    {
        /** @var RoleEntity|null $role */
        $role = $this->select('is_system')->find($id);

        return $role !== null && (bool) $role->is_system;
    }

    /**
     * @return list<array{id:int, code:string, name:string, description:string|null, is_system:bool, is_self_assignable:bool}>
     */
    public function listAllOrderedByName(): array
    {
        /** @var list<RoleEntity> $roles */
        $roles = $this->select('id, code, name, description, is_system, is_self_assignable')
            ->orderBy('name', 'ASC')
            ->findAll();

        return array_map(static fn (RoleEntity $role): array => [
            'id'                 => (int) $role->id,
            'code'               => (string) $role->code,
            'name'               => (string) $role->name,
            'description'        => $role->description !== null ? (string) $role->description : null,
            'is_system'          => (bool) $role->is_system,
            'is_self_assignable' => (bool) ($role->is_self_assignable ?? false),
        ], $roles);
    }

    /**
     * @return list<array{id:int, code:string, name:string, description:string|null, is_system:bool}>
     */
    public function listAllOrderedByCode(): array
    {
        /** @var list<RoleEntity> $roles */
        $roles = $this->select('id, code, name, description, is_system')
            ->orderBy('code', 'ASC')
            ->findAll();

        return array_map(static fn (RoleEntity $role): array => [
            'id'          => (int) $role->id,
            'code'        => (string) $role->code,
            'name'        => (string) $role->name,
            'description' => $role->description !== null ? (string) $role->description : null,
            'is_system'   => (bool) $role->is_system,
        ], $roles);
    }
}
