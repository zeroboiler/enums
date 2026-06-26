<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
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
    /** @var class-string<T> */
    private string $enumClass;

    /**
     * @param  class-string<T>  $enumClass
     */
    public function __construct(string $enumClass)
    {
        $this->enumClass = $enumClass;
    }

    /**
     * Cast raw value to enum instance.
     *
     * @param  Model  $model
     * @param  string|int|null  $value
     * @return T|null
     */
    public function get($model, string $key, $value, array $attributes)
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
     * @param  Model  $model
     * @param  T|UnitEnum|null  $value
     * @return string|int|null
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        // Already a raw value — validate it
        /** @var T $enumClass */
        $enumClass = $this->enumClass;

        if ($enumClass::tryFrom($value) !== null) {
            return $value;
        }

        return $value;
    }

    /**
     * Serialize enum for JSON (API resources, etc).
     *
     * @param  Model  $model
     * @param  T|null  $value
     * @return string|int|null
     */
    public function serialize($model, string $key, $value, array $attributes)
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
