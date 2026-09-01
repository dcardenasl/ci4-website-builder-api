<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class DockerEntrypointConventionsTest extends CIUnitTestCase
{
    public function testDatabaseBootstrapCommandsFailFast(): void
    {
        $path = ROOTPATH . 'docker/entrypoint.sh';
        $source = file_get_contents($path);

        $this->assertIsString($source, "Unable to read {$path}.");
        $this->assertStringContainsString("php spark migrate --all\n", $source);
        $this->assertStringContainsString("php spark db:seed RbacBootstrapSeeder\n", $source);
        $this->assertDoesNotMatchRegularExpression('/php spark migrate --all\s*\|\|\s*true/', $source);
        $this->assertDoesNotMatchRegularExpression('/php spark db:seed RbacBootstrapSeeder\s*\|\|\s*true/', $source);
    }
}
