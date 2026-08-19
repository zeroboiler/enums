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
 * @see \ZeroBoiler\Enums\EnumsServiceProvider For auto-detection setup
 * @see \ZeroBoiler\Enums\Rules\EnumRule For validation-side enum enforcement
 *
 * @template T of \\BackedEnum
 *
 * @implements CastsAttributes<T|null, int|string|null>
 */
final class EnumCast implements CastsAttributes
{
    /**
     * Named constructor — creates an EnumCast for the given backed enum.
     *
     *   protected $casts = [
     *       'status' => EnumCast::of(UserStatus::class),
     *   ];
     *
     * @param  class-string<T>  $enumClass  The backed enum class to cast to/from
     * @return self<T> A new EnumCast instance for the given enum
     */
    public static function of(string $enumClass): self
    {
        return new self($enumClass);
    }

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
    public function get(object $model, string $key, int|string|null $value, array $attributes): ?BackedEnum
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
     * @return int|string|null The backed value, raw int/string, or null
     *
     * @throws \InvalidArgumentException If value is not a valid enum, string, or int
     */
    #[\Override]
    public function set(object $model, string $key, BackedEnum|int|string|null $value, array $attributes): int|string|null
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
     * Laravel calls this method when serializing model attributes to JSON.
     * Not part of the CastsAttributes interface — Laravel detects it via method_exists().
     *
     * @param  object  $model  The Eloquent model instance
     * @param  string  $key  The attribute name being serialized
     * @param  BackedEnum|int|string|null  $value  The enum instance or raw value
     * @param  array<string, mixed>  $attributes  All model attributes
     * @return int|string|null
     */
    public function serialize(object $model, string $key, BackedEnum|int|string|null $value, array $attributes): int|string|null
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
