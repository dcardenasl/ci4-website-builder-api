<?php

declare(strict_types=1);

namespace App\Libraries\Files;

use App\Libraries\Storage\StorageManager;

/**
 * Filename Generator
 *
 * Handles sanitization and collision prevention for stored files.
 */
class FilenameGenerator
{
    public function __construct(
        protected StorageManager $storage
    ) {
    }

    /**
     * Generate a unique filename by checking existence in storage.
     */
    public function generate(string $originalName, string $extension, string $datePath = ''): string
    {
        $basename = $this->sanitizeBasename($originalName);
        $extension = strtolower($extension);

        $filename = "{$basename}.{$extension}";
        $fullPath = $datePath ? "{$datePath}/{$filename}" : $filename;

        // If file doesn't exist, use original name
        if (!$this->storage->exists($fullPath)) {
            return $filename;
        }

        // Handle collision with numeric series (e.g., photo_1.jpg, photo_2.jpg)
        $counter = 1;
        while ($counter <= 20) {
            $filename = "{$basename}_{$counter}.{$extension}";
            $fullPath = $datePath ? "{$datePath}/{$filename}" : $filename;

            if (!$this->storage->exists($fullPath)) {
                return $filename;
            }
            $counter++;
        }

        // Fallback for massive collisions: append a unique ID
        return "{$basename}_" . uniqid() . ".{$extension}";
    }

    /**
     * Sanitize the basename of a file.
     */
    private function sanitizeBasename(string $originalName): string
    {
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // 1. Remove common temp prefixes like 'upload_65da..._'
        if (preg_match('/^upload_[a-f0-9.]+_+(.+)$/i', $basename, $matches)) {
            $basename = $matches[1];
        } elseif (preg_match('/^upload_[a-f0-9]+_+(.+)$/i', $basename, $matches)) {
            $basename = $matches[1];
        }

        // 2. Remove aggressive hexadecimal hash prefixes (16, 32 or 40 chars)
        if (preg_match('/^[a-f0-9]{16,40}_+(.+)$/i', $basename, $matches)) {
            $basename = $matches[1];
        }

        // Clean filename: only alphanumeric, underscore, and dash. preg_replace
        // returns null on PCRE error — fall back to 'file' in that case.
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename) ?? '';
        $basename = substr($basename, 0, 80); // Limit length

        return empty($basename) ? 'file' : $basename;
    }
}
