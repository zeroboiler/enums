<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use UnitEnum;

/**
 * Universal enum cast — works with any backed enum.
 *
 * Auto-detected by NovaForge enums. No need to manually register:
 *
 *   protected $casts = [
 *       'status' => UserStatus::class,  // works automatically
 *   ];
 *
 * @template T of \BackedEnum
 */
class EnumCast implements CastsAttributes
{
    /**
     * @param  class-string<T>  $enumClass
     */
    public function __construct(private readonly string $enumClass) {}

    /**
     * Cast raw value to enum instance.
     *
     * @param  string|int|null  $value
     * @return T|null
     */
    public function get(object $model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        /** @var T $enumClass */
        $enumClass = $this->enumClass;

        return $enumClass::tryFrom($value);
    }

    /**
     * Transform enum to storable value.
     *
     * @param  T|UnitEnum|null  $value
     * @return string|int|null
     */
    public function set(object $model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
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
     * @param  T|null  $value
     * @return string|int|null
     */
    public function serialize(object $model, string $key, $value, array $attributes)
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
