<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use BackedEnum;
use ReflectionClass;
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
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

describe('Enums — Production Final Audit (Source Code Quality)', function () {
    // -----------------------------------------------------------------------
    // 1. Strict types in every source file
    // -----------------------------------------------------------------------
    it('all source files have declare(strict_types=1)', function () {
        $srcDir = dirname(__DIR__, 2) . '/src';
        $files = glob_recursive($srcDir, '*.php');
        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            expect($content)
                ->toContain('declare(strict_types=1)')
                ->and()
                ->not->toContain('declare(strict_types=0)');
        }
    });

    // -----------------------------------------------------------------------
    // 2. All classes are final (except trait)
    // -----------------------------------------------------------------------
    it('all classes and attributes are final', function () {
        $srcDir = dirname(__DIR__, 2) . '/src';
        $files = glob_recursive($srcDir, '*.php');

        $nonFinalClasses = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            // Skip trait files
            if (str_contains($content, 'trait ')) {
                continue;
            }
            if (preg_match('/\b(?:final\s+)?(?:abstract\s+)?class\s+(\w+)/', $content, $m)) {
                if (! str_contains($content, 'final class ')) {
                    $nonFinalClasses[] = $m[1];
                }
            }
        }

        expect($nonFinalClasses)->toBeEmpty(
            'Non-final classes found: ' . implode(', ', $nonFinalClasses)
        );
    });

    // -----------------------------------------------------------------------
    // 3. All attribute classes use readonly promoted properties
    // -----------------------------------------------------------------------
    it('all attribute classes have readonly promoted properties', function () {
        $attributeClasses = [
            \ZeroBoiler\Enums\Attributes\Label::class,
            \ZeroBoiler\Enums\Attributes\Color::class,
            \ZeroBoiler\Enums\Attributes\Icon::class,
            \ZeroBoiler\Enums\Attributes\Description::class,
            \ZeroBoiler\Enums\Attributes\EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\EnumColor::class,
            \ZeroBoiler\Enums\Attributes\EnumIcon::class,
            \ZeroBoiler\Enums\Attributes\EnumDescription::class,
        ];

        foreach ($attributeClasses as $class) {
            $ref = new ReflectionClass($class);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                expect($prop->isReadOnly())
                    ->toBeTrue("{$class}::\${$prop->getName()} must be readonly");
            }
        }
    });

    // -----------------------------------------------------------------------
    // 4. All public methods have return type declarations
    // -----------------------------------------------------------------------
    it('all public methods have explicit return type declarations', function () {
        $srcDir = dirname(__DIR__, 2) . '/src';
        $files = glob_recursive($srcDir, '*.php');

        $missingReturnTypes = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            // Match public function declarations without return types
            // Pattern: public static function name( — must have : type before {
            if (preg_match_all(
                '/public\s+(?:static\s+)?function\s+(\w+)\s*\([^)]*\)(?!\s*:\s*\w)/',
                $content,
                $matches,
                PREG_SET_ORDER
            )) {
                foreach ($matches as $match) {
                    $methodName = $match[1];
                    // __construct is allowed to not have return type
                    if ($methodName === '__construct' || $methodName === '__clone' || $methodName === '__wakeup') {
                        continue;
                    }
                    $missingReturnTypes[] = basename($file) . '::' . $methodName . '()';
                }
            }
        }

        expect($missingReturnTypes)->toBeEmpty(
            'Missing return types: ' . implode(', ', $missingReturnTypes)
        );
    });

    // -----------------------------------------------------------------------
    // 5. HasEnumMetadata trait provides complete API
    // -----------------------------------------------------------------------
    it('HasEnumMetadata trait provides all documented methods', function () {
        $ref = new ReflectionClass(HasEnumMetadata::class);
        $methods = array_map(
            static fn (\ReflectionMethod $m): string => $m->getName(),
            $ref->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $expected = [
            'label', 'description', 'color', 'icon',
            'forSelect', 'forApi',
            'tryFromLabel', 'tryFromName', 'fromName', 'hasCase',
            'is', 'isNot', 'in',
            'values', 'labels',
        ];

        foreach ($expected as $method) {
            expect(in_array($method, $methods, true))
                ->toBeTrue("Missing method: {$method}");
        }
    });

    // -----------------------------------------------------------------------
    // 6. EnumRule implements ValidationRule with correct methods
    // -----------------------------------------------------------------------
    it('EnumRule implements ValidationRule and has validate method', function () {
        $implements = class_implements(EnumRule::class) ?: [];
        expect($implements)->toContain(\Illuminate\Contracts\Validation\ValidationRule::class);

        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->hasMethod('validate'))->toBeTrue();
        expect($ref->hasMethod('for'))->toBeTrue();
        expect($ref->hasMethod('nullable'))->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // 7. EnumCast implements CastsAttributes with correct methods
    // -----------------------------------------------------------------------
    it('EnumCast implements CastsAttributes with correct methods', function () {
        $implements = class_implements(EnumCast::class) ?: [];
        expect($implements)->toContain(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class);

        $ref = new ReflectionClass(EnumCast::class);
        expect($ref->hasMethod('get'))->toBeTrue();
        expect($ref->hasMethod('set'))->toBeTrue();
        expect($ref->hasMethod('serialize'))->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // 8. EnumCache singleton lifecycle
    // -----------------------------------------------------------------------
    it('EnumCache is a proper singleton', function () {
        $reset = EnumCache::resetInstance();

        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();
        expect($a)->toBe($b);

        EnumCache::resetInstance();
        $c = EnumCache::getInstance();
        expect($c)->not->toBe($a);
    });

    // -----------------------------------------------------------------------
    // 9. EnumManager methods match facade @method annotations
    // -----------------------------------------------------------------------
    it('EnumManager public methods exist on Enum facade', function () {
        $facadeRef = new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);
        $doc = $facadeRef->getDocComment();

        expect($doc)->not->toBeFalse();

        // Facade docblock should mention forSelect, forApi, tryFromLabel
        expect($doc)->toContain('forSelect');
        expect($doc)->toContain('forApi');
        expect($doc)->toContain('tryFromLabel');

        // EnumManager should have these methods
        $managerRef = new ReflectionClass(EnumManager::class);
        expect($managerRef->hasMethod('forSelect'))->toBeTrue();
        expect($managerRef->hasMethod('forApi'))->toBeTrue();
        expect($managerRef->hasMethod('tryFromLabel'))->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // 10. InvalidEnumException has named constructors
    // -----------------------------------------------------------------------
    it('InvalidEnumException has value() and forName() named constructors', function () {
        $ref = new ReflectionClass(InvalidEnumException::class);

        expect($ref->hasMethod('value'))->toBeTrue();
        expect($ref->getMethod('value')->isStatic())->toBeTrue();

        expect($ref->hasMethod('forName'))->toBeTrue();
        expect($ref->getMethod('forName')->isStatic())->toBeTrue();

        // Test factory methods
        $e = InvalidEnumException::forName('UserStatus', 'UNKNOWN');
        expect($e)->toBeInstanceOf(InvalidEnumException::class);
        expect($e->getMessage())->toContain('UNKNOWN');
        expect($e->getMessage())->toContain('UserStatus');

        $e2 = InvalidEnumException::value('UserStatus', 'invalid');
        expect($e2)->toBeInstanceOf(InvalidEnumException::class);
        expect($e2->getMessage())->toContain('invalid');
    });

    // -----------------------------------------------------------------------
    // 11. Attribute target constraints are correct
    // -----------------------------------------------------------------------
    it('attributes have correct Attribute targets', function () {
        $expectations = [
            Label::class => [Attribute::TARGET_CLASS_CONSTANT],
            Color::class => [Attribute::TARGET_CLASS_CONSTANT],
            Icon::class => [Attribute::TARGET_CLASS_CONSTANT],
            Description::class => [Attribute::TARGET_CLASS_CONSTANT],
            EnumLabel::class => [Attribute::TARGET_CLASS, Attribute::TARGET_CLASS_CONSTANT],
            EnumColor::class => [Attribute::TARGET_CLASS, Attribute::TARGET_CLASS_CONSTANT],
            EnumIcon::class => [Attribute::TARGET_CLASS, Attribute::TARGET_CLASS_CONSTANT],
            EnumDescription::class => [Attribute::TARGET_CLASS, Attribute::TARGET_CLASS_CONSTANT],
        ];

        foreach ($expectations as $class => $expectedTargets) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(Attribute::class);
            expect($attrs)->toHaveCount(1, "{$class} should have exactly one #[Attribute]");

            $instance = $attrs[0]->newInstance();
            $flags = $instance->flags;
            $actual = [];
            if ($flags & Attribute::TARGET_CLASS) { $actual[] = Attribute::TARGET_CLASS; }
            if ($flags & Attribute::TARGET_CLASS_CONSTANT) { $actual[] = Attribute::TARGET_CLASS_CONSTANT; }
            if ($flags & Attribute::TARGET_PROPERTY) { $actual[] = Attribute::TARGET_PROPERTY; }

            sort($actual);
            sort($expectedTargets);
            expect($actual)->toBe($expectedTargets, "Wrong targets for {$class}");
        }
    });

    // -----------------------------------------------------------------------
    // 12. EnumMetadataResolver returns correct shape
    // -----------------------------------------------------------------------
    it('EnumMetadataResolver returns correct metadata shape', function () {
        EnumMetadataResolver::invalidate(\ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus::class);

        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus::class);

        expect($meta)->toBeArray();
        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);

        // Each key should be an array
        expect($meta['labels'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
        expect($meta['colors'])->toBeArray();
        expect($meta['icons'])->toBeArray();
    });

    // -----------------------------------------------------------------------
    // 13. EnumTestGenerator produces valid PHP
    // -----------------------------------------------------------------------
    it('EnumTestGenerator produces valid PHP content', function () {
        $content = EnumTestGenerator::generate(
            \ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus::class
        );

        expect($content)->toBeString();
        expect($content)->toContain('declare(strict_types=1)');
        expect($content)->toContain('describe(');
        expect($content)->toContain('it(');
        expect($content)->toContain('forSelect');
        expect($content)->toContain('forApi');

        // Must start with <?php
        expect(str_starts_with(trim($content), '<?php'))->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // 14. EnumsServiceProvider registers singleton correctly
    // -----------------------------------------------------------------------
    it('EnumsServiceProvider registers zeroboiler.enum singleton', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        expect($ref->hasMethod('register'))->toBeTrue();
        expect($ref->hasMethod('boot'))->toBeTrue();

        // Check register method content references 'zeroboiler.enum'
        $method = $ref->getMethod('register');
        $filename = $method->getFileName();
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        $lines = array_slice(file($filename), $start - 1, $end - $start + 1);
        $content = implode('', $lines);

        expect($content)->toContain('zeroboiler.enum');
        expect($content)->toContain('singleton');
    });
});

/**
 * Recursively glob for files matching a pattern.
 *
 * @return list<string>
 */
function glob_recursive(string $baseDir, string $pattern): array
{
    $results = [];
    $files = glob($baseDir . '/' . $pattern);

    if ($files !== false) {
        $results = array_values($files);
    }

    $dirs = glob($baseDir . '/*', GLOB_ONLYDIR);

    if ($dirs !== false) {
        foreach ($dirs as $dir) {
            $results = [...$results, ...glob_recursive($dir, $pattern)];
        }
    }

    return $results;
}
