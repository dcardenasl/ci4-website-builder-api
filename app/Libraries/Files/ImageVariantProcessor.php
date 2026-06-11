<?php

declare(strict_types=1);

namespace App\Libraries\Files;

use App\Libraries\Storage\StorageManager;

class ImageVariantProcessor
{
    public const PROCESSABLE = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const VARIANTS = [
        'thumb' => ['width' => 150, 'height' => 150, 'mode' => 'crop'],
        'sm'    => ['width' => 400, 'height' => null, 'mode' => 'fit'],
        'md'    => ['width' => 800, 'height' => null, 'mode' => 'fit'],
    ];

    /**
     * Generate thumb/sm/md variants for an image already in storage.
     *
     * @return array{variants: array<string, array{path: string, url: string, width: int, height: int}>, dimensions: array{width: int|null, height: int|null}}
     */
    public function generate(string $originalPath, string $extension, StorageManager $storage): array
    {
        $originalDimensions = ['width' => null, 'height' => null];
        $variants           = [];
        $tmpOriginal        = null;

        try {
            $contents = $storage->get($originalPath);
            if ($contents === false) {
                log_message('error', "ImageVariantProcessor: could not read {$originalPath}");
                return ['variants' => [], 'dimensions' => $originalDimensions];
            }

            $extension   = strtolower($extension);
            $uid         = bin2hex(random_bytes(8));
            $tmpDir      = sys_get_temp_dir();
            $tmpOriginal = $tmpDir . DIRECTORY_SEPARATOR . 'ci4_img_' . $uid . '.' . $extension;

            if (file_put_contents($tmpOriginal, $contents) === false) {
                return ['variants' => [], 'dimensions' => $originalDimensions];
            }

            $origSize = @getimagesize($tmpOriginal);
            if ($origSize !== false) {
                $originalDimensions = ['width' => $origSize[0], 'height' => $origSize[1]];
            }

            $dir      = dirname($originalPath);
            $basename = pathinfo($originalPath, PATHINFO_FILENAME);
            $dir      = $dir !== '.' ? $dir . '/' : '';

            foreach (self::VARIANTS as $key => $spec) {
                $tmpOutput = $tmpDir . DIRECTORY_SEPARATOR . 'ci4_var_' . $uid . '_' . $key . '.' . $extension;

                try {
                    $imageLib = \Config\Services::image('gd', null, false);
                    $imageLib->withFile($tmpOriginal);

                    if ($spec['mode'] === 'crop') {
                        $imageLib->fit($spec['width'], (int) $spec['height'], 'center');
                    } else {
                        $imageLib->resize($spec['width'], $spec['width'], true, 'width');
                    }

                    $imageLib->save($tmpOutput);

                    $variantContents = file_get_contents($tmpOutput);
                    if ($variantContents !== false) {
                        $variantPath = $dir . $basename . '_' . $key . '.' . $extension;

                        if ($storage->put($variantPath, $variantContents)) {
                            $variantSize = @getimagesize($tmpOutput);
                            $variants[$key] = [
                                'path'   => $variantPath,
                                'url'    => $storage->url($variantPath),
                                'width'  => $variantSize !== false ? $variantSize[0] : $spec['width'],
                                'height' => $variantSize !== false ? $variantSize[1] : ($spec['height'] ?? $spec['width']),
                            ];
                        }
                    }
                } finally {
                    if (file_exists($tmpOutput)) {
                        @unlink($tmpOutput);
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'ImageVariantProcessor::generate failed: ' . $e->getMessage());
            return ['variants' => [], 'dimensions' => $originalDimensions];
        } finally {
            if ($tmpOriginal !== null && file_exists($tmpOriginal)) {
                @unlink($tmpOriginal);
            }
        }

        return ['variants' => $variants, 'dimensions' => $originalDimensions];
    }

    /**
     * @param array<string, array{path?: string}> $variants
     */
    public function deleteVariants(array $variants, StorageManager $storage): void
    {
        foreach ($variants as $variant) {
            if (is_array($variant) && isset($variant['path']) && $variant['path'] !== '') {
                $storage->delete($variant['path']);
            }
        }
    }
}
