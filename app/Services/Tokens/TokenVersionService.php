<?php

declare(strict_types=1);

namespace App\Services\Tokens;

use App\Entities\UserEntity;
use App\Interfaces\Tokens\TokenVersionServiceInterface;
use App\Models\UserModel;

readonly class TokenVersionService implements TokenVersionServiceInterface
{
    public function __construct(private UserModel $userModel)
    {
    }

    public function current(int $userId): int
    {
        $user = $this->userModel->select('auth_token_version')->find($userId);

        if (! $user instanceof UserEntity) {
            throw new \RuntimeException(lang('Tokens.accessTokenVersionUserNotFound'));
        }

        return max(0, (int) ($user->auth_token_version ?? 0));
    }

    public function increment(int $userId): int
    {
        if ($userId <= 0) {
            throw new \InvalidArgumentException(lang('Tokens.accessTokenVersionInvalidUserId'));
        }

        if (! $this->userModel->incrementAuthTokenVersion($userId)) {
            throw new \RuntimeException(lang('Tokens.accessTokenVersionIncrementFailed'));
        }

        return $this->current($userId);
    }

    public function matches(int $userId, int $tokenVersion): bool
    {
        return $this->current($userId) === $tokenVersion;
    }
}
