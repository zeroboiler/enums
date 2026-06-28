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
final readonly class EnumRule implements ValidationRule
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     */
    public function __construct(private string $enumClass) {}

    /**
     * Named constructor for readability.
     *
     * @param  class-string<BackedEnum>  $enumClass
     */
    public static function for(string $enumClass): self
    {
        return new self($enumClass);
    }

    /**
     * @param  Closure(string, string|null=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var class-string<BackedEnum> $enumClass */
        $enumClass = $this->enumClass;

        if (! is_string($value) && ! is_int($value)) {
            $fail($this->message($attribute));

            return;
        }

        if ($enumClass::tryFrom($value) === null) {
            $fail($this->message($attribute));
        }
    }

    /**
     * Generate a descriptive error message.
     */
    private function message(string $attribute): string
    {
        /** @var class-string<BackedEnum> $enumClass */
        $enumClass = $this->enumClass;

        // Check if enum uses HasEnumMetadata
        if (method_exists($enumClass, 'values')) {
            $allowed = implode(', ', $enumClass::values());

            return "The selected {$attribute} is invalid. Allowed values: {$allowed}.";
        }

        return "The selected {$attribute} is invalid.";
    }
}
