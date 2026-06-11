<?php

declare(strict_types=1);

namespace App\Libraries\Files;

use App\Support\Files\ProcessedFile;
use CodeIgniter\HTTP\Files\UploadedFile;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

class MultipartProcessor implements FileProcessorInterface
{
    public function process(mixed $input, array $options = []): ProcessedFile
    {
        if (!$input instanceof UploadedFile) {
            throw new BadRequestException(lang('Files.invalid_file_object'));
        }

        if (!$input->isValid()) {
            throw new BadRequestException(lang('Files.upload_failed', [$input->getErrorString()]));
        }

        $this->validate($input, $options);

        $stream = fopen($input->getTempName(), 'rb');
        if ($stream === false) {
            throw new \RuntimeException(lang('Files.storage_error'));
        }

        return new ProcessedFile(
            originalName: $input->getName(),
            mimeType: $input->getMimeType(),
            size: (int) $input->getSize(),
            extension: $input->getExtension(),
            contents: $stream
        );
    }

    private function validate(UploadedFile $file, array $options): void
    {
        $apiConfig = config('Api');
        $maxSize = $options['maxSize'] ?? $apiConfig->fileMaxSize;
        if ($file->getSize() > $maxSize) {
            throw new ValidationException(lang('Files.file_too_large'), ['file' => lang('Files.file_too_large')]);
        }

        $allowedTypes = $options['allowedTypes'] ?? explode(',', $apiConfig->fileAllowedTypes);
        if (!in_array(strtolower($file->getExtension()), $allowedTypes, true)) {
            throw new ValidationException(lang('Files.invalid_file_type'), ['file' => lang('Files.invalid_file_type')]);
        }
    }
}
