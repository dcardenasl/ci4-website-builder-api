<?php

declare(strict_types=1);

namespace App\DTO\Response\Identity;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

/**
 * Password Reset Response DTO
 */
#[OA\Schema(
    schema: 'PasswordResetResponse',
    title: 'Password Reset Response',
    description: 'Password reset acknowledgement',
    required: ['message', 'success']
)]
readonly class PasswordResetResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Result message', example: 'Password reset successful')]
        public string $message,
        #[OA\Property(description: 'Whether the reset was successful', example: true)]
        public bool $success = true
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            message: (string) ($data['message'] ?? lang('Auth.passwordResetSuccess'))
        );
    }

    public function toArray(): array
    {
        return [
            'status' => 'success',
            'message' => $this->message,
        ];
    }
}
