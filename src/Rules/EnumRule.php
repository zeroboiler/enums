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
 */
final readonly class EnumRule implements ValidationRule
{
    /**
     * @param  class-string<UnitEnum>  $enumClass
     * @param  bool  $nullable  When true, null values pass validation.
     */
    public function __construct(
        private string $enumClass,
        private bool $nullable = false,
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
     */
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
            if (! is_string($value) && ! is_int($value)) {
                $fail($this->message($attribute));

                return;
            }

            /** @var class-string<BackedEnum> $enumClass */
            if (! $enumClass::tryFrom($value) instanceof BackedEnum) {
                $fail($this->message($attribute));
            }
        } elseif (is_a($enumClass, UnitEnum::class, true)) {
            // For pure enums, match by case name
            if (! is_string($value)) {
                $fail($this->message($attribute));

                return;
            }

            /** @var list<string> $validNames */
            $validNames = array_map(fn (UnitEnum $case): string => $case->name, $enumClass::cases());
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
            $allowed = implode(', ', $enumClass::values());

            return "The selected {$attribute} is invalid. Allowed values: {$allowed}.";
        }

        return "The selected {$attribute} is invalid.";
    }
}
