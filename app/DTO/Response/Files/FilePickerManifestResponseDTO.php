<?php

declare(strict_types=1);

namespace App\DTO\Response\Files;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

/**
 * Lightweight read model used by file picker consumers.
 *
 * The manifest contains metadata and direct preview URLs only. Binary content
 * and storage paths are intentionally excluded.
 */
readonly class FilePickerManifestResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param list<array<string, mixed>> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public string $version,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        foreach (($data['items'] ?? []) as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return new self(
            items: $items,
            total: (int) ($data['total'] ?? count($items)),
            version: (string) ($data['version'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
            'version' => $this->version,
        ];
    }
}
