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
     * @param  int|string|null  $value
     * @param  array<string, mixed>  $attributes
     * @return T|null
     *
     * @throws \InvalidArgumentException If the stored value does not match any enum case
     */
    #[\Override]
    public function get(object $model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        /** @var class-string<T> $enumClass */
        $enumClass = $this->enumClass;

        return $enumClass::tryFrom($value);
    }

    /**
     * Transform enum to storable value.
     *
     * @param  BackedEnum|int|string|null  $value
     * @param  array<string, mixed>  $attributes
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
     * @param  BackedEnum|int|string|null  $value
     * @param  array<string, mixed>  $attributes
     */
    #[\Override]
    public function serialize(object $model, string $key, $value, array $attributes): int|string|null
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
