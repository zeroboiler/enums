<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Production readiness audit — verifies all source files meet strict quality criteria.
 *
 * This test is a meta-audit that inspects the source code structure
 * without executing runtime behavior. It ensures:
 *
 * 1. Every PHP file declares strict_types=1
 * 2. Every class/interface/trait is either final or abstract or a trait
 * 3. Every public method has an explicit return type declaration
 * 4. No public method returns `mixed` (PHPStan level 9 requirement)
 * 5. Every attribute class uses readonly promoted properties
 * 6. License header is present in every file
 * 7. No duplicate class names exist in the source tree
 */
#[CoversNothing]
final class ProductionReadinessV8CompleteAuditTest extends TestCase
{
    /** @var non-empty-list<non-empty-string> */
    private const SRC_DIR = __DIR__.'/../src';

    /**
     * Get all PHP files in the src directory recursively.
     *
     * @return list<non-empty-string>
     */
    private function getSourceFiles(): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::SRC_DIR, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    // -----------------------------------------------------------------------
    // 1. strict_types declaration
    // -----------------------------------------------------------------------

    /**
     * Every PHP source file must declare strict_types=1.
     *
     * This ensures type safety at the language level across the entire codebase.
     */
    public function test_every_source_file_declares_strict_types(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $this->assertNotEmpty(
                $contents,
                "File {$file} is empty."
            );

            $this->assertStringContainsString(
                'declare(strict_types=1)',
                $contents,
                "File {$file} is missing declare(strict_types=1)."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 2. Class final/abstract/trait enforcement
    // -----------------------------------------------------------------------

    /**
     * All classes must be either final, abstract, or traits (no open inheritance).
     *
     * This prevents uncontrolled subclassing and ensures the API surface
     * is well-defined and stable.
     */
    public function test_all_classes_are_final_or_abstract_or_trait(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $tokens = token_get_all((string) file_get_contents($file));
            $className = $this->extractClassName($file, $tokens);
            $type = $this->getClassType($tokens);

            // Skip interfaces — they can't be final
            if ($type === 'interface') {
                continue;
            }

            // Skip traits — they can't be final
            if ($type === 'trait') {
                continue;
            }

            $this->assertNotEmpty(
                $className,
                "File {$file} contains a class without a detectable name."
            );

            $this->assertContains(
                $type,
                ['final', 'abstract'],
                "Class {$className} in {$file} is neither final nor abstract. All classes must be sealed."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 3. Public method return type declarations
    // -----------------------------------------------------------------------

    /**
     * Every public method must have an explicit return type declaration.
     *
     * Ensures PHPStan can infer types without guesswork and
     * callers know what to expect from every public API method.
     */
    public function test_all_public_methods_have_return_types(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            // Use a regex to find public function declarations without return types
            // This is a simplified check — it catches the most common patterns
            if (! preg_match_all(
                '/public\s+static\s+function\s+(\w+)\s*\(/',
                $contents,
                $staticMethods
            )) {
                $staticMethods[1] = [];
            }

            if (! preg_match_all(
                '/public\s+function\s+(\w+)\s*\(/',
                $contents,
                $instanceMethods
            )) {
                $instanceMethods[1] = [];
            }

            $allMethods = array_merge($staticMethods[1], $instanceMethods[1]);

            foreach ($allMethods as $method) {
                // Check that the method has a return type (colon followed by type before opening brace)
                $hasReturnType = preg_match(
                    '/public\s+(static\s+)?function\s+'
                    . preg_quote($method, '/')
                    . '\s*\([^)]*\)\s*:\s*/',
                    $contents
                );

                $this->assertTrue(
                    (bool) $hasReturnType,
                    "Method {$method} in {$file} lacks an explicit return type declaration."
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // 4. No `mixed` return types in public API (PHPStan level 9)
    // -----------------------------------------------------------------------

    /**
     * No public method should use `mixed` as its return type.
     *
     * PHPStan level 9 requires explicit types. `mixed` return types
     * defeat the purpose of static analysis.
     *
     * Note: Some internal/private methods may legitimately use `mixed`
     * for generic casting, but the public API should never expose `mixed`.
     */
    public function test_no_public_method_returns_mixed(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            // Find public methods that return `mixed`
            preg_match_all(
                '/public\s+(static\s+)?function\s+(\w+)\s*\([^)]*\)\s*:\s*mixed\s*({|;)/',
                $contents,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $method = $match[2];
                $this->fail(
                    "Public method {$method} in {$file} returns `mixed`. "
                    . 'Use a specific type for PHPStan level 9 compliance.'
                );
            }

            // If no mixed found, the test passes for this file
            $this->assertTrue(true);
        }
    }

    // -----------------------------------------------------------------------
    // 5. Attribute classes use readonly promoted properties
    // -----------------------------------------------------------------------

    /**
     * All attribute classes (in the Attributes namespace) must use
     * readonly promoted constructor properties.
     *
     * This ensures attribute instances are immutable after construction.
     */
    public function test_attribute_classes_use_readonly_properties(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            if (! str_contains($file, '/Attributes/')) {
                continue;
            }

            $contents = file_get_contents($file);

            // Skip if no class declaration
            if (! str_contains($contents, 'class ')) {
                continue;
            }

            // Check that the constructor has promoted properties (public readonly)
            $this->assertMatchesRegularExpression(
                '/public\s+readonly/',
                $contents,
                "Attribute class in {$file} must use public readonly promoted properties."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 6. License header present
    // -----------------------------------------------------------------------

    /**
     * Every source file must contain the ZeroBoiler license header.
     *
     * This is a legal/compliance requirement for proprietary code.
     */
    public function test_every_source_file_has_license_header(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertStringContainsString(
                'This file is part of ZeroBoiler',
                $contents,
                "File {$file} is missing the ZeroBoiler license header."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 7. No duplicate class names
    // -----------------------------------------------------------------------

    /**
     * No two source files should define the same class name.
     *
     * Duplicate class names cause fatal errors when both are loaded.
     */
    public function test_no_duplicate_class_names(): void
    {
        $files = $this->getSourceFiles();
        $classNames = [];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            // Skip if not a class/interface/trait definition
            if (! preg_match('/\b(?:final|abstract)?\s+(?:class|interface|trait)\s+(\w+)/', $contents, $match)) {
                continue;
            }

            $className = $match[1];

            if (isset($classNames[$className])) {
                $this->fail(
                    "Duplicate class name '{$className}' found in both "
                    . $classNames[$className] . ' and ' . $file
                );
            }

            $classNames[$className] = $file;
        }

        $this->assertTrue(true); // No duplicates found
    }

    // -----------------------------------------------------------------------
    // 8. EnumMetadataResolver is internal (final class)
    // -----------------------------------------------------------------------

    /**
     * Verify EnumMetadataResolver is a final internal class
     * that is not part of the public API.
     */
    public function test_enum_metadata_resolver_is_final_internal(): void
    {
        $file = self::SRC_DIR . '/Support/EnumMetadataResolver.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('final class EnumMetadataResolver', $contents);
        $this->assertStringContainsString('@internal', $contents);
    }

    // -----------------------------------------------------------------------
    // 9. EnumCache singleton pattern integrity
    // -----------------------------------------------------------------------

    /**
     * Verify EnumCache singleton enforces clone/wakeup prevention.
     */
    public function test_enum_cache_singleton_prevents_clone_and_wakeup(): void
    {
        $file = self::SRC_DIR . '/EnumCache.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('final class EnumCache', $contents);
        $this->assertStringContainsString('private static ?self $instance', $contents);
        $this->assertStringContainsString('__clone(): never', $contents);
        $this->assertStringContainsString('__wakeup(): never', $contents);
    }

    // -----------------------------------------------------------------------
    // 10. All attributes have #[Attribute] declaration
    // -----------------------------------------------------------------------

    /**
     * Every class in the Attributes namespace must use the #[Attribute] declaration.
     */
    public function test_attribute_classes_have_attribute_declaration(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            if (! str_contains($file, '/Attributes/')) {
                continue;
            }

            $contents = file_get_contents($file);

            if (! str_contains($contents, 'class ')) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/#\[Attribute/',
                $contents,
                "Attribute class in {$file} must have an #[Attribute] declaration."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 11. EnumManager is readonly final class
    // -----------------------------------------------------------------------

    /**
     * Verify EnumManager uses readonly class modifier (PHP 8.5 feature).
     */
    public function test_enum_manager_is_readonly_final_class(): void
    {
        $file = self::SRC_DIR . '/EnumManager.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('final readonly class EnumManager', $contents);
    }

    // -----------------------------------------------------------------------
    // 12. Facade has correct accessor
    // -----------------------------------------------------------------------

    /**
     * Verify the Enum facade is bound to the correct accessor key.
     */
    public function test_facade_accessor_key(): void
    {
        $file = self::SRC_DIR . '/Facades/Enum.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString("'zeroboiler.enum'", $contents);
        $this->assertStringContainsString('final class Enum extends Facade', $contents);
    }

    // -----------------------------------------------------------------------
    // 13. ServiceProvider registers singleton
    // -----------------------------------------------------------------------

    /**
     * Verify the service provider registers EnumManager as a singleton.
     */
    public function test_service_provider_registers_singleton(): void
    {
        $file = self::SRC_DIR . '/EnumsServiceProvider.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString("'zeroboiler.enum'", $contents);
        $this->assertStringContainsString('singleton', $contents);
        $this->assertStringContainsString('EnumManager', $contents);
        $this->assertStringContainsString('final class EnumsServiceProvider', $contents);
    }

    // -----------------------------------------------------------------------
    // 14. InvalidEnumException has named constructors
    // -----------------------------------------------------------------------

    /**
     * Verify InvalidEnumException provides both named constructors.
     */
    public function test_invalid_enum_exception_has_named_constructors(): void
    {
        $file = self::SRC_DIR . '/Exceptions/InvalidEnumException.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('public static function value(', $contents);
        $this->assertStringContainsString('public static function forName(', $contents);
        $this->assertStringContainsString('final class InvalidEnumException', $contents);
    }

    // -----------------------------------------------------------------------
    // 15. HasEnumMetadata trait has all expected methods
    // -----------------------------------------------------------------------

    /**
     * Verify the trait provides the complete public API.
     */
    public function test_has_enum_metadata_trait_has_complete_api(): void
    {
        $file = self::SRC_DIR . '/Concerns/HasEnumMetadata.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $expectedMethods = [
            'public function label(): string',
            'public function description(): ?string',
            'public function color(): string',
            'public function icon(): ?string',
            'public static function forSelect(): array',
            'public static function forApi(): array',
            'public static function tryFromLabel(string $label): ?static',
            'public static function tryFromName(string $name): ?static',
            'public static function fromName(string $name): static',
            'public static function hasCase(string $name): bool',
            'public function is(self|string $case): bool',
            'public function isNot(self|string $case): bool',
            'public function in(array $cases): bool',
            'public function notIn(array $cases): bool',
            'public static function values(): array',
            'public static function labels(): array',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertStringContainsString(
                $method,
                $contents,
                "HasEnumMetadata trait is missing method signature: {$method}"
            );
        }
    }

    // -----------------------------------------------------------------------
    // 16. EnumRule implements ValidationRule
    // -----------------------------------------------------------------------

    /**
     * Verify EnumRule implements Laravel's ValidationRule interface.
     */
    public function test_enum_rule_implements_validation_rule(): void
    {
        $file = self::SRC_DIR . '/Rules/EnumRule.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('implements ValidationRule', $contents);
        $this->assertStringContainsString('final readonly class EnumRule', $contents);
        $this->assertStringContainsString('public function validate(string $attribute, mixed $value, Closure $fail): void', $contents);
    }

    // -----------------------------------------------------------------------
    // 17. EnumCast implements CastsAttributes
    // -----------------------------------------------------------------------

    /**
     * Verify EnumCast implements Laravel's CastsAttributes interface.
     */
    public function test_enum_cast_implements_casts_attributes(): void
    {
        $file = self::SRC_DIR . '/Casts/EnumCast.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('implements CastsAttributes', $contents);
        $this->assertStringContainsString('final class EnumCast', $contents);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Extract the class name from token array.
     *
     * @param non-empty-string $file
     * @param list<int|string|array{int, string, int}> $tokens
     */
    private function extractClassName(string $file, array $tokens): string
    {
        $foundClass = false;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[1] === 'class' || $token[1] === 'interface' || $token[1] === 'trait') {
                    $foundClass = true;

                    continue;
                }

                if ($foundClass && $token[0] === T_STRING) {
                    return $token[1];
                }
            }
        }

        return '';
    }

    /**
     * Determine if a class is final, abstract, a trait, or an interface.
     *
     * @param list<int|string|array{int, string, int}> $tokens
     * @return string One of: 'final', 'abstract', 'trait', 'interface', 'class'
     */
    private function getClassType(array $tokens): string
    {
        $foundKeyword = false;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[1] === 'final') {
                    return 'final';
                }

                if ($token[1] === 'abstract') {
                    return 'abstract';
                }

                if ($token[1] === 'trait') {
                    return 'trait';
                }

                if ($token[1] === 'interface') {
                    return 'interface';
                }

                if ($token[1] === 'class') {
                    // Found class keyword without final/abstract preceding it
                    // Check if there's a modifier before it
                    return 'class';
                }
            }
        }

        return 'unknown';
    }
}
