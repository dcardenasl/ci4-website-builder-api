<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Entities\FileEntity;
use App\Interfaces\Files\FileRepositoryInterface;
use App\Libraries\Storage\StorageManager;
use App\Services\Files\FileService;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Services\AuditServiceInterface;
use Tests\Support\Traits\CustomAssertionsTrait;

/**
 * FileService Unit Tests
 *
 * Tests file operations with mocked dependencies.
 */
class FileServiceTest extends CIUnitTestCase
{
    use CustomAssertionsTrait;

    protected FileService $service;
    protected FileRepositoryInterface $mockFileRepository;
    protected StorageManager $mockStorage;
    protected AuditServiceInterface $mockAuditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFileRepository = $this->createMock(FileRepositoryInterface::class);
        $mockFileModel = $this->createMock(\App\Models\FileModel::class);
        $this->mockFileRepository->method('getModel')->willReturn($mockFileModel);

        $this->mockStorage = $this->createMock(StorageManager::class);
        $this->mockAuditService = $this->createMock(AuditServiceInterface::class);

        // Inject real processors and generator as they are mostly stateless and hard to mock without overhead
        $responseMapper = new \dcardenasl\Ci4ApiCore\Mappers\DtoResponseMapper(
            \App\DTO\Response\Files\FileResponseDTO::class
        );

        $mockVariantProcessor = $this->createMock(\App\Libraries\Files\ImageVariantProcessor::class);
        $mockVariantProcessor->method('generate')
            ->willReturn(['variants' => [], 'dimensions' => ['width' => null, 'height' => null]]);

