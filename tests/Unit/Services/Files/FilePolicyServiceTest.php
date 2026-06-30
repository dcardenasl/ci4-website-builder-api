<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Files;

use App\Entities\FileEntity;
use App\Services\Files\FilePolicyService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\FilePolicy;

final class FilePolicyServiceTest extends CIUnitTestCase
{
    public function testResolveUploadVisibilityFallsBackToDefaultWhenPublicIsDisabled(): void
    {
        $policy = new FilePolicy();
        $policy->defaultVisibility = 'private';
        $policy->allowPublicVisibility = false;
        $policy->allowedVisibilities = ['private', 'public'];

        $service = new FilePolicyService($policy);
        $this->assertSame('private', $service->resolveUploadVisibility(['visibility' => 'public'], null));
    }

    public function testCanListAllFilesRespectsGlobalUnscopedMode(): void
    {
        $policy = new FilePolicy();
        $policy->userScopedFiles = false;

        $service = new FilePolicyService($policy);
        $this->assertTrue($service->canListAllFiles(null));
        $this->assertFalse($service->shouldScopeListingsToOwner(null));
    }

    public function testCanAccessFileAllowsAnyReaderWhenUnscoped(): void
    {
        $policy = new FilePolicy();
        $policy->userScopedFiles = false;
        $service = new FilePolicyService($policy);

        $file = new FileEntity([
            'id' => 10,
            'user_id' => 22,
        ]);

        $this->assertTrue($service->canAccessFile($file, 7, 'view', null));
        $this->assertTrue($service->canAccessFile($file, 7, 'download', null));
        $this->assertFalse($service->canAccessFile($file, 7, 'delete', null));
    }
}
