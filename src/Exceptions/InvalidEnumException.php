<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Exceptions;

use Exception;

final class InvalidEnumException extends Exception
{
    public static function value(string $enumClass, mixed $value): self
    {
        $type = get_debug_type($value);

        return new self("Value [{$type}] is not a valid case of [{$enumClass}].");
    }

    /**
     * @param  list<string>  $matchedLabels
     */
    public static function ambiguousLabel(string $enumClass, string $label, array $matchedLabels): self
    {
        $labels = implode(', ', $matchedLabels);

        return new self(
            "Label [{$label}] ambiguously matches multiple cases in [{$enumClass}]: {$labels}. Provide an exact match."
        );
    }
}
