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
        self::resolvePerCaseAttributes($reflection, $enumClass, $metadata);

        $cache->set($enumClass, $metadata);

        return $metadata;
    }

    /**
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
     * @return array<string, string>
     */
    private static function resolveClassIcons(ReflectionEnum $reflection): array
    {
        $icons = [];
        foreach ($reflection->getAttributes() as $attr) {
            $instance = $attr->newInstance();
            if ($instance instanceof EnumIcon && $instance->default) {
                /** @var UnitEnum $enumClass */
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
     * @param  array{labels: array<string,string>, descriptions: array<string,string>, colors: array<string,string>, icons: array<string,string>}  $metadata
     */
    private static function resolvePerCaseAttributes(ReflectionEnum $reflection, string $enumClass, array &$metadata): void
    {
        /** @var UnitEnum $enumClass */
        foreach ($enumClass::cases() as $case) {
            $caseReflection = $reflection->getCase($case->name);
            $value = $case instanceof BackedEnum ? $case->value : $case->name;

            foreach ($caseReflection->getAttributes() as $attr) {
                $instance = $attr->newInstance();

                if ($instance instanceof Label) {
                    $metadata['labels'][$value] = $instance->value;
                }

                if ($instance instanceof Description) {
                    $metadata['descriptions'][$value] = $instance->value;
                }

                if ($instance instanceof Color) {
                    $metadata['colors'][$value] = $instance->value;
                }

                if ($instance instanceof Icon) {
                    $metadata['icons'][$value] = $instance->value;
                }
            }
        }
    }

    private static function getCache(): EnumCache
    {
        if (! self::$cache instanceof EnumCache) {
            self::$cache = EnumCache::getInstance();
        }

        return self::$cache;
    }
}
