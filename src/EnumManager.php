<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums;

use BackedEnum;

/**
 * Runtime enum helper — accessible via `Enum` facade or injected.
 *
 *   Enum::forSelect(UserStatus::class);
 *   Enum::forApi(UserStatus::class);
 *   Enum::tryFromLabel(UserStatus::class, 'Active User');
 */
final class EnumManager
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return list<array{value: string|int, label: string}>
     */
    public function forSelect(string $enumClass): array
    {
        if (! method_exists($enumClass, 'forSelect')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        return $enumClass::forSelect();
    }

    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return list<array<string, mixed>>
     */
    public function forApi(string $enumClass): array
    {
        if (! method_exists($enumClass, 'forApi')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        return $enumClass::forApi();
    }

    /**
     * Perform a reverse label-to-case lookup via the enum's trait method.
     *
     * @param  class-string<BackedEnum>  $enumClass
     * @param  string  $label  The human-readable label to search for (case-insensitive).
     * @return BackedEnum|null The matching enum case, or null if no match found.
     */
    public function tryFromLabel(string $enumClass, string $label): ?BackedEnum
    {
        if (! method_exists($enumClass, 'tryFromLabel')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var BackedEnum|null */
        return $enumClass::tryFromLabel($label);
    }
}
