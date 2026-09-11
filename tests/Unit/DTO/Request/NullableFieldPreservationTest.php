<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request;

use App\DTO\Request\ApiKeys\ApiKeyCreateRequestDTO;
use App\DTO\Request\ApiKeys\ApiKeyUpdateRequestDTO;
use App\DTO\Request\Auth\UpdateMeRequestDTO;
use App\DTO\Request\Common\GalleryAttachRequestDTO;
use App\DTO\Request\Files\UpdateFileMetadataRequestDTO;
use App\DTO\Request\Iam\PermissionUpdateRequestDTO;
use App\DTO\Request\Iam\RoleUpdateRequestDTO;
use App\DTO\Request\Users\UserUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class NullableFieldPreservationTest extends CIUnitTestCase
{
    /**
     * @return iterable<string, array{class-string<BaseRequestDTO>, array<string, mixed>, string}>
     */
    public static function explicitNullCases(): iterable
    {
        yield 'api key create rate limit' => [ApiKeyCreateRequestDTO::class, [
            'name' => 'integration',
            'rate_limit_requests' => null,
        ], 'rate_limit_requests'];
        yield 'api key update name' => [ApiKeyUpdateRequestDTO::class, ['name' => null], 'name'];
        yield 'profile first name' => [UpdateMeRequestDTO::class, ['first_name' => null], 'first_name'];
        yield 'gallery sort order' => [GalleryAttachRequestDTO::class, [
            'file_id' => 'file-1',
            'sort_order' => null,
        ], 'sort_order'];
        yield 'file metadata alt text' => [UpdateFileMetadataRequestDTO::class, ['alt_text' => null], 'alt_text'];
        yield 'permission description' => [PermissionUpdateRequestDTO::class, ['description' => null], 'description'];
        yield 'role description' => [RoleUpdateRequestDTO::class, ['description' => null], 'description'];
        yield 'user first name' => [UserUpdateRequestDTO::class, ['first_name' => null], 'first_name'];
    }

    /** @param class-string<BaseRequestDTO> $class */
    #[DataProvider('explicitNullCases')]
    public function testExplicitNullIsRetainedInPayload(string $class, array $payload, string $field): void
    {
        $dto = new $class($payload, service('validation'));
        $data = $dto->toArray();

        $this->assertArrayHasKey($field, $data);
        $this->assertNull($data[$field]);
    }

    public function testAbsentNullableFieldsRemainOmitted(): void
    {
        $this->assertSame([], (new ApiKeyUpdateRequestDTO([], service('validation')))->toArray());
        $this->assertSame([], (new UpdateMeRequestDTO([], service('validation')))->toArray());
    }
}
