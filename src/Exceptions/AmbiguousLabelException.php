<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Exceptions;

use Exception;

/**
 * Thrown when {@see tryFromLabel()} matches multiple enum cases case-insensitively.
 */
final class AmbiguousLabelException extends Exception
{
    public static function forLabel(string $enumClass, string $label): self
    {
        return new self(
            "Label [{$label}] ambiguously matches multiple cases of [{$enumClass}]. "
            .'Use strict mode or provide a case-sensitive label.'
        );
    }
}
