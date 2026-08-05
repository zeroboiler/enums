<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Concerns;

use BackedEnum;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * Smart enum trait — zero boilerplate metadata, serialization and helpers.
 *
 * @see EnumMetadataResolver For attribute resolution internals.
 */
trait HasEnumMetadata
{
    /**
     * Get the human-readable label for this enum case.
     *
     * Resolved from per-case #[Label] attribute, then class-level
     * #[EnumLabel], then auto-generated from the case name.
     *
     *   SCREAMING_SNAKE_CASE → Title Case
     *   camelCase → Title Case
     */
    public function label(): string
    {
        $meta = EnumMetadataResolver::resolve(static::class);
        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['labels'][$value]
            ?? $meta['labels'][$this->name]
            ?? $this->generateLabel();
    }

    /**
     * Get the human-readable description for this enum case.
     *
     * Resolved from per-case #[Description] attribute, then class-level
     * #[EnumDescription], or null if not defined.
     */
    public function description(): ?string
    {
        $meta = EnumMetadataResolver::resolve(static::class);
        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['descriptions'][$value]
            ?? $meta['descriptions'][$this->name]
            ?? null;
    }

    /**
     * Get the color for this enum case.
     *
     * Resolved from per-case #[Color] attribute, then class-level
     * #[EnumColor], or defaults to 'secondary'.
     */
    public function color(): string
    {
        $meta = EnumMetadataResolver::resolve(static::class);
        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['colors'][$value]
            ?? $meta['colors'][$this->name]
            ?? 'secondary';
    }

    /**
     * Get the icon for this enum case.
     *
     * Resolved from per-case #[Icon] attribute, then class-level
     * #[EnumIcon], or null if not defined.
     */
    public function icon(): ?string
    {
        $meta = EnumMetadataResolver::resolve(static::class);
        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['icons'][$value]
            ?? $meta['icons'][$this->name]
            ?? null;
    }

    /**
     * @return list<array{value: string|int, label: string}>
     */
    public static function forSelect(): array
    {
        return array_map(static fn (self $case): array => [
            'value' => $case instanceof BackedEnum ? $case->value : $case->name,
            'label' => $case->label(),
        ], self::cases());
    }

    /**
     * @return list<array{value: string|int, name: string, label: string, description: ?string, color: string, icon: ?string}>
     */
    public static function forApi(): array
    {
        return array_map(static fn (self $case): array => [
            'value' => $case instanceof BackedEnum ? $case->value : $case->name,
            'name' => $case->name,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
            'icon' => $case->icon(),
        ], self::cases());
    }

    /**
     * Resolve an enum case by its human-readable label (case-insensitive).
     *
     * Iterates all cases and compares labels using strcasecmp.
     */
    public static function tryFromLabel(string $label): ?static
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->label(), $label) === 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Resolve a case by its enum name (e.g. 'ACTIVE' → UserStatus::ACTIVE).
     *
     * Unlike PHP's native tryFrom() which works on *values* of backed enums,
     * this method works on *case names* and supports both backed and pure enums.
     *
     * @param  string  $name  The case name (e.g. 'ACTIVE', 'PENDING')
     * @return static|null The enum case, or null if not found
     */
    public static function tryFromName(string $name): ?static
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Resolve a case by its enum name, throwing on failure.
     *
     * @param  string  $name  The case name (e.g. 'ACTIVE', 'PENDING')
     *
     * @throws InvalidEnumException If no case with the given name exists
     */
    public static function fromName(string $name): static
    {
        $case = self::tryFromName($name);

        if ($case === null) {
            throw InvalidEnumException::forName(static::class, $name);
        }

        return $case;
    }

    /**
     * Check if a case with the given name exists on this enum.
     *
     * @param  string  $name  The case name (e.g. 'ACTIVE')
     */
    public static function hasCase(string $name): bool
    {
        return self::tryFromName($name) !== null;
    }

    /**
     * Get all backed values or case names for this enum.
     *
     * For backed enums returns the backed values (int|string).
     * For pure enums returns the case names (string).
     *
     * @return list<string|int>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $case) => $case instanceof BackedEnum ? $case->value : $case->name,
            self::cases(),
        );
    }

    /**
     * Get all labels for every enum case, in case declaration order.
     *
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_map(static fn (self $case): string => $case->label(), self::cases());
    }

    /**
     * SCREAMING_SNAKE_CASE → Title Case
     * camelCase → Title Case
     */
    private function generateLabel(): string
    {
        $name = $this->name;

        if ($name === strtoupper((string) $name)) {
            return ucwords(trim(str_replace('_', ' ', strtolower($name))));
        }

        $label = preg_replace('/(?<!^)([A-Z])/', ' $1', (string) $name) ?? $name;

        return ucwords(trim(strtolower((string) $label)));
    }
}
