<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Internal;

use Config\Services;
use Tests\Support\ApiTestCase;
use Tests\Support\Traits\AuthTestTrait;

final class InternalFileMetaControllerTest extends ApiTestCase
{
    use AuthTestTrait;

    public function testBatchMetaResolvesUrlsFromCurrentStoragePaths(): void
    {
        $rawKey = $this->createActiveApiKey();
        $userId = $this->createUser('internal-file-meta@example.com', 'ValidPass123!');

        \Config\Database::connect()->table('files')->insert([
            'user_id' => $userId,
            'original_name' => 'photo.webp',
            'stored_name' => 'photo.webp',
            'mime_type' => 'image/webp',
            'size' => 100,
            'storage_driver' => 'local',
            'path' => '2026/08/25/photo.webp',
            'url' => 'https://stale.example/uploads/2026/08/25/photo.webp',
            'variants' => json_encode([
                'thumb' => [
                    'path' => '2026/08/25/photo_thumb.webp',
                    'url' => 'https://stale.example/uploads/2026/08/25/photo_thumb.webp',
                ],
            ], JSON_THROW_ON_ERROR),
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);
        $fileId = (int) \Config\Database::connect()->insertID();

        $result = $this->withHeaders(['X-App-Key' => $rawKey])
            ->get('/api/v1/internal/files/batch-meta?ids=' . $fileId);

        $result->assertStatus(200);
        $json = $this->getResponseJson($result);
        $item = $json['data'][(string) $fileId] ?? $json['data'][$fileId] ?? null;

        $this->assertIsArray($item);
        $this->assertSame(base_url('uploads/2026/08/25/photo.webp'), $item['url']);
        $this->assertSame(
            base_url('uploads/2026/08/25/photo_thumb.webp'),
            $item['variants']['thumb']['url'],
        );
    }

    public function testBatchMetaRejectsMoreThanTwoHundredIds(): void
    {
        $rawKey = $this->createActiveApiKey();
        $query = implode('&', array_map(
            static fn (int $id): string => 'ids[]=' . $id,
            range(1, 201),
        ));

        $result = $this->withHeaders(['X-App-Key' => $rawKey])
            ->get('/api/v1/internal/files/batch-meta?' . $query);

        $result->assertStatus(400);
    }

    private function createActiveApiKey(): string
    {
        $material = Services::apiKeyMaterialService();
        $rawKey = $material->generateRawKey();

        \Config\Database::connect()->table('api_keys')->insert([
            'name' => 'internal-file-meta-test-key',
            'key_prefix' => substr($rawKey, 0, 8),
            'key_hash' => $material->hash($rawKey),
            'is_active' => 1,
            'rate_limit_requests' => 600,
            'rate_limit_window' => 60,
            'user_rate_limit' => 60,
            'ip_rate_limit' => 200,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $rawKey;
    }
}
