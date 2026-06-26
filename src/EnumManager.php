<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums;

use BackedEnum;

/**
 * Runtime enum helper — accessible via `Enum` facade or injected.
 *
 *   Enum::forSelect(UserStatus::class);
 *   Enum::forApi(UserStatus::class);
 */
final class EnumManager
{
    /**
     * @param  class-string<\BackedEnum>  $enumClass
     * @return list<array{value: string|int, label: string}>
     */
    public function forSelect(string $enumClass): array
    {
        if (!method_exists($enumClass, 'forSelect')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        return $enumClass::forSelect();
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     * @return list<array<string, mixed>>
     */
    public function forApi(string $enumClass): array
    {
        if (!method_exists($enumClass, 'forApi')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        return $enumClass::forApi();
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     */
    public function tryFromLabel(string $enumClass, string $label): ?BackedEnum
    {
        if (!method_exists($enumClass, 'tryFromLabel')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        return $enumClass::tryFromLabel($label);
    }
}
