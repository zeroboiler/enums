<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Universal enum cast — works with any backed enum.
 *
 * Auto-detected by ZeroBoiler enums. No need to manually register:
 *
 *   protected $casts = [
 *       'status' => UserStatus::class,  // works automatically
 *   ];
 *
 * @template T of \BackedEnum
 *
 * @implements CastsAttributes<int|string, int|string|null>
 */
final class EnumCast implements CastsAttributes
{
    /**
     * @param  class-string<T>  $enumClass
     */
    public function __construct(
        private readonly string $enumClass,
    ) {}

    /**
     * Cast raw value to enum instance.
     *
     * Returns `null` silently for values that don't match any enum case
     * (via `tryFrom()`). This matches Eloquent's convention for cast
     * attributes. If you need strict validation on stored values, use
     * model-level rules or accessors.
     *
     * @param  object  $model  The Eloquent model instance
     * @param  string  $key  The attribute name being cast
     * @param  int|string|null  $value  The raw value from the database
     * @param  array<string, mixed>  $attributes  All model attributes
     * @return T|null Returns null when $value is null or doesn't match any case
     */
    #[\Override]
    public function get(object $model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        // Cast to int/string to satisfy BackedEnum::tryFrom() strict type at PHPStan level 9.
        // Database values may come as numeric strings; this normalizes them.
        if (! is_int($value) && ! is_string($value)) {
            return null;
        }

        /** @var class-string<T> $enumClass */
        $enumClass = $this->enumClass;

        return $enumClass::tryFrom($value);
    }

    /**
     * Transform enum to storable value.
     *
     * Validates that the value is a valid instance or raw backed value
     * of the target enum class before storing.
     *
     * @param  object  $model  The Eloquent model instance
     * @param  string  $key  The attribute name being cast
     * @param  BackedEnum|int|string|null  $value  The enum instance or raw value to store
     * @param  array<string, mixed>  $attributes  All model attributes
     *
     * @throws \InvalidArgumentException If value is not a valid enum, string, or int
     */
    #[\Override]
    public function set(object $model, string $key, $value, array $attributes): int|string|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            if (! $value instanceof $this->enumClass) {
                throw new \InvalidArgumentException(
                    sprintf('Expected enum %s, got %s', $this->enumClass, get_debug_type($value))
                );
            }

            return $value->value;
        }

        if (! is_int($value) && ! is_string($value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid value type for enum %s', $this->enumClass)
            );
        }

        // Validate raw value — throw on invalid
        /** @var class-string<BackedEnum> $enumClass */
        $enumClass = $this->enumClass;

        if ($enumClass::tryFrom($value) === null) {
            throw new \InvalidArgumentException(
                sprintf('Invalid value [%s] for enum %s', $value, $enumClass)
            );
        }

        return $value;
    }

    /**
     * Serialize enum for JSON (API resources, etc).
     *
     * @param  object  $model  The Eloquent model instance
     * @param  string  $key  The attribute name being serialized
     * @param  BackedEnum|int|string|null  $value  The enum instance or raw value
     * @param  array<string, mixed>  $attributes  All model attributes
     */
    public function serialize(object $model, string $key, $value, array $attributes): int|string|null
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        // Pass through int/string values directly (e.g. already-stored raw values)
        if (is_int($value) || is_string($value)) {
            return $value;
        }

        return null;
    }
}
