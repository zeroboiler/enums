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
 *
 * @phpstan-type EnumMetadata array{labels: array<string, string>, descriptions: array<string, string>, colors: array<string, string>, icons: array<string, string>}
 */
final class EnumMetadataResolver
{
    private static ?EnumCache $cache = null;

    /**
     * Resolve all metadata for an enum class.
     *
     * @param  class-string<UnitEnum>  $enumClass
     * @return EnumMetadata
     */
    public static function resolve(string $enumClass): array
    {
        $cache = self::getCache();
        if ($cache->has($enumClass)) {
            /** @var EnumMetadata */
            return $cache->get($enumClass);
        }

        $metadata = self::buildMetadata($enumClass);

        $cache->set($enumClass, $metadata);

        return $metadata;
    }

    /**
     * Build complete metadata by merging class-level and per-case attributes.
     *
     * @param  class-string<UnitEnum>  $enumClass
     * @return EnumMetadata
     */
    private static function buildMetadata(string $enumClass): array
    {
        $labels = [];
        $descriptions = [];
        $colors = [];
        $icons = [];

        // --- Class-level attributes ---
        $reflection = new ReflectionEnum($enumClass);

        foreach ($reflection->getAttributes() as $attr) {
            $instance = $attr->newInstance();

            if ($instance instanceof EnumLabel && $instance->labels) {
                $labels = $instance->labels;
            }

            if ($instance instanceof EnumDescription && $instance->descriptions) {
                $descriptions = $instance->descriptions;
            }

            if ($instance instanceof EnumColor) {
                foreach (['success', 'danger', 'warning', 'info', 'secondary'] as $colorName) {
                    foreach ($instance->$colorName as $caseValue) {
                        $colors[$caseValue] = $colorName;
                    }
                }
            }

            if ($instance instanceof EnumIcon && $instance->default) {
                foreach ($enumClass::cases() as $case) {
                    $caseValue = $case instanceof BackedEnum ? $case->value : $case->name;
                    $icons[$caseValue] = $instance->default;
                }
            }
        }

        // --- Per-case attributes (override class-level) ---
        foreach ($enumClass::cases() as $case) {
            $caseReflection = $reflection->getCase($case->name);
            $value = $case instanceof BackedEnum ? $case->value : $case->name;

            foreach ($caseReflection->getAttributes() as $attr) {
                $instance = $attr->newInstance();

                if ($instance instanceof Label) {
                    $labels[$value] = $instance->value;
                }

                if ($instance instanceof Description) {
                    $descriptions[$value] = $instance->value;
                }

                if ($instance instanceof Color) {
                    $colors[$value] = $instance->value;
                }

                if ($instance instanceof Icon) {
                    $icons[$value] = $instance->value;
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

    private static function getCache(): EnumCache
    {
        if (! self::$cache instanceof EnumCache) {
            self::$cache = EnumCache::getInstance();
        }

        return self::$cache;
    }
}
