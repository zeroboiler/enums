<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums;

use UnitEnum;

/**
 * Runtime enum helper — accessible via `Enum` facade or injected.
 *
 * Provides runtime access to enum metadata without direct trait usage.
 *
 *   Enum::forSelect(UserStatus::class);
 *   Enum::forApi(UserStatus::class);
 *   Enum::tryFromLabel(UserStatus::class, 'Active User');
 */
final class EnumManager
{
    /**
     * Generate select options for an enum class.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use HasEnumMetadata trait
     * @return list<array{value: string|int, label: string}>
     *
     * @throws \BadMethodCallException If the enum does not use HasEnumMetadata
     */
    public function forSelect(string $enumClass): array
    {
        if (! method_exists($enumClass, 'forSelect')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var list<array{value: string|int, label: string}> */
        return $enumClass::forSelect();
    }

    /**
     * Generate full API metadata for an enum class.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use HasEnumMetadata trait
     * @return list<array{value: string|int, name: string, label: string, description: ?string, color: string, icon: ?string}>
     *
     * @throws \BadMethodCallException If the enum does not use HasEnumMetadata
     */
    public function forApi(string $enumClass): array
    {
        if (! method_exists($enumClass, 'forApi')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var list<array{value: string|int, name: string, label: string, description: ?string, color: string, icon: ?string}> */
        return $enumClass::forApi();
    }

    /**
     * Resolve an enum case by its human-readable label (case-insensitive).
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use HasEnumMetadata trait
     * @return \UnitEnum|null The matching case, or null if no label matches
     *
     * @throws \BadMethodCallException If the enum does not use HasEnumMetadata
     */
    public function tryFromLabel(string $enumClass, string $label): ?\UnitEnum
    {
        if (! method_exists($enumClass, 'tryFromLabel')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        return $enumClass::tryFromLabel($label);
    }
}
