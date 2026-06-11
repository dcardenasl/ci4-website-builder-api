<?php

declare(strict_types=1);

namespace App\DTO\Response\Identity;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

/**
 * Verification Response DTO
 */
#[OA\Schema(
    schema: 'VerificationResponse',
    title: 'Verification Response',
    required: ['message', 'user_id', 'email', 'verified_at']
)]
readonly class VerificationResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Success message')]
        public string $message,
        #[OA\Property(property: 'user_id', description: 'Verified user id', example: 1)]
        public int $user_id,
        #[OA\Property(description: 'User email')]
        public string $email,
        #[OA\Property(property: 'verified_at', description: 'Verification timestamp')]
        public string $verified_at
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            message: (string) ($data['message'] ?? ''),
            user_id: (int) ($data['user_id'] ?? 0),
            email: (string) ($data['email'] ?? ''),
            verified_at: (string) ($data['verified_at'] ?? '')
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'user_id' => $this->user_id,
            'email' => $this->email,
            'verified_at' => $this->verified_at,
        ];
    }
}
