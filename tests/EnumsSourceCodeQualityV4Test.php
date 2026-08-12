<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
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
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

describe('Enums — Source Code Quality V4 Deep Audit', function () {
    // -------------------------------------------------------------------------
    // 1. All public methods across all src classes have return type declarations
    // -------------------------------------------------------------------------
    it('every public method in every src class has a return type declaration', function () {
        $srcDir = dirname(__DIR__, 2) . '/src';
        $files = glob_recursive($srcDir, '*.php');
        $violations = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            // Skip if file doesn't define a class
            if (! preg_match('/\b(class|trait|enum)\s+\w+/', $content)) {
                continue;
            }

            $tokens = token_get_all($content);
            // Find all function definitions and check for return types
            $i = 0;
            while ($i < count($tokens)) {
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {
                    // Skip __construct
                    $nameToken = null;
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                            $nameToken = $tokens[$j][1];
                            break;
                        }
                        if ($tokens[$j] === '(') {
                            break;
                        }
                    }

                    // Check visibility before function keyword
                    $isPublic = false;
                    for ($k = $i - 1; $k >= 0; $k--) {
                        if (is_array($tokens[$k])) {
                            if ($tokens[$k][0] === T_PUBLIC) {
                                $isPublic = true;
                                break;
                            }
                            if ($tokens[$k][0] === T_PRIVATE || $tokens[$k][0] === T_PROTECTED) {
                                break;
                            }
                        }
                    }

                    if ($isPublic && $nameToken !== null && ! str_starts_with($nameToken, '__')) {
                        // Check for return type between ) and {
                        $hasReturnType = false;
                        for ($m = $i; $m < count($tokens); $m++) {
                            if ($tokens[$m] === '{') {
                                break;
                            }
                            if ($tokens[$m] === ':') {
                                $hasReturnType = true;
                                break;
                            }
                        }

                        if (! $hasReturnType) {
                            $violations[] = basename($file) . '::' . $nameToken . '()';
                        }
                    }
                }
                $i++;
            }
        }

        expect($violations)->toBeEmpty(
            'Public methods without return types: ' . implode(', ', $violations)
        );
    });

    // -------------------------------------------------------------------------
    // 2. All constructor parameters in attribute classes have types
    // -------------------------------------------------------------------------
    it('all constructor parameters in attribute classes have type declarations', function () {
        $attributeClasses = [
            Color::class, Description::class, Icon::class, Label::class,
            EnumColor::class, EnumDescription::class, EnumIcon::class, EnumLabel::class,
        ];

        $violations = [];
        foreach ($attributeClasses as $class) {
            $ref = new ReflectionClass($class);
            $ctor = $ref->getConstructor();
            if ($ctor === null) {
                continue;
            }

            foreach ($ctor->getParameters() as $param) {
                if ($param->getType() === null) {
                    $violations[] = "{$class}::\${$param->getName()}";
                }
            }
        }

        expect($violations)->toBeEmpty(
            'Untyped constructor params: ' . implode(', ', $violations)
        );
    });

    // -------------------------------------------------------------------------
    // 3. EnumRule validate() accepts mixed but handles null/int/string correctly
    // -------------------------------------------------------------------------
    it('EnumRule validate method signature matches Laravel ValidationRule interface', function () {
        $ref = new ReflectionClass(EnumRule::class);
        $method = $ref->getMethod('validate');

        $params = $method->getParameters();
        expect($params)->toHaveCount(3);

        expect($params[0]->getName())->toBe('attribute');
        expect($params[0]->getType()->getName())->toBe('string');

        expect($params[1]->getName())->toBe('value');
        expect($params[1]->getType()->getName())->toBe('mixed');

        expect($params[2]->getName())->toBe('fail');
        expect($method->getReturnType()->getName())->toBe('void');
    });

    // -------------------------------------------------------------------------
    // 4. EnumCast implements get/set/serialize with correct signatures
    // -------------------------------------------------------------------------
    it('EnumCast has get, set, and serialize methods with correct parameter types', function () {
        $ref = new ReflectionClass(EnumCast::class);

        // get(object, string, int|string|null, array)
        $get = $ref->getMethod('get');
        $getParams = $get->getParameters();
        expect($getParams[0]->getType()->getName())->toBe('object');
        expect($getParams[1]->getType()->getName())->toBe('string');

        // set(object, string, BackedEnum|int|string|null, array)
        $set = $ref->getMethod('set');
        expect($set->getReturnType()->getName())->toBe('int');

        // serialize(object, string, BackedEnum|int|string|null, array)
        $serialize = $ref->getMethod('serialize');
        expect($serialize->getReturnType()->getName())->toBe('int');
    });

    // -------------------------------------------------------------------------
    // 5. EnumCache has proper TTL handling with type safety
    // -------------------------------------------------------------------------
    it('EnumCache TTL methods use int type (no float leakage)', function () {
        $ref = new ReflectionClass(EnumCache::class);

        $setTtl = $ref->getMethod('setTtl');
        $params = $setTtl->getParameters();
        expect($params[0]->getType()->getName())->toBe('int');

        $getTtl = $ref->getMethod('getTtl');
        expect($getTtl->getReturnType()->getName())->toBe('int');
    });

    // -------------------------------------------------------------------------
    // 6. HasEnumMetadata trait has all expected methods
    // -------------------------------------------------------------------------
    it('HasEnumMetadata trait provides all expected methods', function () {
        $ref = new ReflectionClass(HasEnumMetadata::class);
        $methods = array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            $ref->getMethods()
        );

        $expected = [
            'label', 'description', 'color', 'icon',
            'forSelect', 'forApi', 'tryFromLabel',
            'tryFromName', 'fromName', 'hasCase',
            'is', 'isNot', 'in', 'values', 'labels',
            'generateLabel',
        ];

        foreach ($expected as $method) {
            expect($methods)->toContain($method, "HasEnumMetadata must have {$method}()");
        }
    });

    // -------------------------------------------------------------------------
    // 7. fromName throws InvalidEnumException for non-existent case
    // -------------------------------------------------------------------------
    it('fromName throws InvalidEnumException for non-existent names', function () {
        expect(fn () => PaymentStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    // -------------------------------------------------------------------------
    // 8. Backed enum vs pure enum behavior consistency
    // -------------------------------------------------------------------------
    it('backed enums and pure enums both support full metadata API', function () {
        // Int-backed enum
        $intCase = IntBackedPriority::cases()[0];
        expect($intCase->label())->toBeString()->not->toBeEmpty();
        expect($intCase->color())->toBeString();
        expect(IntBackedPriority::forSelect())->toBeArray()->not->toBeEmpty();
        expect(IntBackedPriority::forApi())->toBeArray()->not->toBeEmpty();
        expect(IntBackedPriority::values())->toBeArray()->not->toBeEmpty();

        // String-backed enum
        $strCase = PaymentStatus::cases()[0];
        expect($strCase->label())->toBeString()->not->toBeEmpty();
        expect($strCase->color())->toBeString();
        expect(PaymentStatus::forSelect())->toBeArray()->not->toBeEmpty();

        // Pure enum
        $pureCase = PureFeatureFlag::cases()[0];
        expect($pureCase->label())->toBeString()->not->toBeEmpty();
        expect($pureCase->color())->toBeString();
        expect(PureFeatureFlag::forSelect())->toBeArray()->not->toBeEmpty();
        expect(PureFeatureFlag::values())->toBeArray()->not->toBeEmpty();
    });

    // -------------------------------------------------------------------------
    // 9. Zero-backed int enum edge case
    // -------------------------------------------------------------------------
    it('zero-backed int enum handles value 0 correctly', function () {
        $zeroCase = ZeroBackedPriority::cases()[0];
        expect($zeroCase->label())->toBeString()->not->toBeEmpty();
        expect($zeroCase->value)->toBe(0);
    });

    // -------------------------------------------------------------------------
    // 10. EnumMetadataResolver::invalidate works per-class
    // -------------------------------------------------------------------------
    it('EnumMetadataResolver invalidates per-class without affecting others', function () {
        EnumMetadataResolver::resolve(PaymentStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        EnumMetadataResolver::invalidate(PaymentStatus::class);

        // IntBackedPriority should still be cached
        $cache = EnumCache::getInstance();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();
        expect($cache->has(PaymentStatus::class))->toBeFalse();

        // Cleanup
        EnumMetadataResolver::invalidateAll();
    });

    // -------------------------------------------------------------------------
    // 11. Facade accessor key consistency
    // -------------------------------------------------------------------------
    it('Facade accessor and ServiceProvider binding use the same key', function () {
        $facadeRef = new ReflectionClass(Enum::class);
        $method = $facadeRef->getMethod('getFacadeAccessor');
        $filename = $method->getFileName();
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        $lines = array_slice(file($filename), $start - 1, $end - $start + 1);
        $facadeContent = implode('', $lines);

        expect($facadeContent)->toContain("'zeroboiler.enum'");

        $spRef = new ReflectionClass(EnumsServiceProvider::class);
        $spMethod = $spRef->getMethod('register');
        $spFilename = $spMethod->getFileName();
        $spStart = $spMethod->getStartLine();
        $spEnd = $spMethod->getEndLine();
        $spLines = array_slice(file($spFilename), $spStart - 1, $spEnd - $spStart + 1);
        $spContent = implode('', $spLines);

        expect($spContent)->toContain("'zeroboiler.enum'");
        expect($spContent)->toContain('singleton');
    });

    // -------------------------------------------------------------------------
    // 12. EnumTestGenerator output is valid PHP structure
    // -------------------------------------------------------------------------
    it('EnumTestGenerator produces valid PHP content for backed enums', function () {
        $output = EnumTestGenerator::generate(PaymentStatus::class);

        expect($output)->toContain('<?php');
        expect($output)->toContain('declare(strict_types=1)');
        expect($output)->toContain('describe(');
        expect($output)->toContain('use ' . PaymentStatus::class);
        expect($output)->toContain('it(');
    });

    // -------------------------------------------------------------------------
    // 13. No duplicate attribute classes
    // -------------------------------------------------------------------------
    it('no duplicate attribute class names exist in src/Attributes', function () {
        $attrDir = dirname(__DIR__, 2) . '/src/Attributes';
        $files = glob($attrDir . '/*.php');
        $classNames = array_map(static fn (string $f): string => basename($f, '.php'), $files);

        $unique = array_unique($classNames);
        expect($unique)->toHaveCount(count($classNames), 'Duplicate attribute class names found');
    });

    // -------------------------------------------------------------------------
    // 14. composer.json requires PHP ^8.5
    // -------------------------------------------------------------------------
    it('composer.json requires PHP ^8.5', function () {
        $composer = json_decode(
            file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        expect($composer['require']['php'])->toBe('^8.5');
    });

    // -------------------------------------------------------------------------
    // 15. phpstan.neon.dist is configured for level 9
    // -------------------------------------------------------------------------
    it('phpstan.neon.dist sets level to 9', function () {
        $neonContent = file_get_contents(dirname(__DIR__, 2) . '/phpstan.neon.dist');

        expect($neonContent)->toContain('level: 9');
        expect($neonContent)->toContain('paths:');
        expect($neonContent)->toContain('- src');
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
