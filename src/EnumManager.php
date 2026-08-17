<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums;

use UnitEnum;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Runtime enum helper — accessible via `Enum` facade or injected.
 *
 * Provides runtime access to enum metadata without direct trait usage.
 * All methods validate that the target enum uses {@see HasEnumMetadata}
 * and throw {@see \BadMethodCallException} if not.
 *
 *   Enum::forSelect(UserStatus::class);
 *   Enum::forApi(UserStatus::class);
 *   Enum::tryFromLabel(UserStatus::class, 'Active User');
 *   Enum::tryFromName(UserStatus::class, 'ACTIVE');
 *   Enum::fromName(UserStatus::class, 'ACTIVE');
 *   Enum::hasCase(UserStatus::class, 'ACTIVE');
 *   Enum::values(UserStatus::class);
 *   Enum::labels(UserStatus::class);
 *
 * @see \ZeroBoiler\Enums\Facades\Enum
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata For the trait that provides the actual methods
 */
final readonly class EnumManager
{
    /**
     * Generate select options for an enum class.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use {@see HasEnumMetadata} trait
     * @return list<array{value: int|string, label: string}>
     *
     * @throws \BadMethodCallException If the enum does not use {@see HasEnumMetadata}
     */
    public function forSelect(string $enumClass): array
    {
        if (! method_exists($enumClass, 'forSelect')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var class-string<\UnitEnum> $enumClass */
        return $enumClass::forSelect();
    }

    /**
     * Generate full API metadata for an enum class.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use {@see HasEnumMetadata} trait
     * @return list<array{value: int|string, name: string, label: string, description: ?string, color: string, icon: ?string}>
     *
     * @throws \BadMethodCallException If the enum does not use {@see HasEnumMetadata}
     */
    public function forApi(string $enumClass): array
    {
        if (! method_exists($enumClass, 'forApi')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var class-string<\UnitEnum> $enumClass */
        return $enumClass::forApi();
    }

    /**
     * Resolve an enum case by its human-readable label (case-insensitive).
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use {@see HasEnumMetadata} trait
     * @param  string  $label  The label to search for (case-insensitive)
     * @return \UnitEnum|null The matching case, or null if no label matches
     *
     * @throws \BadMethodCallException If the enum does not use {@see HasEnumMetadata}
     */
    public function tryFromLabel(string $enumClass, string $label): ?\UnitEnum
    {
        if (! method_exists($enumClass, 'tryFromLabel')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var class-string<\UnitEnum> $enumClass */
        return $enumClass::tryFromLabel($label);
    }

    /**
     * Resolve a case by its enum name (e.g. 'ACTIVE' → UserStatus::ACTIVE).
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use {@see HasEnumMetadata} trait
     * @param  string  $name  The case name (e.g. 'ACTIVE', 'PENDING')
     * @return \UnitEnum|null The enum case, or null if not found
     *
     * @throws \BadMethodCallException If the enum does not use {@see HasEnumMetadata}
     */
    public function tryFromName(string $enumClass, string $name): ?\UnitEnum
    {
        if (! method_exists($enumClass, 'tryFromName')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var class-string<\UnitEnum> $enumClass */
        return $enumClass::tryFromName($name);
    }

    /**
     * Check if a case with the given name exists on the enum.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use {@see HasEnumMetadata} trait
     * @param  string  $name  The case name (e.g. 'ACTIVE')
     * @return bool True if the case exists, false otherwise
     *
     * @throws \BadMethodCallException If the enum does not use {@see HasEnumMetadata}
     */
    public function hasCase(string $enumClass, string $name): bool
    {
        if (! method_exists($enumClass, 'hasCase')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var class-string<\UnitEnum> $enumClass */
        return $enumClass::hasCase($name);
    }

    /**
     * Resolve a case by its enum name, throwing on failure.
     *
     * Delegates to {@see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::fromName()}.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use {@see HasEnumMetadata} trait
     * @param  string  $name  The case name (e.g. 'ACTIVE', 'PENDING')
     * @return \UnitEnum The enum case
     *
     * @throws \BadMethodCallException If the enum does not use {@see HasEnumMetadata}
     * @throws \ZeroBoiler\Enums\Exceptions\InvalidEnumException If no case with the given name exists
     */
    public function fromName(string $enumClass, string $name): \UnitEnum
    {
        if (! method_exists($enumClass, 'fromName')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var class-string<\UnitEnum> $enumClass */
        return $enumClass::fromName($name);
    }

    /**
     * Get all backed values or case names for an enum class.
     *
     * Delegates to {@see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::values()}.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use {@see HasEnumMetadata} trait
     * @return list<string|int>
     *
     * @throws \BadMethodCallException If the enum does not use {@see HasEnumMetadata}
     */
    public function values(string $enumClass): array
    {
        if (! method_exists($enumClass, 'values')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var class-string<\UnitEnum> $enumClass */
        return $enumClass::values();
    }

    /**
     * Get all labels for every enum case.
     *
     * Delegates to {@see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::labels()}.
     *
     * @param  class-string<\UnitEnum>  $enumClass  Must use {@see HasEnumMetadata} trait
     * @return list<string>
     *
     * @throws \BadMethodCallException If the enum does not use {@see HasEnumMetadata}
     */
    public function labels(string $enumClass): array
    {
        if (! method_exists($enumClass, 'labels')) {
            throw new \BadMethodCallException(
                "[{$enumClass}] does not use HasEnumMetadata trait."
            );
        }

        /** @var class-string<\UnitEnum> $enumClass */
        return $enumClass::labels();
    }
}
