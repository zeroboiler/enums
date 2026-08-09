<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Rules;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use UnitEnum;

/**
 * Universal enum validation rule.
 *
 * Usage in a Form Request / DTO:
 *
 *   'status' => ['required', EnumRule::for(UserStatus::class)],
 *   'status' => ['nullable', EnumRule::for(UserStatus::class)], // optional field
 *
 * Or inline:
 *
 *   Rule::enum(UserStatus::class)  // Laravel built-in (also works)
 *
 * ZeroBoiler version provides better error messages and works with metadata.
 *
 * Implements Laravel's {@see \Illuminate\Contracts\Validation\ValidationRule} interface,
 * making it usable anywhere Laravel rules are accepted (Form Requests, manual validation, etc.).
 *
 * Supports both backed enums (validates against backed values) and pure enums (validates
 * against case names).
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata For the trait that provides metadata API
 * @see \ZeroBoiler\Enums\Facades\Enum For the facade that delegates to EnumManager
 */
final readonly class EnumRule implements ValidationRule
{
    /**
     * @param  class-string<UnitEnum>  $enumClass  The enum class to validate against (backed or pure)
     * @param  bool  $nullable  When true, null values pass validation without error
     */
    public function __construct(
        private readonly string $enumClass,
        private readonly bool $nullable = false,
    ) {}

    /**
     * Named constructor for readability.
     *
     * @param  class-string<UnitEnum>  $enumClass
     */
    public static function for(string $enumClass): self
    {
        return new self($enumClass);
    }

    /**
     * Create a nullable instance of this rule.
     */
    public function nullable(): self
    {
        return new self($this->enumClass, true);
    }

    /**
     * Validate the attribute value against the enum class.
     *
     * For backed enums: validates that the value matches a valid backed value.
     * For pure enums: validates that the value string matches a case name.
     * Null values are rejected unless the nullable flag is set.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     *
     * @throws \ReflectionException If the enum class does not exist or is not an enum
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow null for optional fields when nullable is enabled
        if ($value === null) {
            if (! $this->nullable) {
                $fail($this->message($attribute));
            }

            return;
        }

        /** @var class-string<UnitEnum> $enumClass */
        $enumClass = $this->enumClass;

        if (is_a($enumClass, BackedEnum::class, true)) {
            /** @var class-string<BackedEnum> $enumClass */
            // Determine the accepted scalar type from the enum's backing type
            // to avoid TypeError from PHP's strict tryFrom() (e.g. passing
            // a string to an int-backed enum).
            $backingType = (new \ReflectionEnum($enumClass))->getBackingType();

            if ($backingType === null) {
                $fail($this->message($attribute));

                return;
            }

            $backingTypeName = $backingType->getName();

            // Reject value if its PHP type doesn't match the enum's backing type
            if (($backingTypeName === 'int' && ! is_int($value))
                || ($backingTypeName === 'string' && ! is_string($value))
            ) {
                $fail($this->message($attribute));

                return;
            }

            /** @var int|string $value */
            if ($enumClass::tryFrom($value) === null) {
                $fail($this->message($attribute));
            }
        } elseif (enum_exists($enumClass)) {
            // For pure enums, match by case name
            if (! is_string($value)) {
                $fail($this->message($attribute));

                return;
            }

            /** @var list<string> $validNames */
            $validNames = array_map(static fn (UnitEnum $case): string => $case->name, $enumClass::cases());
            if (! in_array($value, $validNames, true)) {
                $fail($this->message($attribute));
            }
        } else {
            $fail("The {$attribute} field must be a valid enum.");
        }
    }

    /**
     * Generate a descriptive error message.
     */
    private function message(string $attribute): string
    {
        /** @var class-string<UnitEnum> $enumClass */
        $enumClass = $this->enumClass;

        // Check if enum uses HasEnumMetadata
        if (method_exists($enumClass, 'values')) {
            /** @var list<string|int> $values */
            $values = $enumClass::values();
            $allowed = implode(', ', array_map(static fn (int|string $v): string => (string) $v, $values));

            return "The selected {$attribute} is invalid. Allowed values: {$allowed}.";
        }

        return "The selected {$attribute} is invalid.";
    }
}
