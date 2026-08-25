<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Domains;

use App\Libraries\Domains\DomainFileUsageClient;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\DomainWebhooks;

class DomainFileUsageClientTest extends CIUnitTestCase
{
    public function testCollectUsagesSignsAndMapsDomainResponse(): void
    {
        $config = $this->config('https://domain.test/', 'shared-secret', 7);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn(json_encode([
            'status' => 'success',
            'data' => [
                'usages' => [
                    [
                        'source' => 'domain',
                        'resource' => 'pages',
                        'resource_id' => 12,
                        'label' => 'Home',
                        'role' => 'hero',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $http = $this->createMock(CURLRequest::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://domain.test/api/v1/internal/files/42/usage',
                $this->callback(function (array $options): bool {
                    $timestamp = (string) ($options['headers']['X-Hub-Timestamp'] ?? '');

                    return ctype_digit($timestamp)
                        && ($options['timeout'] ?? null) === 7
                        && ($options['headers']['X-Hub-Signature'] ?? null) === hash_hmac(
                            'sha256',
                            "GET\n/api/v1/internal/files/42/usage\n{$timestamp}",
                            'shared-secret'
                        );
                })
            )
            ->willReturn($response);

        $client = new DomainFileUsageClient($config, $http);

        $this->assertSame([
            [
                'source' => 'domain',
                'resource' => 'pages',
                'resource_id' => 12,
                'label' => 'Home',
                'role' => 'hero',
            ],
        ], $client->collectUsages(42));
    }

    public function testUnreachableDomainFailsOpenForOptionalIntegration(): void
    {
        $config = $this->config('https://domain.test', 'shared-secret', 3);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(503);

        $http = $this->createMock(CURLRequest::class);
        $http->method('request')->willReturn($response);

        $client = new DomainFileUsageClient($config, $http);

        $this->assertSame([], $client->collectUsages(42));
    }

    private function config(string $url, string $secret, int $timeout): DomainWebhooks
    {
        $config = new DomainWebhooks();
        $config->domains = ['cms' => $url];
        $config->internalSecret = $secret;
        $config->httpTimeoutSeconds = $timeout;

        return $config;
    }
}
