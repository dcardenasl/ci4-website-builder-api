<?php

declare(strict_types=1);

namespace App\DTO\Request\Identity;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

/**
 * Reset Password Request DTO
 *
 * Validates the token, email, and new password.
 */
readonly class ResetPasswordRequestDTO extends BaseRequestDTO
{
    public string $email;
    public string $token;
    public string $password;

    public function rules(): array
    {
        return [
            'email'    => 'required|valid_email',
            'token'    => 'required|string',
            'password' => 'required|strong_password',
        ];
    }

    protected function map(array $data): void
    {
        $this->email = strtolower(trim((string) ($data['email'] ?? '')));
        $this->token = (string) ($data['token'] ?? '');
        $this->password = (string) ($data['password'] ?? '');
    }

    public function toArray(): array
    {
        return [
            'email'    => $this->email,
            'token'    => $this->token,
            'password' => $this->password,
        ];
    }
}
