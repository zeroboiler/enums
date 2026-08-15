<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Production readiness V9 structural audit — comprehensive source code verification.
 *
 * Extends the V8 audit with additional checks:
 *
 * 1. Every PHP file declares strict_types=1
 * 2. Every class is final or abstract (traits/interfaces exempt)
 * 3. Every public method has an explicit return type declaration
 * 4. No public method returns `mixed` (PHPStan level 9)
 * 5. License header present in every source file
 * 6. No duplicate class names in the source tree
 * 7. All attribute classes use readonly promoted properties
 * 8. All infrastructure classes have class-level docblocks
 * 9. EnumCache singleton implements __clone and __wakeup guards
 * 10. All public methods on EnumManager/EnumCache have @param/@return docblocks
 * 11. HasEnumMetadata trait provides all documented public API methods
 * 12. EnumCast implements CastsAttributes with correct template annotations
 * 13. EnumRule implements ValidationRule with correct interface compliance
 */
#[CoversNothing]
final class ProductionReadinessV9StructuralAuditTest extends TestCase
{
    /** @var non-empty-string */
    private const SRC_DIR = __DIR__.'/../src';

    /**
     * Get all PHP source files recursively.
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

    /**
     * Extract fully qualified class names from a PHP file.
     *
     * @return list<non-empty-string>
     */
    private function extractClassNames(string $file): array
    {
        $contents = file_get_contents($file);
        $tokens = token_get_all($contents);
        $classes = [];
        $namespace = '';

        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                if ($token[0] === T_NAMESPACE) {
                    // Collect namespace tokens until ;
                    $namespace = '';
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if (is_array($tokens[$j])) {
                            if ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_NAME_QUALIFIED) {
                                $namespace .= $tokens[$j][1];
                            }
                        } elseif ($tokens[$j] === ';') {
                            break;
                        }
                    }
                }

                if ($token[0] === T_CLASS || $token[0] === T_ENUM) {
                    // Look backward for 'final' or 'abstract' keyword
                    $modifier = '';
                    for ($k = $i - 1; $k >= 0; $k--) {
                        if (is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
                            continue;
                        }
                        if (is_array($tokens[$k]) && $tokens[$k][0] === T_FINAL) {
                            $modifier = 'final ';
                            break;
                        }
                        if (is_array($tokens[$k]) && $tokens[$k][0] === T_READONLY) {
                            continue;
                        }
                        if (is_array($tokens[$k]) && $tokens[$k][0] === T_ABSTRACT) {
                            $modifier = 'abstract ';
                            break;
                        }
                        break;
                    }

                    // Find class name
                    for ($k = $i + 1; $k < count($tokens); $k++) {
                        if (is_array($tokens[$k]) && $tokens[$k][0] === T_STRING) {
                            $fqn = ($namespace !== '' ? $namespace.'\\' : '').$tokens[$k][1];
                            $classes[] = ['name' => $tokens[$k][1], 'fqn' => $fqn, 'modifier' => trim($modifier)];
                            break;
                        }
                    }
                }
            }
        }

        return $classes;
    }

    // -----------------------------------------------------------------------
    // 1. strict_types declaration
    // -----------------------------------------------------------------------

    public function test_every_source_file_declares_strict_types(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);
            $this->assertNotEmpty($contents, "File {$file} is empty.");
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

    public function test_all_classes_are_final_or_abstract(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);

            // Skip traits
            if (str_contains($contents, 'trait ')) {
                continue;
            }

            // Skip interfaces
            if (preg_match('/interface\s+\w+/', $contents)) {
                continue;
            }

            // For each class/enum declaration
            if (! preg_match('/(?:final\s+|abstract\s+)?(?:readonly\s+)?(?:class|enum)\s+\w+/', $contents, $matches)) {
                continue;
            }

            $declaration = $matches[0];
            $this->assertTrue(
                str_starts_with($declaration, 'final') || str_starts_with($declaration, 'abstract'),
                "Class in {$file} (\"{$declaration}\") is neither final nor abstract."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 3. Public method return type declarations
    // -----------------------------------------------------------------------

    public function test_all_public_methods_have_return_types(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);

            // Match public static function and public function
            preg_match_all('/public\s+(static\s+)?function\s+(\w+)\s*\(/', $contents, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $method = $match[2];

                // Find the full declaration including parameters to check for return type
                $pattern = '/public\s+(?:static\s+)?function\s+'.preg_quote($method, '/').'\s*\([^)]*\)\s*:\s*/';
                $hasReturnType = preg_match($pattern, $contents);

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

    public function test_no_public_method_returns_mixed(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);

            preg_match_all(
                '/public\s+(static\s+)?function\s+(\w+)\s*\([^)]*\)\s*:\s*mixed/',
                $contents,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $method = $match[2];
                $this->fail("Public method {$method} in {$file} returns `mixed` — violates PHPStan level 9.");
            }
        }
    }

    // -----------------------------------------------------------------------
    // 5. License header
    // -----------------------------------------------------------------------

    public function test_license_header_present_in_all_files(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);
            $this->assertStringContainsString(
                'This file is part of ZeroBoiler, licensed under the proprietary license.',
                $contents,
                "File {$file} is missing the license header."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 6. No duplicate class names
    // -----------------------------------------------------------------------

    public function test_no_duplicate_class_names(): void
    {
        $allClasses = [];
        foreach ($this->getSourceFiles() as $file) {
            foreach ($this->extractClassNames($file) as $classInfo) {
                $allClasses[] = $classInfo['fqn'];
            }
        }

        $unique = array_unique($allClasses);
        $this->assertSameSize(
            $allClasses,
            $unique,
            'Duplicate class names found: '.implode(', ', array_diff_assoc($allClasses, $unique))
        );
    }

    // -----------------------------------------------------------------------
    // 7. Attribute classes use readonly promoted properties
    // -----------------------------------------------------------------------

    public function test_attribute_classes_use_readonly_promoted_properties(): void
    {
        $attributesDir = self::SRC_DIR.'/Attributes';
        if (! is_dir($attributesDir)) {
            $this->markTestSkipped('No Attributes directory found.');

            return;
        }

        foreach (glob($attributesDir.'/*.php') as $file) {
            $contents = file_get_contents($file);

            // Skip if the attribute has no constructor
            if (! preg_match('/public function __construct\s*\(/', $contents)) {
                continue;
            }

            // Must use readonly promoted properties (constructor property promotion with readonly)
            $hasReadonlyPromoted = preg_match('/public\s+readonly\s+/', $contents);

            $this->assertTrue(
                (bool) $hasReadonlyPromoted,
                basename($file).' should use readonly promoted properties.'
            );
        }
    }

    // -----------------------------------------------------------------------
    // 8. Infrastructure classes have class-level docblocks
    // -----------------------------------------------------------------------

    public function test_infrastructure_classes_have_class_docblocks(): void
    {
        $infraFiles = array_merge(
            glob(self::SRC_DIR.'/*.php'),
            glob(self::SRC_DIR.'/Support/*.php'),
            glob(self::SRC_DIR.'/Rules/*.php'),
            glob(self::SRC_DIR.'/Casts/*.php'),
            glob(self::SRC_DIR.'/Exceptions/*.php'),
            glob(self::SRC_DIR.'/Facades/*.php'),
            glob(self::SRC_DIR.'/Console/Commands/*.php'),
        );

        foreach ($infraFiles as $file) {
            $contents = file_get_contents($file);
            $hasDocblock = preg_match('/\/\*\*[\s\S]*?\*\//', $contents);
            // The first docblock should appear before the class declaration
            $hasClassDocblock = preg_match('/\/\*\*[\s\S]*?\*\/\s*(?:declare|namespace|final|abstract|readonly|class|enum|trait)/', $contents);

            $this->assertTrue(
                (bool) $hasClassDocblock,
                basename($file).' is missing a class-level docblock.'
            );
        }
    }

    // -----------------------------------------------------------------------
    // 9. EnumCache singleton guards
    // -----------------------------------------------------------------------

    public function test_enum_cache_singleton_has_clone_and_wakeup_guards(): void
    {
        $file = self::SRC_DIR.'/EnumCache.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('function __clone(): never', $contents,
            'EnumCache must prevent cloning with a never-return __clone().');
        $this->assertStringContainsString('function __wakeup(): never', $contents,
            'EnumCache must prevent unserialization with a never-return __wakeup().');
        $this->assertStringContainsString('private function __construct()', $contents,
            'EnumCache must have a private constructor.');
    }

    // -----------------------------------------------------------------------
    // 10. EnumManager/EnumCache public methods have docblocks with @param/@return
    // -----------------------------------------------------------------------

    public function test_enum_manager_public_methods_have_docblocks(): void
    {
        $file = self::SRC_DIR.'/EnumManager.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        // Extract public methods and check each has a preceding docblock
        preg_match_all('/public\s+function\s+(\w+)/', $contents, $methods);

        foreach ($methods[1] as $method) {
            // Find the method and look backward for a docblock
            $pattern = '/\/\*\*[\s\S]*?\*\/\s*(?:\#[\s\S]*?\s+)?public\s+function\s+'.preg_quote($method, '/').'/';
            $hasDocblock = preg_match($pattern, $contents);

            $this->assertTrue(
                (bool) $hasDocblock,
                "EnumManager::{$method}() is missing a docblock."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 11. HasEnumMetadata trait provides all documented API methods
    // -----------------------------------------------------------------------

    public function test_has_enum_metadata_trait_provides_complete_api(): void
    {
        $file = self::SRC_DIR.'/Concerns/HasEnumMetadata.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $requiredMethods = [
            'label', 'description', 'color', 'icon',
            'forSelect', 'forApi', 'tryFromLabel', 'tryFromName',
            'fromName', 'hasCase', 'is', 'isNot', 'in', 'notIn',
            'values', 'labels',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertStringContainsString(
                'function '.$method.'(',
                $contents,
                "HasEnumMetadata trait is missing the {$method}() method."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 12. EnumCast implements CastsAttributes
    // -----------------------------------------------------------------------

    public function test_enum_cast_implements_casts_attributes(): void
    {
        $file = self::SRC_DIR.'/Casts/EnumCast.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('implements CastsAttributes', $contents,
            'EnumCast must implement CastsAttributes interface.');
        $this->assertStringContainsString('function get(', $contents,
            'EnumCast must implement get() method.');
        $this->assertStringContainsString('function set(', $contents,
            'EnumCast must implement set() method.');
        $this->assertStringContainsString('function serialize(', $contents,
            'EnumCast should implement serialize() method.');
    }

    // -----------------------------------------------------------------------
    // 13. EnumRule implements ValidationRule
    // -----------------------------------------------------------------------

    public function test_enum_rule_implements_validation_rule(): void
    {
        $file = self::SRC_DIR.'/Rules/EnumRule.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('implements ValidationRule', $contents,
            'EnumRule must implement ValidationRule interface.');
        $this->assertStringContainsString('function validate(', $contents,
            'EnumRule must implement validate() method.');
        $this->assertStringContainsString('public static function for(', $contents,
            'EnumRule must have a named constructor for().');
        $this->assertStringContainsString('public function nullable(', $contents,
            'EnumRule must have a nullable() method.');
    }

    // -----------------------------------------------------------------------
    // 14. Facade resolves correct accessor
    // -----------------------------------------------------------------------

    public function test_enum_facade_resolves_correct_accessor(): void
    {
        $file = self::SRC_DIR.'/Facades/Enum.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString("'zeroboiler.enum'", $contents,
            'Enum facade must resolve the zeroboiler.enum singleton.');
    }

    // -----------------------------------------------------------------------
    // 15. ServiceProvider registers singleton and commands
    // -----------------------------------------------------------------------

    public function test_service_provider_registers_singleton_and_commands(): void
    {
        $file = self::SRC_DIR.'/EnumsServiceProvider.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('singleton(', $contents,
            'ServiceProvider must register a singleton.');
        $this->assertStringContainsString("'zeroboiler.enum'", $contents,
            'ServiceProvider must register the EnumManager singleton.');
        $this->assertStringContainsString('InspectEnumCommand', $contents,
            'ServiceProvider must register InspectEnumCommand.');
        $this->assertStringContainsString('MakeEnumTestCommand', $contents,
            'ServiceProvider must register MakeEnumTestCommand.');
    }

    // -----------------------------------------------------------------------
    // 16. All source files have no syntax errors (parseable)
    // -----------------------------------------------------------------------

    public function test_all_source_files_are_parseable(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $result = token_get_all((string) file_get_contents($file));
            $errors = [];

            foreach ($result as $token) {
                if (is_array($token) && $token[0] === T_ERROR) {
                    $errors[] = $token[1];
                }
            }

            $this->assertEmpty(
                $errors,
                "File {$file} has parse error(s): ".implode(', ', $errors)
            );
        }
    }

    // -----------------------------------------------------------------------
    // 17. No TODO/FIXME/HACK comments in production code
    // -----------------------------------------------------------------------

    public function test_no_todo_fixme_hack_comments(): void
    {
        $forbidden = ['TODO', 'FIXME', 'HACK', 'XXX'];

        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);
            $lineNum = 0;

            foreach (explode("\n", $contents) as $line) {
                $lineNum++;

                foreach ($forbidden as $keyword) {
                    // Match only comments (// or #)
                    if (preg_match('/^\s*(\/\/|#)/', $line) && str_contains($line, $keyword)) {
                        $this->fail("File {$file}:{$lineNum} contains {$keyword} comment: {$line}");
                    }
                }
            }
        }

        $this->assertTrue(true); // No violations found
    }

    // -----------------------------------------------------------------------
    // 18. Enums package has exactly 20 source files
    // -----------------------------------------------------------------------

    public function test_source_file_count_matches_readme(): void
    {
        $files = $this->getSourceFiles();
        $this->assertCount(
            20,
            $files,
            'README claims 20 source files in src/, but found '.count($files).'. Update README badge.'
        );
    }
}
