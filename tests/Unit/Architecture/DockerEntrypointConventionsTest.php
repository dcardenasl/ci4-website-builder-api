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

    public function testDockerComposeSupportsIsolatedE2eResources(): void
    {
        $path = ROOTPATH . 'docker-compose.yml';
        $source = file_get_contents($path);

        $this->assertIsString($source, "Unable to read {$path}.");
        $this->assertStringContainsString('${API_APP_CONTAINER_NAME:-ci4-api-app}', $source);
        $this->assertStringContainsString('${API_DB_CONTAINER_NAME:-ci4-api-db}', $source);
        $this->assertStringContainsString('${API_NETWORK_NAME:-ci4-platform}', $source);
        $this->assertStringContainsString('${API_MYSQL_VOLUME_NAME:-ci4-mysql-data}', $source);
        $this->assertStringContainsString('${API_ENV_VOLUME_NAME:-ci4-api-env}', $source);
    }
}
