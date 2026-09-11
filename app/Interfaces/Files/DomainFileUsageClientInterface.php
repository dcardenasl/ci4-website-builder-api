<?php

declare(strict_types=1);

namespace App\Interfaces\Files;

interface DomainFileUsageClientInterface
{
    /**
     * Ask every configured domain app whether it references the given file.
     * A domain that is unreachable is logged and treated as reporting no
     * usages, so an optional integration outage does not break Hub operations.
     *
     * @return list<array{source: string, resource: string, resource_id: int, label: string|null, role: string}>
     */
    public function collectUsages(int $fileId): array;

    /**
     * Notify configured domain apps that cached metadata for a file is stale.
     * This is best effort and must never roll back a successful Hub operation.
     */
    public function broadcastInvalidate(int $fileId): void;
}
