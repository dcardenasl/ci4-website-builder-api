<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Files;

use App\DTO\Request\Files\FileUploadRequestDTO;
use App\Entities\FileEntity;
use App\Services\Files\FilePolicyService;
use App\Support\Files\FileAction;
use CodeIgniter\Test\CIUnitTestCase;
use Config\FilePolicy;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;

final class FilePolicyServiceTest extends CIUnitTestCase
{
    public function testResolveUploadVisibilityFallsBackToDefaultWhenPublicIsDisabled(): void
    {
        $policy = new FilePolicy();
        $policy->defaultVisibility = 'private';
        $policy->allowPublicVisibility = false;
        $policy->allowedVisibilities = ['private', 'public'];

        $service = new FilePolicyService($policy);
        $tempFile = tempnam(sys_get_temp_dir(), 'file-policy-');
        file_put_contents($tempFile, 'demo');
        try {
            $request = new FileUploadRequestDTO([
                'user_id'    => 1,
                'file'       => [
                    'tmp_name' => $tempFile,
                    'name'     => 'demo.txt',
                    'type'     => 'text/plain',
                    'size'     => 4,
                    'error'    => 0,
                ],
                'visibility' => 'public',
            ]);

            $this->assertSame('private', $service->resolveUploadVisibility($request, null));
        } finally {
            @unlink($tempFile);
        }
    }

    public function testCanListAllFilesRequiresReadPermissionEvenWhenUnscoped(): void
    {
        $policy = new FilePolicy();
        $policy->userScopedFiles = false;

        $service = new FilePolicyService($policy);
        $reader = new SecurityContext(7, [], ['files.read']);

        $this->assertFalse($service->canListAllFiles(null));
        $this->assertTrue($service->canListAllFiles($reader));
        $this->assertFalse($service->shouldScopeListingsToOwner($reader));
    }

    public function testReadAccessRequiresPermissionAndHonorsUnscopedPolicy(): void
    {
        $policy = new FilePolicy();
        $policy->userScopedFiles = false;
        $service = new FilePolicyService($policy);

        $file = new FileEntity([
            'id' => 10,
            'user_id' => 22,
        ]);
        $reader = new SecurityContext(7, [], ['files.read']);

        $this->assertFalse($service->canAccessFile($file, 7, FileAction::VIEW, null));
        $this->assertTrue($service->canAccessFile($file, 7, FileAction::VIEW, $reader));
        $this->assertTrue($service->canAccessFile($file, 7, FileAction::DOWNLOAD, $reader));
        $this->assertFalse($service->canAccessFile($file, 7, FileAction::DELETE, $reader));
    }

    public function testOwnerMutationsRequireWriteAndCrossOwnerMutationsRequireAdmin(): void
    {
        $service = new FilePolicyService(new FilePolicy());
        $file = new FileEntity(['id' => 10, 'user_id' => 22]);
        $reader = new SecurityContext(22, [], ['files.read']);
        $writer = new SecurityContext(22, [], ['files.read', 'files.write']);
        $admin = new SecurityContext(7, [], ['files.read', 'files.admin']);

        $this->assertFalse($service->canAccessFile($file, 22, FileAction::DELETE, $reader));
        $this->assertTrue($service->canAccessFile($file, 22, FileAction::DELETE, $writer));
        $this->assertFalse($service->canAccessFile($file, 7, FileAction::DELETE, $writer));
        $this->assertTrue($service->canAccessFile($file, 7, FileAction::DELETE, $admin));
        $this->assertTrue($service->canAccessFile($file, 22, FileAction::FORCE_DELETE, $writer));
    }
}
