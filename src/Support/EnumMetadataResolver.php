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
 */
final class EnumMetadataResolver
{
    private static ?EnumCache $cache = null;

    /**
     * Resolve all metadata for an enum class.
     *
     * @param  class-string  $enumClass
     * @return array{labels: array<string,string>, descriptions: array<string,string>, colors: array<string,string>, icons: array<string,string>}
     */
    public static function resolve(string $enumClass): array
    {
        $cache = self::getCache();
        if ($cache->has($enumClass)) {
            return $cache->get($enumClass);
        }

        $reflection = new ReflectionEnum($enumClass);

        $metadata = [
            'labels' => self::resolveClassLabels($reflection),
            'descriptions' => self::resolveClassDescriptions($reflection),
            'colors' => self::resolveClassColors($reflection),
            'icons' => self::resolveClassIcons($reflection),
        ];

        // Per-case attributes override class-level
        $perCase = self::resolvePerCaseAttributes($reflection, $enumClass);

        $metadata['labels'] = array_merge($metadata['labels'], $perCase['labels']);
        $metadata['descriptions'] = array_merge($metadata['descriptions'], $perCase['descriptions']);
        $metadata['colors'] = array_merge($metadata['colors'], $perCase['colors']);
        $metadata['icons'] = array_merge($metadata['icons'], $perCase['icons']);

        $cache->set($enumClass, $metadata);

        return $metadata;
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     * @return array<string, string>
     */
    private static function resolveClassLabels(ReflectionEnum $reflection): array
    {
        $labels = [];
        foreach ($reflection->getAttributes() as $attr) {
            $instance = $attr->newInstance();
            if ($instance instanceof EnumLabel && $instance->labels) {
                $labels = $instance->labels;
            }
        }

        return $labels;
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     * @return array<string, string>
     */
    private static function resolveClassDescriptions(ReflectionEnum $reflection): array
    {
        $descriptions = [];
        foreach ($reflection->getAttributes() as $attr) {
            $instance = $attr->newInstance();
            if ($instance instanceof EnumDescription && $instance->descriptions) {
                $descriptions = $instance->descriptions;
            }
        }

        return $descriptions;
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     * @return array<string, string>
     */
    private static function resolveClassColors(ReflectionEnum $reflection): array
    {
        $colors = [];
        foreach ($reflection->getAttributes() as $attr) {
            $instance = $attr->newInstance();
            if ($instance instanceof EnumColor) {
                foreach (['success', 'danger', 'warning', 'info', 'secondary'] as $color) {
                    foreach ($instance->$color as $caseValue) {
                        $colors[$caseValue] = $color;
                    }
                }
            }
        }

        return $colors;
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     * @return array<string, string>
     */
    private static function resolveClassIcons(ReflectionEnum $reflection): array
    {
        $icons = [];
        foreach ($reflection->getAttributes() as $attr) {
            $instance = $attr->newInstance();
            if ($instance instanceof EnumIcon && $instance->default) {
                $enumClass = $reflection->getName();
                foreach ($enumClass::cases() as $case) {
                    $caseValue = $case instanceof BackedEnum ? $case->value : $case->name;
                    $icons[$caseValue] = $instance->default;
                }
            }
        }

        return $icons;
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     * @param  class-string<UnitEnum>  $enumClass
     * @return array{labels: array<string,string>, descriptions: array<string,string>, colors: array<string,string>, icons: array<string,string>}
     */
    private static function resolvePerCaseAttributes(ReflectionEnum $reflection, string $enumClass): array
    {
        $labels = [];
        $descriptions = [];
        $colors = [];
        $icons = [];

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

    /**
     * Reset the internal cache reference.
     *
     * Useful in long-lived processes (Octane/Swoole) or tests
     * where EnumCache::resetInstance() has been called and the
     * resolver needs to re-acquire the new instance.
     */
    public static function resetCache(): void
    {
        self::$cache = null;
    }
}
