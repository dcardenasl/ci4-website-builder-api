<?php

declare(strict_types=1);

namespace App\DTO\Request\Iam;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'AttachRolesRequest', required: ['role_ids'])]
readonly class AttachRolesRequestDTO extends BaseRequestDTO
{
    /** @var int[] */
    #[OA\Property(description: 'Role ids to attach to the membership', type: 'array', items: new OA\Items(type: 'integer'))]
    public array $role_ids;

    public function rules(): array
    {
        return [
            'role_ids'   => 'required',
            'role_ids.*' => 'required|integer|is_natural_no_zero',
        ];
    }

    protected function map(array $data): void
    {
        $ids = $data['role_ids'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }
        $this->role_ids = array_values(array_unique(array_map(static fn ($v) => (int) $v, $ids)));
    }

    public function toArray(): array
    {
        return ['role_ids' => $this->role_ids];
    }
}
