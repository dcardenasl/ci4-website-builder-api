<?php

declare(strict_types=1);

namespace App\DTO\Request\Identity;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

/**
 * Forgot Password Request DTO
 *
 * Validates the email address for password recovery.
 */
readonly class ForgotPasswordRequestDTO extends BaseRequestDTO
{
    public string $email;

    public function rules(): array
    {
        return [
            'email' => 'required|valid_email|max_length[255]',
        ];
    }

    protected function map(array $data): void
    {
        $this->email = strtolower(trim((string) ($data['email'] ?? '')));
    }

    public function toArray(): array
    {
        return ['email' => $this->email];
    }
}
