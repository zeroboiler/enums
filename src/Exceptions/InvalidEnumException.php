<?php

declare(strict_types=1);

namespace NovaForge\Enums\Exceptions;

use Exception;

final class InvalidEnumException extends Exception
{
    public static function value(string $enumClass, mixed $value): self
    {
        $type = get_debug_type($value);

        return new self("Value [{$type}] is not a valid case of [{$enumClass}].");
    }
}
