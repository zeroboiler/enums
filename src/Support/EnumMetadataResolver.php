<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Support;

use BackedEnum;
use ReflectionEnum;
use UnitEnum;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\EnumCache;

/**
 * Resolves metadata (labels, descriptions, colors, icons) for enums
 * from class-level and per-case attributes.
 *
 * Extracted from HasEnumMetadata trait to reduce complexity.
 * Results are cached by {@see EnumCache} with TTL-based expiration.
 *
 * @phpstan-type EnumMetadataShape array{
 *     labels: array<int|string, string>,
 *     descriptions: array<int|string, string>,
 *     colors: array<int|string, string>,
 *     icons: array<int|string, string>
 * }
 */
final class EnumMetadataResolver
{
    /**
     * Resolve all metadata for an enum class.
     *
     * Results are cached by {@see EnumCache} with TTL-based expiration.
     * Subsequent calls for the same class return the cached result.
     *
     * @param  class-string<UnitEnum>  $enumClass
     * @return EnumMetadataShape
     *
     * @throws \ReflectionException If the class does not exist or is not an enum
     */
    public static function resolve(string $enumClass): array
    {
        $cache = EnumCache::getInstance();
        if ($cache->has($enumClass)) {
            /** @var EnumMetadataShape */
            return $cache->get($enumClass);
        }

        $metadata = self::buildMetadata($enumClass);

        $cache->set($enumClass, $metadata);

        return $metadata;
    }

    /**
     * Invalidate cached metadata for a specific enum class.
     *
     * Forces the next call to {@see resolve()} to rebuild metadata
     * from scratch via reflection. Useful after runtime attribute
     * modification or in testing scenarios.
     *
     * @param  class-string<UnitEnum>  $enumClass
     */
    public static function invalidate(string $enumClass): void
    {
        EnumCache::getInstance()->clearClass($enumClass);
    }

    /**
     * Invalidate all cached metadata for every enum class.
     *
     * Alias for {@see EnumCache::flush()}.
     */
    public static function invalidateAll(): void
    {
        EnumCache::flush();
    }

    /**
     * Build complete metadata by merging class-level and per-case attributes.
     *
     * @param  class-string<UnitEnum>  $enumClass
     * @return EnumMetadataShape
     *
     * @throws \ReflectionException If the class does not exist or is not an enum
     */
    private static function buildMetadata(string $enumClass): array
    {
        /** @var array<int|string, string> $labels */
        $labels = [];
        /** @var array<int|string, string> $descriptions */
        $descriptions = [];
        /** @var array<int|string, string> $colors */
        $colors = [];
        /** @var array<int|string, string> $icons */
        $icons = [];

        // --- Class-level attributes ---
        $reflection = new ReflectionEnum($enumClass);

        foreach ($reflection->getAttributes() as $attr) {
            /** @var object $instance */
            $instance = $attr->newInstance();

            if ($instance instanceof EnumLabel && $instance->labels !== null && $instance->labels !== []) {
                $labels = $instance->labels;
            }

            if ($instance instanceof EnumDescription && $instance->descriptions !== null && $instance->descriptions !== []) {
                $descriptions = $instance->descriptions;
            }

            if ($instance instanceof EnumColor) {
                /** @var array<string, list<int|string>> $colorMap */
                $colorMap = [
                    'success' => $instance->success,
                    'danger' => $instance->danger,
                    'warning' => $instance->warning,
                    'info' => $instance->info,
                    'secondary' => $instance->secondary,
                ];
                foreach ($colorMap as $colorName => $caseValues) {
                    foreach ($caseValues as $caseValue) {
                        $colors[$caseValue] = $colorName;
                    }
                }
            }

            if ($instance instanceof EnumIcon) {
                // Apply per-case icon map first (specific icons for specific values)
                foreach ($instance->icons as $caseValue => $icon) {
                    $icons[$caseValue] = $icon;
                }

                // Apply default icon for cases without a specific icon
                if ($instance->default !== null && $instance->default !== '') {
                    foreach ($enumClass::cases() as $case) {
                        /** @var int|string $caseValue */
                        $caseValue = $case instanceof BackedEnum ? $case->value : $case->name;
                        if (! isset($icons[$caseValue])) {
                            $icons[$caseValue] = $instance->default;
                        }
                    }
                }
            }
        }

        // --- Per-case attributes (override class-level) ---
        foreach ($enumClass::cases() as $case) {
            $caseReflection = $reflection->getCase($case->name);
            /** @var int|string $value */
            $value = $case instanceof BackedEnum ? $case->value : $case->name;

            foreach ($caseReflection->getAttributes() as $attr) {
                /** @var object $instance */
                $instance = $attr->newInstance();

                if ($instance instanceof Label) {
                    $labels[$value] = $instance->value;
                }

                if ($instance instanceof EnumLabel && $instance->label !== null && $instance->label !== '') {
                    $labels[$value] = $instance->label;
                }

                if ($instance instanceof Description) {
                    $descriptions[$value] = $instance->value;
                }

                if ($instance instanceof EnumDescription && $instance->description !== null && $instance->description !== '') {
                    $descriptions[$value] = $instance->description;
                }

                if ($instance instanceof Color) {
                    $colors[$value] = $instance->value;
                }

                if ($instance instanceof Icon) {
                    $icons[$value] = $instance->value;
                }

                // Per-case EnumIcon overrides (single case icon via EnumIcon attribute)
                if ($instance instanceof EnumIcon && $instance->default !== null && $instance->default !== '') {
                    $icons[$value] = $instance->default;
                }
            }
        }

        return [
            'labels' => $labels,
            'descriptions' => $descriptions,
            'colors' => $colors,
            'icons' => $icons,
        ];
    }
}
