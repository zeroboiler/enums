<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid enum value or case name is used.
 *
 * Provides named constructors for common failure modes:
 * - {@see value()} — when a backed value doesn't match any case
 * - {@see forName()} — when a case name doesn't exist on the enum
 *
 * Used by {@see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::fromName()}
 * when a case name does not exist on the target enum.
 *
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata For the trait that throws this exception
 * @see \ZeroBoiler\Enums\Rules\EnumRule For the validation rule that uses value-based enums
 */
final class InvalidEnumException extends Exception
{
    /**
     * Create an exception for an invalid value lookup.
     *
     * Includes the actual value in the message for easier debugging,
     * rather than only the PHP type name.
     *
     * @param  class-string  $enumClass
     * @param  int|string|null  $value  The invalid backed value or case name
     *
     * @see \ZeroBoiler\Enums\Rules\EnumRule For the validation rule that uses value-based validation
     */
    public static function value(string $enumClass, int|string|null $value): self
    {
        $display = $value === null ? 'null' : (string) $value;

        return new self("Value [{$display}] is not a valid case of [{$enumClass}].");
    }

    /**
     * Get a human-readable string representation of the exception.
     *
     * Useful for logging and display contexts where catching and
     * re-throwing as a string is needed (e.g., custom error pages).
     */
    #[\Override]
    public function __toString(): string
    {
        return self::class.': '.$this->getMessage();
    }

    /**
     * Create an exception for an invalid case name lookup.
     *
     * @param  class-string  $enumClass
     * @param  string  $name  The invalid case name that was not found
     *
     * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::fromName() For the method that throws this on failure
     */
    public static function forName(string $enumClass, string $name): self
    {
        return new self("Case name [{$name}] does not exist on enum [{$enumClass}].");
    }
}
