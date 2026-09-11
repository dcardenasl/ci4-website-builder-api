<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Presentation mode requested by a role.
 *
 * This value never grants permissions. It only tells a consuming panel which
 * shell to render; authorization continues to come from the effective scope.
 */
enum UiMode: string
{
    case Full = 'full';
    case Simple = 'simple';

    public static function fromMixed(mixed $value): self
    {
        return self::tryFrom(is_string($value) ? strtolower(trim($value)) : '') ?? self::Full;
    }

    /** @param list<mixed> $modes */
    public static function effective(array $modes): self
    {
        if ($modes === []) {
            return self::Full;
        }

        foreach ($modes as $mode) {
            if (self::fromMixed($mode) !== self::Simple) {
                return self::Full;
            }
        }

        return self::Simple;
    }
}
