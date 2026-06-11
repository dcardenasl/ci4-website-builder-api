<?php

declare(strict_types=1);

namespace App\DTO\Response\Metrics;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

/**
 * Metrics Overview Response DTO
 */
#[OA\Schema(
    schema: 'MetricsOverview',
    title: 'Metrics Overview',
    description: 'Overview of request stats, slow requests, and SLO indicators',
    required: ['request_stats', 'slow_requests', 'slo', 'timestamp']
)]
readonly class MetricsOverviewResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(
            property: 'request_stats',
            description: 'Aggregated request statistics',
            ref: '#/components/schemas/MetricsRequestStats'
        )]
        public array $request_stats,
        #[OA\Property(
            property: 'slow_requests',
            description: 'Slow requests list',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/MetricsSlowRequest')
        )]
        public array $slow_requests,
        #[OA\Property(
            property: 'slo',
            description: 'Service level objectives summary',
            type: 'object',
            properties: [
                new OA\Property(property: 'availability_percent', type: 'number', example: 99.95),
                new OA\Property(property: 'error_rate_percent', type: 'number', example: 0.05),
                new OA\Property(property: 'p95_response_time_ms', type: 'number', example: 350),
                new OA\Property(property: 'p99_response_time_ms', type: 'number', example: 900),
                new OA\Property(property: 'p95_target_ms', type: 'integer', example: 500),
                new OA\Property(property: 'p95_target_met', type: 'boolean', example: true),
            ]
        )]
        public array $slo,
        #[OA\Property(property: 'timestamp', description: 'Snapshot timestamp', example: '2026-02-26 12:00:00')]
        public string $timestamp
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            request_stats: $data['request_stats'] ?? [],
            slow_requests: $data['slow_requests'] ?? [],
            slo: $data['slo'] ?? [],
            timestamp: date('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'request_stats' => $this->request_stats,
            'slow_requests' => $this->slow_requests,
            'slo' => $this->slo,
            'timestamp' => $this->timestamp,
        ];
    }
}
