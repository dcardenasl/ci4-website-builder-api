<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Guardrail against services bypassing the Model layer with raw query-builder
 * or connection access. Services may depend on typed models; persistence
 * queries belong in those models, where they can be named and tested.
 */
class ServiceModelDependencyConventionsTest extends CIUnitTestCase
{
    public function testServicesDoNotBypassModelsWithRawBuilderAccess(): void
    {
        $root = rtrim((string) ROOTPATH, DIRECTORY_SEPARATOR);
        $serviceDir = $root . DIRECTORY_SEPARATOR . 'app/Services';

        $allowed = [];
        sort($allowed);

        $found = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($serviceDir));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $path = $file->getPathname();
            $source = file_get_contents($path);
            if (!is_string($source) || $source === '') {
                continue;
            }

            // Ignore comments and string literals so this guard only inspects
            // executable service code.
            $code = '';
            foreach (token_get_all($source) as $token) {
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING], true)) {
                    continue;
                }
                $code .= is_array($token) ? $token[1] : $token;
            }

            $bypassesModels = preg_match('/->\s*table\s*\(/', $code) === 1
                || preg_match('/\\\\?Database\s*::\s*connect\s*\(/', $code) === 1;

            if (! $bypassesModels) {
                continue;
            }

            $relative = str_replace('\\', '/', ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR));
            $found[] = $relative;
        }

        sort($found);
        $this->assertSame(
            $allowed,
            $found,
            "Services with raw query-builder/DB access changed.\n" .
            'Extend the relevant Model with a finder/mutator instead of using $db->table()/Database::connect() directly.'
        );
    }
}
