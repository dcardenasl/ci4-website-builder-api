<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Files;

use App\Services\Files\NullVirusScannerService;
use CodeIgniter\Test\CIUnitTestCase;
use Psr\Log\LoggerInterface;

final class NullVirusScannerServiceTest extends CIUnitTestCase
{
    public function testDisabledScannerAllowsFileAndLogsDebug(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug');

        $service = new NullVirusScannerService($logger);

        $this->assertTrue($service->isSafe('/tmp/example.txt'));
    }

    public function testEnabledScannerFailsClosedWhenNoIntegrationExists(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $service = new NullVirusScannerService($logger, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(lang('Files.virus_scan_unavailable'));
        $service->isSafe('/tmp/example.txt');
    }
}