        $this->service = new FileService(
            $this->mockFileRepository,
            $responseMapper,
            $this->mockStorage,
            $this->mockAuditService,
            new \App\Libraries\Files\FilenameGenerator($this->mockStorage),
            new \App\Libraries\Files\MultipartProcessor(),
            new \App\Libraries\Files\Base64Processor(),
            $mockVariantProcessor,
            $this->createMock(\App\Interfaces\Files\FileReferenceRepositoryInterface::class),
        );
    }

    // ==================== UPLOAD VALIDATION TESTS ====================

    public function testUploadWithoutFileThrowsException(): void
    {
        $this->expectException(BadRequestException::class);

        new \App\DTO\Request\Files\FileUploadRequestDTO(['user_id' => 1], service('validation'));
    }

    public function testUploadWithoutUserIdThrowsException(): void
    {
        $mockFile = $this->createMockUploadedFile();

        $this->expectException(\dcardenasl\Ci4ApiCore\Exceptions\AuthenticationException::class);

        new \App\DTO\Request\Files\FileUploadRequestDTO(['file' => $mockFile], service('validation'));
    }

    public function testUploadWithInvalidFileObjectThrowsException(): void
    {
        $this->expectException(BadRequestException::class);

        new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => 12345, // Integer is always invalid
            'user_id' => 1,
        ], service('validation'));
    }

    public function testUploadWithInvalidFileThrowsException(): void
    {
        $mockFile = $this->createMockUploadedFile(['isValid' => false]);

        $this->expectException(BadRequestException::class);

        $this->service->upload(new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => $mockFile,
            'user_id' => 1,
        ], service('validation')));
    }

    public function testUploadWithFileTooLargeThrowsValidationException(): void
    {
        $mockFile = $this->createMockUploadedFile([
            'size' => 99999999, // Very large file
        ]);

        $this->expectException(ValidationException::class);

        $this->service->upload(new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => $mockFile,
            'user_id' => 1,
        ], service('validation')));
    }

    public function testUploadWithInvalidExtensionThrowsValidationException(): void
    {
        $mockFile = $this->createMockUploadedFile([
            'extension' => 'exe',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->upload(new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => $mockFile,
            'user_id' => 1,
        ], service('validation')));
    }

    public function testUploadSuccessfullyStoresFileAndReturnsCreated(): void
    {
        // Create a real temp file so file_get_contents works
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tempFile, 'fake file contents');

        $mockFile = $this->createMockUploadedFile([
            'tempName' => $tempFile,
            'name' => 'photo.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        // Mock storage
        $this->mockStorage
            ->expects($this->once())
            ->method('put')
            ->willReturn(true);

        $this->mockStorage
            ->method('getDriverName')
            ->willReturn('local');

        $this->mockStorage
            ->method('url')
            ->willReturn('http://localhost/uploads/2026/02/17/photo_abc123.jpg');

        // Mock FileRepository
        $savedEntity = $this->createFileEntity([
            'id' => 1,
            'original_name' => 'photo.jpg',
            'size' => 1024,
            'mime_type' => 'image/jpeg',
            'url' => 'http://localhost/uploads/2026/02/17/photo_abc123.jpg',
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);

        $this->mockFileRepository
            ->method('insert')
            ->willReturn(1);

        $this->mockFileRepository
            ->method('find')
            ->willReturn($savedEntity);

        $result = $this->service->upload(new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => $mockFile,
            'user_id' => 1,
        ], service('validation')));

        $this->assertInstanceOf(\App\DTO\Response\Files\FileResponseDTO::class, $result);
        $data = $result->toArray();
        $this->assertEquals('photo.jpg', $data['original_name']);
        $this->assertEquals(1024, $data['size']);
        $this->assertEquals('image/jpeg', $data['mime_type']);

        // Clean up temp file
        @unlink($tempFile);
    }

    public function testUploadRollsBackStorageWhenDatabaseInsertFails(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tempFile, 'fake file contents');

        $mockFile = $this->createMockUploadedFile([
            'tempName' => $tempFile,
            'name' => 'doc.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        $this->mockStorage
            ->method('put')
            ->willReturn(true);

        $this->mockStorage
            ->method('getDriverName')
            ->willReturn('local');

        $this->mockStorage
            ->method('url')
            ->willReturn('http://localhost/uploads/doc.pdf');

        // Storage delete should be called for rollback
        $this->mockStorage
            ->expects($this->once())
            ->method('delete');

        // Insert fails
        $this->mockFileRepository
            ->method('insert')
            ->willReturn(false);

        $this->mockFileRepository
            ->method('errors')
            ->willReturn(['file' => 'Database error']);

        $this->expectException(ValidationException::class);

        $this->service->upload(new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => $mockFile,
            'user_id' => 1,
        ], service('validation')));

        @unlink($tempFile);
    }

    public function testUploadFailsWhenStoragePutFails(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tempFile, 'fake file contents');

        $mockFile = $this->createMockUploadedFile([
            'tempName' => $tempFile,
            'name' => 'image.png',
            'extension' => 'png',
            'mime_type' => 'image/png',
            'size' => 512,
        ]);

        $this->mockStorage
            ->method('put')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $this->service->upload(new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => $mockFile,
            'user_id' => 1,
        ], service('validation')));

        @unlink($tempFile);
    }

    // ==================== INDEX TESTS ====================

    public function testIndexWithoutUserIdThrowsException(): void
    {
        $this->expectException(\dcardenasl\Ci4ApiCore\Exceptions\AuthenticationException::class);
        new \App\DTO\Request\Files\FileIndexRequestDTO([], service('validation'));
    }

    public function testIndexReturnsUserFiles(): void
    {
        $files = [
            $this->createFileEntity([
                'id' => 1,
                'original_name' => 'photo.jpg',
                'size' => 1024,
                'mime_type' => 'image/jpeg',
            ]),
            $this->createFileEntity([
                'id' => 2,
                'original_name' => 'doc.pdf',
                'size' => 2048,
                'mime_type' => 'application/pdf',
            ]),
        ];

        $this->mockFileRepository
            ->method('paginateCriteria')
            ->willReturn([
                'data' => $files,
                'total' => 2,
                'page' => 1,
                'per_page' => 20
            ]);

        $request = new \App\DTO\Request\Files\FileIndexRequestDTO(['user_id' => 1], service('validation'));
        $result = $this->service->index($request);
        $payload = $result->toArray();

        $this->assertInstanceOf(\dcardenasl\Ci4ApiCore\Dto\PaginatedResponseDTO::class, $result);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('total', $payload);
        $this->assertCount(2, $payload['data']);
    }

    // ==================== DOWNLOAD TESTS ====================

    public function testDownloadWithoutIdThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        new \App\DTO\Request\Files\FileGetRequestDTO(['user_id' => 1], service('validation'));
    }

    public function testDownloadWithoutUserIdThrowsException(): void
    {
        $this->expectException(\dcardenasl\Ci4ApiCore\Exceptions\AuthenticationException::class);
        new \App\DTO\Request\Files\FileGetRequestDTO(['id' => 1], service('validation'));
    }

    public function testDownloadNonExistentFileThrowsNotFoundException(): void
    {
        $this->mockFileRepository
            ->method('find')
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $request = new \App\DTO\Request\Files\FileGetRequestDTO(['id' => 999, 'user_id' => 1], service('validation'));
        $this->service->download($request);
    }

    public function testDownloadOtherUsersFileThrowsAuthorizationException(): void
    {
        $file = $this->createFileEntity([
            'id' => 1,
            'user_id' => 99, // Different user
        ]);

        $this->mockFileRepository
            ->method('find')
            ->willReturn($file);

        $this->expectException(AuthorizationException::class);

        $request = new \App\DTO\Request\Files\FileGetRequestDTO(['id' => 1, 'user_id' => 1], service('validation'));
        $this->service->download($request);
    }

    public function testDownloadOwnFileReturnsSuccess(): void
    {
        $file = $this->createFileEntity([
            'id' => 1,
            'user_id' => 1,
            'original_name' => 'myfile.pdf',
            'url' => 'http://example.com/myfile.pdf',
            'path' => '2024/01/01/myfile.pdf',
            'storage_driver' => 'local',
        ]);

        $this->mockFileRepository
            ->method('find')
            ->willReturn($file);

        $request = new \App\DTO\Request\Files\FileGetRequestDTO(['id' => 1, 'user_id' => 1], service('validation'));
        $result = $this->service->download($request);
        $payload = $result->toArray();

        $this->assertInstanceOf(\App\DTO\Response\Files\FileDownloadResponseDTO::class, $result);
        $this->assertEquals('myfile.pdf', $payload['original_name']);
        $this->assertEquals('http://example.com/myfile.pdf', $payload['url']);
    }

    // ==================== DESTROY TESTS ====================

    public function testDestroyNonExistentFileThrowsNotFoundException(): void
    {
        $this->mockFileRepository
            ->method('find')
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->destroy(999, new \dcardenasl\Ci4ApiCore\Dto\SecurityContext(1));
    }

    public function testDestroyOtherUsersFileThrowsAuthorizationException(): void
    {
        $file = $this->createFileEntity([
            'id' => 1,
            'user_id' => 99,
        ]);

        $this->mockFileRepository
            ->method('find')
            ->willReturn($file);

        $this->expectException(AuthorizationException::class);

        $this->service->destroy(1, new \dcardenasl\Ci4ApiCore\Dto\SecurityContext(1));
    }

    public function testDestroyOwnFileSoftDeletesAndPreservesStorage(): void
    {
        // Post-API-015: destroy() is soft-delete. It must record the actor in
        // `deleted_by_user_id` and call the repo's `delete()` (which the model
        // turns into a soft-delete because `$useSoftDeletes=true`). Crucially
        // it must NOT touch storage — bytes are preserved for restore().
        $file = $this->createFileEntity([
            'id' => 1,
            'user_id' => 1,
            'path' => '2024/01/01/file.jpg',
        ]);

        $this->mockFileRepository
            ->method('find')
            ->willReturn($file);

        $this->mockStorage
            ->expects($this->never())
            ->method('delete');

        $this->mockFileRepository
            ->expects($this->once())
            ->method('update')
            ->with(1, ['deleted_by_user_id' => 1])
            ->willReturn(true);

        $this->mockFileRepository
            ->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $result = $this->service->destroy(1, new \dcardenasl\Ci4ApiCore\Dto\SecurityContext(1));

        $this->assertTrue($result);
    }

    public function testForceDestroyPurgesTrashedFile(): void
    {
        // Pre-condition: file must be in the trash (`deleted_at` set). Service
        // calls `findIncludingTrashed()` to bypass soft-delete filter, then
        // removes both storage bytes and the DB row via `purge()`.
        $file = $this->createFileEntity([
            'id' => 1,
            'user_id' => 1,
            'path' => '2024/01/01/file.jpg',
            'deleted_at' => '2026-05-17 12:00:00',
        ]);

        $this->mockFileRepository
            ->method('findIncludingTrashed')
            ->willReturn($file);

        $this->mockStorage
            ->expects($this->once())
            ->method('delete')
            ->with('2024/01/01/file.jpg')
            ->willReturn(true);

        $this->mockFileRepository
            ->expects($this->once())
            ->method('purge')
            ->with(1)
            ->willReturn(true);

        $result = $this->service->forceDestroy(1, new \dcardenasl\Ci4ApiCore\Dto\SecurityContext(1));

        $this->assertTrue($result);
    }

    public function testForceDestroyRefusesNonTrashedFile(): void
    {
        $file = $this->createFileEntity([
            'id' => 1,
            'user_id' => 1,
            'path' => '2024/01/01/file.jpg',
            'deleted_at' => null,
        ]);

        $this->mockFileRepository
            ->method('findIncludingTrashed')
            ->willReturn($file);

        $this->mockStorage->expects($this->never())->method('delete');
        $this->mockFileRepository->expects($this->never())->method('purge');

        $this->expectException(BadRequestException::class);

        $this->service->forceDestroy(1, new \dcardenasl\Ci4ApiCore\Dto\SecurityContext(1));
    }

    public function testRestoreClearsTrashedRow(): void
    {
        $file = $this->createFileEntity([
            'id' => 1,
            'user_id' => 1,
            'deleted_at' => '2026-05-17 12:00:00',
            'deleted_by_user_id' => 1,
        ]);

        $this->mockFileRepository
            ->method('findIncludingTrashed')
            ->willReturn($file);

        $this->mockFileRepository
            ->expects($this->once())
            ->method('restore')
            ->with(1)
            ->willReturn(true);

        $result = $this->service->restore(1, new \dcardenasl\Ci4ApiCore\Dto\SecurityContext(1));

        $this->assertTrue($result);
    }

    public function testRestoreRefusesNonTrashedFile(): void
    {
        $file = $this->createFileEntity([
            'id' => 1,
            'user_id' => 1,
            'deleted_at' => null,
        ]);

        $this->mockFileRepository
            ->method('findIncludingTrashed')
            ->willReturn($file);

        $this->mockFileRepository->expects($this->never())->method('restore');

        $this->expectException(BadRequestException::class);

        $this->service->restore(1, new \dcardenasl\Ci4ApiCore\Dto\SecurityContext(1));
    }

    public function testUploadWithDuplicateFilenameGeneratesNumericSeries(): void
    {
        $filename = 'logo.png';
        $datePath = date('Y/m/d');

        // Mock Storage to simulate existing file for 'logo.png' but NOT for 'logo_1.png'
        $this->mockStorage
            ->method('exists')
            ->willReturnMap([
                ["{$datePath}/logo.png", true],   // First check: exists
                ["{$datePath}/logo_1.png", false] // Second check: doesn't exist
            ]);

        $this->mockStorage
            ->expects($this->once())
            ->method('put')
            ->with($this->equalTo("{$datePath}/logo_1.png"), $this->anything())
            ->willReturn(true);

        $this->mockStorage
            ->method('getDriverName')
            ->willReturn('local');

        $this->mockStorage
            ->method('url')
            ->willReturn("http://localhost/uploads/{$datePath}/logo_1.png");

        $savedEntity = $this->createFileEntity([
            'id' => 1,
            'original_name' => $filename,
            'stored_name' => 'logo_1.png',
            'mime_type' => 'image/png',
            'url' => "http://localhost/uploads/{$datePath}/logo_1.png",
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);

        $this->mockFileRepository->method('insert')->willReturn(1);
        $this->mockFileRepository->method('find')->willReturn($savedEntity);

        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tempFile, 'fake contents');

        $result = $this->service->upload(new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => $this->createMockUploadedFile([
                'tempName' => $tempFile,
                'name' => $filename,
                'extension' => 'png',
                'mime_type' => 'image/png',
            ]),
            'user_id' => 1,
        ], service('validation')));

        $this->assertInstanceOf(\App\DTO\Response\Files\FileResponseDTO::class, $result);
        $this->assertEquals($filename, $result->toArray()['original_name']);
        @unlink($tempFile);
    }

    public function testUploadWithUploadPrefixCleansFilename(): void
    {
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        // Simulating the dirty name reported by the user
        $dirtyFilename = 'upload_699e505bebdf92.24327053_Captura09.PNG';
        $datePath = date('Y/m/d');

        $this->mockStorage
            ->method('exists')
            ->willReturn(false); // No collision

        $this->mockStorage
            ->expects($this->once())
            ->method('put')
            ->willReturn(true);

        $savedEntity = $this->createFileEntity([
            'id' => 1,
            'original_name' => 'Captura09.PNG',
            'stored_name' => 'Captura09.png',
            'mime_type' => 'image/png',
            'url' => "http://localhost/uploads/{$datePath}/Captura09.png",
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);

        $this->mockFileRepository->method('insert')->willReturn(1);
        $this->mockFileRepository->method('find')->willReturn($savedEntity);

        $result = $this->service->upload(new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => $base64,
            'filename' => $dirtyFilename,
            'user_id' => 1,
        ], service('validation')));

        $this->assertInstanceOf(\App\DTO\Response\Files\FileResponseDTO::class, $result);
    }

    public function testUploadWithHexHashPrefixCleansFilename(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tempFile, 'fake file contents');

        // Simulating the dirty name with hex hash reported by the user
        $dirtyFilename = '8605b9b8f03a7a1f_Captura02.PNG';
        $datePath = date('Y/m/d');

        $mockFile = $this->createMockUploadedFile([
            'tempName' => $tempFile,
            'name' => $dirtyFilename,
            'extension' => 'png',
            'mime_type' => 'image/png',
            'size' => 512,
        ]);

        $this->mockStorage
            ->method('exists')
            ->willReturn(false);

        $this->mockStorage
            ->expects($this->once())
            ->method('put')
            ->willReturn(true);

        $savedEntity = $this->createFileEntity([
            'id' => 1,
            'original_name' => 'Captura02.PNG',
            'stored_name' => 'Captura02.png',
            'mime_type' => 'image/png',
            'url' => "http://localhost/uploads/{$datePath}/Captura02.png",
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);

        $this->mockFileRepository->method('insert')->willReturn(1);
        $this->mockFileRepository->method('find')->willReturn($savedEntity);

        $result = $this->service->upload(new \App\DTO\Request\Files\FileUploadRequestDTO([
            'file' => $mockFile,
            'user_id' => 1,
        ], service('validation')));

        $this->assertInstanceOf(\App\DTO\Response\Files\FileResponseDTO::class, $result);
        @unlink($tempFile);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Create a mock UploadedFile object
     */
    private function createMockUploadedFile(array $overrides = []): UploadedFile
    {
        $defaults = [
            'isValid' => true,
            'size' => 1024,
            'extension' => 'jpg',
            'name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'tempName' => '/tmp/php123456',
            'errorString' => '',
        ];

        $config = array_merge($defaults, $overrides);

        $mock = $this->createMock(UploadedFile::class);
        $mock->method('isValid')->willReturn($config['isValid']);
        $mock->method('getSize')->willReturn($config['size']);
        $mock->method('getExtension')->willReturn($config['extension']);
        $mock->method('getName')->willReturn($config['name']);
        $mock->method('getMimeType')->willReturn($config['mime_type']);
        $mock->method('getTempName')->willReturn($config['tempName']);
        $mock->method('getErrorString')->willReturn($config['errorString']);

        return $mock;
    }

    /**
     * Create a FileEntity with mock data
     */
    private function createFileEntity(array $data): FileEntity
    {
        $entity = new FileEntity();
        foreach ($data as $key => $value) {
            $entity->{$key} = $value;
        }
        return $entity;
    }
}
