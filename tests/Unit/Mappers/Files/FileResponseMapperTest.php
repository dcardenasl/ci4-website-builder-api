<?php

declare(strict_types=1);

namespace Tests\Unit\Mappers\Files;

use App\DTO\Response\Files\FileResponseDTO;
use App\Libraries\Storage\StorageManager;
use App\Mappers\Files\FileResponseMapper;
use CodeIgniter\Test\CIUnitTestCase;

final class FileResponseMapperTest extends CIUnitTestCase
{
    public function testResolvesCurrentStorageUrlsFromOriginalAndVariantPaths(): void
    {
        $storage = $this->createMock(StorageManager::class);
        $storage->expects($this->exactly(3))
            ->method('url')
            ->willReturnMap([
                ['2026/08/25/original.webp', 'https://current.example/uploads/2026/08/25/original.webp'],
                ['2026/08/25/original_thumb.webp', 'https://current.example/uploads/2026/08/25/original_thumb.webp'],
                ['2026/08/25/original_md.webp', 'https://current.example/uploads/2026/08/25/original_md.webp'],
            ]);

        $result = (new FileResponseMapper($storage))->map([
            'id' => 7,
            'original_name' => 'original.webp',
            'stored_name' => 'original.webp',
            'mime_type' => 'image/webp',
            'size' => 100,
            'path' => '2026/08/25/original.webp',
            'url' => 'https://stale.example/uploads/2026/08/25/original.webp',
            'variants' => json_encode([
                'thumb' => [
                    'path' => '2026/08/25/original_thumb.webp',
                    'url' => 'https://stale.example/uploads/2026/08/25/original_thumb.webp',
                ],
                'md' => ['path' => '2026/08/25/original_md.webp'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->assertInstanceOf(FileResponseDTO::class, $result);
        $this->assertSame('https://current.example/uploads/2026/08/25/original.webp', $result->url);
        $this->assertSame(
            'https://current.example/uploads/2026/08/25/original_thumb.webp',
            $result->variants['thumb']['url'],
        );
        $this->assertSame(
            'https://current.example/uploads/2026/08/25/original_md.webp',
            $result->variants['md']['url'],
        );
    }

    public function testKeepsLegacyUrlWhenNoStoragePathExists(): void
    {
        $storage = $this->createMock(StorageManager::class);
        $storage->expects($this->never())->method('url');

        $result = (new FileResponseMapper($storage))->map([
            'id' => 8,
            'original_name' => 'legacy.pdf',
            'stored_name' => 'legacy.pdf',
            'mime_type' => 'application/pdf',
            'size' => 20,
            'url' => '/uploads/legacy.pdf',
        ]);

        $this->assertSame('/uploads/legacy.pdf', $result->toArray()['url']);
    }
}
