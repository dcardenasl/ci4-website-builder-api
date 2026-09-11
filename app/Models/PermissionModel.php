<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\PermissionEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class PermissionModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $returnType = PermissionEntity::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = ['application_id', 'code', 'resource', 'action', 'description'];

    /** @var array<int, string> */
    protected array $searchableFields = ['code'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'application_id', 'resource', 'action'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'application_id', 'code', 'resource', 'action'];

    protected $validationRules = [
        'application_id' => 'required|integer',
        'code' => 'required|string|max_length[100]',
        'resource' => 'required|string|max_length[50]',
        'action' => 'required|string|max_length[50]',
        'description' => 'permit_empty|string',
    ];

    /**
     * @param list<string> $codes
     * @return list<int>
     */
    public function findIdsByCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        /** @var list<PermissionEntity> $rows */
        $rows = $this->select('id')->whereIn('code', $codes)->findAll();

        return array_values(array_map(static fn (PermissionEntity $permission): int => (int) $permission->id, $rows));
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    public function findExistingIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var list<PermissionEntity> $rows */
        $rows = $this->select('id')->whereIn('id', $ids)->findAll();

        return array_values(array_map(static fn (PermissionEntity $permission): int => (int) $permission->id, $rows));
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

        /** @var list<PermissionEntity> $rows */
        $rows = $this->select('code')->whereIn('id', $ids)->findAll();

        return array_values(array_unique(array_map(static fn (PermissionEntity $permission): string => (string) $permission->code, $rows)));
    }

    /**
     * @return list<string>
     */
    public function findCodesByApplication(int $applicationId): array
    {
        /** @var list<PermissionEntity> $rows */
        $rows = $this->select('code')
            ->where('application_id', $applicationId)
            ->orderBy('code', 'ASC')
            ->findAll();

        return array_values(array_unique(array_map(static fn (PermissionEntity $permission): string => (string) $permission->code, $rows)));
    }

    /**
     * @return list<string>
     */
    public function findAllCodes(): array
    {
        /** @var list<PermissionEntity> $rows */
        $rows = $this->select('code')->orderBy('code', 'ASC')->findAll();

        return array_values(array_unique(array_map(static fn (PermissionEntity $permission): string => (string) $permission->code, $rows)));
    }

    /**
     * @return array<int, list<array{id:int, code:string, resource:string, action:string, description:string}>>
     */
    public function groupedByApplication(): array
    {
        /** @var list<PermissionEntity> $rows */
        $rows = $this->orderBy('application_id', 'ASC')
            ->orderBy('resource', 'ASC')
            ->orderBy('action', 'ASC')
            ->findAll();

        $byApplication = [];
        foreach ($rows as $permission) {
            $applicationId = (int) $permission->application_id;
            $byApplication[$applicationId][] = [
                'id'          => (int) $permission->id,
                'code'        => (string) $permission->code,
                'resource'    => (string) $permission->resource,
                'action'      => (string) $permission->action,
                'description' => (string) ($permission->description ?? ''),
            ];
        }

        return $byApplication;
    }
}
