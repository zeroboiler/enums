<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Concerns;

use BackedEnum;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ReflectionEnum;
use UnitEnum;

/**
 * Smart enum trait — zero boilerplate metadata, serialization and helpers.
 *
 * Provides:
 * - label():        Human-readable label (auto-generated or via attribute)
 * - description():  Optional description (via attribute)
 * - color():        UI color token (via attribute)
 * - icon():         UI icon name (via attribute)
 * - forSelect():    Array for <select> dropdowns
 * - forApi():       Array for JSON API responses
 * - tryFromLabel(): Reverse lookup from label
 * - cases():        (inherited from native enum)
 *
 * Usage:
 *   #[EnumColor(success: ['active'], danger: ['banned', 'suspended'])]
 *   enum UserStatus: string
 *   {
 *       use HasEnumMetadata;
 *
 *       #[Label('Active User')]
 *       #[Icon('heroicon-o-check-circle')]
 *       case ACTIVE = 'active';
 *
 *       case INACTIVE = 'inactive';
 *       case SUSPENDED = 'suspended';
 *
 *       #[Label('Banned')]
 *       case BANNED = 'banned';
 *   }
 *
 *   UserStatus::ACTIVE->label();       // "Active User"
 *   UserStatus::INACTIVE->label();     // "Inactive" (auto-generated)
 *   UserStatus::ACTIVE->color();       // "success"
 *   UserStatus::BANNED->color();       // "danger"
 *   UserStatus::ACTIVE->icon();        // "heroicon-o-check-circle"
 *   UserStatus::forSelect();           // [['value'=>'active','label'=>'Active User'], ...]
 *   UserStatus::forApi();              // [['value'=>'active','label'=>'Active User','color'=>'success','icon'=>'...'], ...]
 */
trait HasEnumMetadata
{
    /**
     * Cache for reflection and metadata lookups (per enum class).
     *
     * @var array<string, array{
     *     labels: array<string, string>,
     *     descriptions: array<string, string>,
     *     colors: array<string, string>,
     *     icons: array<string, string>
     * }>
     */
    private static array $_metadataCache = [];

    /**
     * Get the human-readable label for this case.
     *
     * Priority:
     * 1. Per-case #[Label] attribute
     * 2. Class-level #[EnumLabel] mapping
     * 3. Auto-generated from case name (SCREAMING_SNAKE → Title Case)
     */
    public function label(): string
    {
        $meta = $this->resolveMetadata();

        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['labels'][$value]
            ?? $meta['labels'][$this->name]
            ?? $this->generateLabel();
    }

    /**
     * Get the optional description for this case.
     */
    public function description(): ?string
    {
        $meta = $this->resolveMetadata();

        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['descriptions'][$value]
            ?? $meta['descriptions'][$this->name]
            ?? null;
    }

    /**
     * Get the UI color token for this case.
     *
     * Priority:
     * 1. Per-case #[Color] attribute
     * 2. Class-level #[EnumColor] mapping
     * 3. Default: 'secondary'
     */
    public function color(): string
    {
        $meta = $this->resolveMetadata();

        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['colors'][$value]
            ?? $meta['colors'][$this->name]
            ?? 'secondary';
    }

    /**
     * Get the UI icon name for this case.
     */
    public function icon(): ?string
    {
        $meta = $this->resolveMetadata();

        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        return $meta['icons'][$value]
            ?? $meta['icons'][$this->name]
            ?? null;
    }

    /**
     * Generate an array suitable for <select> dropdowns.
     *
     * @return list<array{value: string|int, label: string}>
     */
    public static function forSelect(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $value = $case instanceof BackedEnum ? $case->value : $case->name;
            $result[] = [
                'value' => $value,
                'label' => $case->label(),
            ];
        }

        return $result;
    }

    /**
     * Generate a rich array for API JSON responses.
     *
     * @return list<array{value: string|int, name: string, label: string, description: ?string, color: string, icon: ?string}>
     */
    public static function forApi(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $value = $case instanceof BackedEnum ? $case->value : $case->name;
            $result[] = [
                'value'       => $value,
                'name'        => $case->name,
                'label'       => $case->label(),
                'description' => $case->description(),
                'color'       => $case->color(),
                'icon'        => $case->icon(),
            ];
        }

        return $result;
    }

    /**
     * Reverse lookup: find an enum case from its label.
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
     * Get all values as a simple array.
     *
     * @return list<string|int>
     */
    public static function values(): array
    {
        return array_map(
            static fn(self $case) => $case instanceof BackedEnum ? $case->value : $case->name,
            self::cases(),
        );
    }

    /**
     * Get all labels as a simple array.
     *
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_map(static fn(self $case) => $case->label(), self::cases());
    }

    /**
     * Generate a human-readable label from the case name.
     * SCREAMING_SNAKE_CASE → Title Case
     * camelCase → Title Case
     */
    private function generateLabel(): string
    {
        $name = $this->name;

        // Convert SCREAMING_SNAKE_CASE to Title Case
        $label = str_replace('_', ' ', strtolower($name));

        // Convert camelCase to Title Case (if no underscores were found)
        if ($label === strtolower($name)) {
            $label = preg_replace('/(?<!^)([A-Z])/', ' $1', $name) ?? $name;
            $label = strtolower($label);
        }

        return ucwords(trim($label));
    }

    /**
     * Resolve all metadata for this enum from attributes.
     * Results are cached per enum class.
     *
     * @return array{labels: array<string,string>, descriptions: array<string,string>, colors: array<string,string>, icons: array<string,string>}
     */
    private function resolveMetadata(): array
    {
        $enumClass = static::class;

        if (isset(self::$_metadataCache[$enumClass])) {
            return self::$_metadataCache[$enumClass];
        }

        $reflection = new ReflectionEnum($enumClass);
        $value = $this instanceof BackedEnum ? $this->value : $this->name;

        $labels       = [];
        $descriptions = [];
        $colors       = [];
        $icons        = [];

        // Parse class-level attributes
        foreach ($reflection->getAttributes() as $attr) {
            $instance = $attr->newInstance();

            // EnumLabel
            if ($instance instanceof EnumLabel && $instance->labels) {
                $labels = $instance->labels;
            }

            // EnumDescription
            if ($instance instanceof EnumDescription && $instance->descriptions) {
                $descriptions = $instance->descriptions;
            }

            // EnumColor
            if ($instance instanceof EnumColor) {
                foreach (['success', 'danger', 'warning', 'info', 'secondary'] as $color) {
                    foreach ($instance->$color as $caseValue) {
                        $colors[$caseValue] = $color;
                    }
                }
            }

            // EnumIcon
            if ($instance instanceof EnumIcon && $instance->default) {
                // Apply default icon to all cases
                foreach (self::cases() as $case) {
                    $caseValue = $case instanceof BackedEnum ? $case->value : $case->name;
                    $icons[$caseValue] = $instance->default;
                }
            }
        }

        // Parse per-case attributes (override class-level)
        $caseReflection = $reflection->getCase($this->name);

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

        return self::$_metadataCache[$enumClass] = [
            'labels'       => $labels,
            'descriptions' => $descriptions,
            'colors'       => $colors,
            'icons'        => $icons,
        ];
    }
}
