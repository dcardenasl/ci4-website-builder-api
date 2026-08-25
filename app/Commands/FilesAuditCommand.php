<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Read-only diagnostic command for auditing local files against `files` rows.
 * It reports discrepancies and never deletes, updates, or repairs anything.
 */
class FilesAuditCommand extends BaseCommand
{
    protected $group = 'Files';
    protected $name = 'files:audit';
    protected $description = 'Audit local files against database records in the files table';
    protected $usage = 'php spark files:audit';

    public function run(array $params): void
    {
        CLI::write('Filesystem & Database Storage Audit (Read-Only)', 'cyan');
        CLI::write(str_repeat('=', 60), 'cyan');

        $apiConfig = config('Api');
        $uploadPath = (string) ($apiConfig->fileUploadPath ?? 'writable/uploads');
        $projectRoot = dirname(FCPATH);
        $fullUploadPath = rtrim($projectRoot, '/') . '/' . ltrim(rtrim($uploadPath, '/'), '/');

        CLI::write("Upload Path: {$fullUploadPath}", 'info');

        if (! is_dir($fullUploadPath)) {
            CLI::write('Upload directory does not exist.', 'red');
            return;
        }

        $db = Database::connect();
        $queryResult = $db->table('files')
            ->select('id, path, original_name, variants')
            ->get();
        /** @var list<array{id: int, path: string, original_name: string, variants: string|null}> $dbFiles */
        $dbFiles = $queryResult !== false ? $queryResult->getResultArray() : [];

        /** @var array<string, int> $dbPaths */
        $dbPaths = [];
        foreach ($dbFiles as $row) {
            $dbPaths[(string) $row['path']] = (int) $row['id'];

            $variants = is_string($row['variants']) && trim($row['variants']) !== ''
                ? json_decode($row['variants'], true)
                : [];
            if (! is_array($variants)) {
                continue;
            }

            foreach ($variants as $variant) {
                if (is_array($variant) && isset($variant['path']) && is_string($variant['path'])) {
                    $dbPaths[$variant['path']] = (int) $row['id'];
                }
            }
        }

        CLI::write(sprintf('Total database records in `files`: %d', count($dbFiles)), 'yellow');

        /** @var array<string, int> $diskFiles */
        $diskFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullUploadPath, \FilesystemIterator::SKIP_DOTS)
        );

        $totalDiskSize = 0;
        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if (in_array($file->getFilename(), ['.gitkeep', 'index.html'], true)) {
                continue;
            }

            $relativePath = ltrim(
                str_replace('\\', '/', str_replace($fullUploadPath, '', $file->getPathname())),
                '/'
            );
            $size = (int) $file->getSize();
            $totalDiskSize += $size;
            $diskFiles[$relativePath] = $size;
        }

        CLI::write(
            sprintf('Total physical files on disk: %d (%.2f MB)', count($diskFiles), $totalDiskSize / (1024 * 1024)),
            'yellow'
        );
        CLI::write(str_repeat('-', 60), 'cyan');

        $untrackedOnDisk = [];
        foreach ($diskFiles as $path => $size) {
            if (! isset($dbPaths[$path])) {
                $untrackedOnDisk[$path] = $size;
            }
        }

        $missingOnDisk = [];
        foreach ($dbFiles as $row) {
            $path = (string) $row['path'];
            if (! isset($diskFiles[$path])) {
                $missingOnDisk[] = $row;
            }
        }

        CLI::write("\n1. Physical files on disk with NO database record:", 'yellow');
        if ($untrackedOnDisk === []) {
            CLI::write('   ✓ None (all disk files are tracked in database)', 'green');
        } else {
            CLI::write(sprintf('   ✗ Found %d untracked files on disk:', count($untrackedOnDisk)), 'red');
            $count = 0;
            foreach ($untrackedOnDisk as $path => $size) {
                if ($count++ < 10) {
                    CLI::write(sprintf('     - %s (%.2f KB)', $path, $size / 1024), 'white');
                }
            }
            if (count($untrackedOnDisk) > 10) {
                CLI::write(sprintf('     ... and %d more', count($untrackedOnDisk) - 10), 'gray');
            }
        }

        CLI::write("\n2. Database records with MISSING physical disk files:", 'yellow');
        if ($missingOnDisk === []) {
            CLI::write('   ✓ None (all database records exist on disk)', 'green');
        } else {
            CLI::write(sprintf('   ✗ Found %d missing files:', count($missingOnDisk)), 'red');
            $count = 0;
            foreach ($missingOnDisk as $row) {
                if ($count++ < 10) {
                    CLI::write(
                        sprintf('     - ID #%d: %s (%s)', $row['id'], $row['path'], $row['original_name']),
                        'white'
                    );
                }
            }
            if (count($missingOnDisk) > 10) {
                CLI::write(sprintf('     ... and %d more', count($missingOnDisk) - 10), 'gray');
            }
        }

        CLI::write("\n" . str_repeat('=', 60), 'cyan');
        CLI::write('Audit Summary Complete.', 'green');
    }
}
