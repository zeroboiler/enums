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
 *
 * @see \ZeroBoiler\Enums\Facades\Enum
 */
final readonly class EnumManager
{
    /**
     * Generate select options for an enum class.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use HasEnumMetadata trait
     * @return list<array{value: int|string, label: string}>
     *
     * @throws \BadMethodCallException If the enum does not use HasEnumMetadata
     * @throws \ReflectionException If the enum class does not exist
     */
    public function forSelect(string $enumClass): array
    {
        if (! method_exists($enumClass, 'forSelect')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var list<array{value: int|string, label: string}> */
        return $enumClass::forSelect();
    }

    /**
     * Generate full API metadata for an enum class.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use HasEnumMetadata trait
     * @return list<array{value: int|string, name: string, label: string, description: ?string, color: string, icon: ?string}>
     *
     * @throws \BadMethodCallException If the enum does not use HasEnumMetadata
     * @throws \ReflectionException If the enum class does not exist
     */
    public function forApi(string $enumClass): array
    {
        if (! method_exists($enumClass, 'forApi')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var list<array{value: int|string, name: string, label: string, description: ?string, color: string, icon: ?string}> */
        return $enumClass::forApi();
    }

    /**
     * Resolve an enum case by its human-readable label (case-insensitive).
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use HasEnumMetadata trait
     * @param  string  $label  The label to search for (case-insensitive)
     * @return \UnitEnum|null The matching case, or null if no label matches
     *
     * @throws \BadMethodCallException If the enum does not use HasEnumMetadata
     * @throws \ReflectionException If the enum class does not exist
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

    /**
     * Resolve a case by its enum name (e.g. 'ACTIVE' → UserStatus::ACTIVE).
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use HasEnumMetadata trait
     * @param  string  $name  The case name (e.g. 'ACTIVE', 'PENDING')
     * @return \UnitEnum|null The enum case, or null if not found
     *
     * @throws \BadMethodCallException If the enum does not use HasEnumMetadata
     * @throws \ReflectionException If the enum class does not exist
     */
    public function tryFromName(string $enumClass, string $name): ?\UnitEnum
    {
        if (! method_exists($enumClass, 'tryFromName')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        return $enumClass::tryFromName($name);
    }

    /**
     * Check if a case with the given name exists on the enum.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use HasEnumMetadata trait
     * @param  string  $name  The case name (e.g. 'ACTIVE')
     * @return bool True if the case exists, false otherwise
     *
     * @throws \BadMethodCallException If the enum does not use HasEnumMetadata
     * @throws \ReflectionException If the enum class does not exist
     */
    public function hasCase(string $enumClass, string $name): bool
    {
        if (! method_exists($enumClass, 'hasCase')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        return $enumClass::hasCase($name);
    }
}
