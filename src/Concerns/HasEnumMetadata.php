<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Concerns;

use BackedEnum;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * Smart enum trait — zero boilerplate metadata, serialization and helpers.
 *
 * @see EnumMetadataResolver For attribute resolution internals.
 */
trait HasEnumMetadata
{
    public function label(): string
    {
        $meta = EnumMetadataResolver::resolve(static::class);
        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['labels'][$value]
            ?? $meta['labels'][$this->name]
            ?? $this->generateLabel();
    }

    public function description(): ?string
    {
        $meta = EnumMetadataResolver::resolve(static::class);
        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['descriptions'][$value]
            ?? $meta['descriptions'][$this->name]
            ?? null;
    }

    public function color(): string
    {
        $meta = EnumMetadataResolver::resolve(static::class);
        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['colors'][$value]
            ?? $meta['colors'][$this->name]
            ?? 'secondary';
    }

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
     * Reverse label lookup — finds the enum case by its label.
     *
     * Matching strategy (prevents ambiguous results):
     * 1. Exact (case-sensitive) match — always wins.
     * 2. Case-insensitive fallback — only if exactly one case matches.
     * 3. Returns null when multiple cases match case-insensitively (ambiguous).
     */
    public static function tryFromLabel(string $label): ?static
    {
        // 1. Exact (case-sensitive) match
        foreach (self::cases() as $case) {
            if ($case->label() === $label) {
                return $case;
            }
        }

        // 2. Case-insensitive fallback — collect all matches
        $matches = array_filter(
            self::cases(),
            static fn (self $case): bool => strcasecmp($case->label(), $label) === 0
        );

        // 3. Only return if unambiguous (exactly one match)
        return count($matches) === 1 ? array_first($matches) : null;
    }

    /**
     * Reverse label lookup — throws when the label is not found.
     *
     * @throws \ValueError When no case matches the label.
     */
    public static function fromLabel(string $label): static
    {
        $case = self::tryFromLabel($label);

        if ($case === null) {
            $labels = implode(', ', self::labels());

            throw new \ValueError(
                sprintf('"%s" is not a valid label for enum %s. Valid labels: %s', $label, static::class, $labels)
            );
        }

        return $case;
    }

    /**
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
