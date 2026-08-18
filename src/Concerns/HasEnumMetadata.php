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
 * Provides a complete public API for enum metadata access, bulk generation,
 * comparison, reverse lookup, and label generation. Works with all three
 * PHP enum types: string-backed, int-backed, and pure enums.
 *
 * @see EnumMetadataResolver For attribute resolution internals.
 * @see \ZeroBoiler\Enums\Attributes\Label Per-case label override
 * @see \ZeroBoiler\Enums\Attributes\Color Per-case color override
 * @see \ZeroBoiler\Enums\Attributes\Icon Per-case icon override
 * @see \ZeroBoiler\Enums\Attributes\Description Per-case description override
 * @see \ZeroBoiler\Enums\Attributes\EnumLabel Class-level bulk label mapping
 * @see \ZeroBoiler\Enums\Attributes\EnumColor Class-level bulk color mapping
 * @see \ZeroBoiler\Enums\Attributes\EnumIcon Class-level default + per-value icon mapping
 * @see \ZeroBoiler\Enums\Attributes\EnumDescription Class-level bulk description mapping
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
     *
     * @see \ZeroBoiler\Enums\Attributes\Label For per-case label override
     * @see \ZeroBoiler\Enums\Attributes\EnumLabel For class-level label mapping
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
     *
     * @see \ZeroBoiler\Enums\Attributes\Description For per-case description override
     * @see \ZeroBoiler\Enums\Attributes\EnumDescription For class-level description mapping
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
     *
     * @see \ZeroBoiler\Enums\Attributes\Color For per-case color override
     * @see \ZeroBoiler\Enums\Attributes\EnumColor For class-level color mapping
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
     *
     * @see \ZeroBoiler\Enums\Attributes\Icon For per-case icon override
     * @see \ZeroBoiler\Enums\Attributes\EnumIcon For class-level icon mapping
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
     * Generate select options for all enum cases.
     *
     * Returns an array of `{value, label}` pairs suitable for HTML `<select>` elements.
     * For backed enums, value is the backed value. For pure enums, value is the case name.
     *
     * @return list<array{value: int|string, label: string}>
     *
     * @see forApi() For full metadata output
     */
    public static function forSelect(): array
    {
        return array_map(static fn (self $case): array => [
            'value' => $case instanceof BackedEnum ? $case->value : $case->name,
            'label' => $case->label(),
        ], self::cases());
    }

    /**
     * Generate full API metadata for all enum cases.
     *
     * Returns an array of associative arrays containing value, name, label,
     * description, color, and icon for each case — suitable for API responses.
     *
     * @return list<array{value: int|string, name: string, label: string, description: ?string, color: string, icon: ?string}>
     *
     * @see forSelect() For dropdown-ready value/label pairs only
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
     * For backed enums, labels may differ from values (e.g. 'Active User' vs 'active').
     * For pure enums, labels are auto-generated from case names unless overridden.
     *
     * @param  string  $label  The label to search for (case-insensitive)
     * @return static|null The matching enum case, or null if no label matches
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
     *   UserStatus::hasCase('ACTIVE');  // true
     *   UserStatus::hasCase('UNKNOWN'); // false
     *
     * @param  string  $name  The case name (e.g. 'ACTIVE')
     * @return bool True if the case exists, false otherwise
     */
    public static function hasCase(string $name): bool
    {
        return self::tryFromName($name) !== null;
    }

    /**
     * Check if this enum case matches the given case.
     *
     * Uses strict identity comparison. Accepts both enum instances
     * and case names (strings) for flexible comparison.
     *
     *   if ($status->is(UserStatus::ACTIVE)) { ... }
     *   if ($status->is('ACTIVE')) { ... }
     *
     * @param  static|string  $case  The case instance or name to compare against
     */
    public function is(self|string $case): bool
    {
        if ($case instanceof self) {
            return $this === $case;
        }

        return $this->name === $case;
    }

    /**
     * Check if this enum case does NOT match the given case.
     *
     * Negation of {@see is()}.
     *
     *   if ($status->isNot(UserStatus::BANNED)) { ... }
     *   if ($status->isNot('BANNED')) { ... }
     *
     * @param  static|string  $case  The case instance or name to compare against
     */
    public function isNot(self|string $case): bool
    {
        return ! $this->is($case);
    }

    /**
     * Check if this enum case is one of the given cases.
     *
     * Useful for grouping related states:
     *
     *   if ($status->in([UserStatus::ACTIVE, UserStatus::PENDING])) { ... }
     *   if ($status->in(['ACTIVE', 'PENDING'])) { ... }
     *
     * @param  array<static|string>  $cases  List of case instances or names
     */
    public function in(array $cases): bool
    {
        foreach ($cases as $case) {
            if ($this->is($case)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this enum case is NOT any of the given cases.
     *
     * Negation of {@see in()}. Useful for exclusion logic:
     *
     *   if ($status->notIn([UserStatus::BANNED, UserStatus::DELETED])) { ... }
     *   if ($status->notIn(['BANNED', 'DELETED'])) { ... }
     *
     * @param  array<static|string>  $cases  List of case instances or names to exclude
     */
    public function notIn(array $cases): bool
    {
        return ! $this->in($cases);
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
     * Get the backed value for backed enums, or the case name for pure enums.
     *
     * A convenience accessor that normalizes access across all three PHP enum types:
     * - String-backed: returns the string value (e.g. 'active')
     * - Int-backed: returns the int value (e.g. 1)
     * - Pure enum: returns the case name (e.g. 'ACTIVE')
     *
     * Useful when you need the storable/serializable representation without
     * checking instanceof BackedEnum first:
     *
     *   $value = $status->toValue();  // 'active' or 'ACTIVE' depending on backing
     *
     * @return int|string
     */
    public function toValue(): int|string
    {
        return $this instanceof BackedEnum ? $this->value : $this->name;
    }

    /**
     * Generate a human-readable label from the case name.
     *
     * Conversion rules:
     * - SCREAMING_SNAKE_CASE → "Title Case" (e.g. USER_STATUS → "User Status")
     * - camelCase → "Title Case" (e.g. activeUser → "Active User")
     *
     * This is the final fallback when neither per-case #[Label] nor
     * class-level #[EnumLabel] provides a label for this case.
     *
     * @return string Human-readable label in Title Case
     */
    private function generateLabel(): string
    {
        $name = $this->name;

        if ($name === strtoupper($name)) {
            return ucwords(trim(str_replace('_', ' ', strtolower($name))));
        }

        $label = preg_replace('/(?<!^)([A-Z])/', ' $1', $name) ?? $name;

        return ucwords(trim(strtolower($label)));
    }
}
