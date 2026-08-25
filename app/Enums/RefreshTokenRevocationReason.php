<?php

declare(strict_types=1);

namespace App\Enums;

enum RefreshTokenRevocationReason: string
{
    case Rotated = 'rotated';
    case Logout = 'logout';
    case RevokeAll = 'revoke_all';
    case ReuseDetected = 'reuse_detected';
}
