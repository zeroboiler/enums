<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Rules;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use UnitEnum;

/**
 * Universal enum validation rule.
 *
 * Usage in a Form Request / DTO:
 *
 *   'status' => ['required', EnumRule::for(UserStatus::class)],
 *
 * Or inline:
 *
 *   Rule::enum(UserStatus::class)  // Laravel built-in (also works)
 *
 * NovaForge version provides better error messages and works with metadata.
 */
final class EnumRule implements ValidationRule
{
    /** @var class-string<\BackedEnum> */
    private string $enumClass;

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     */
    public function __construct(string $enumClass)
    {
        $this->enumClass = $enumClass;
    }

    /**
     * Named constructor for readability.
     *
     * @param  class-string<\BackedEnum>  $enumClass
     */
    public static function for(string $enumClass): self
    {
        return new self($enumClass);
    }

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var class-string<\BackedEnum> $enumClass */
        $enumClass = $this->enumClass;

        if (!is_string($value) && !is_int($value)) {
            $fail($this->message($attribute, $value));
            return;
        }

        if ($enumClass::tryFrom($value) === null) {
            $fail($this->message($attribute, $value));
        }
    }

    /**
     * Generate a descriptive error message.
     */
    private function message(string $attribute, mixed $value): string
    {
        /** @var class-string<\BackedEnum> $enumClass */
        $enumClass = $this->enumClass;

        // Check if enum uses HasEnumMetadata
        if (method_exists($enumClass, 'values')) {
            $allowed = implode(', ', $enumClass::values());

            return "The selected {$attribute} is invalid. Allowed values: {$allowed}.";
        }

        return "The selected {$attribute} is invalid.";
    }
}
