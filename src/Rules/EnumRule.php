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
use InvalidArgumentException;
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
 * NovaForge version provides better error messages and works with metadata.
 */
final readonly class EnumRule implements ValidationRule
{
    /**
     * @param  class-string<UnitEnum>  $enumClass
     * @param  bool  $nullable  When true, null values pass validation.
     *
     * @throws InvalidArgumentException If the class does not exist or is not a UnitEnum.
     */
    public function __construct(
        private string $enumClass,
        private bool $nullable = false,
    ) {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException("Class [{$enumClass}] is not a valid enum.");
        }

        if (! is_subclass_of($enumClass, UnitEnum::class)) {
            throw new InvalidArgumentException("Class [{$enumClass}] must be a UnitEnum or BackedEnum.");
        }
    }

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
     * @param  Closure(string, string|null=):PotentiallyTranslatedString  $fail
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
            if ((! is_string($value) && ! is_int($value)) || ! $enumClass::tryFrom($value) instanceof BackedEnum) {
                $fail($this->message($attribute));
            }
        } elseif (is_a($enumClass, UnitEnum::class, true)) {
            // For pure enums, match by case name
            if (! is_string($value)) {
                $fail($this->message($attribute));

                return;
            }

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
