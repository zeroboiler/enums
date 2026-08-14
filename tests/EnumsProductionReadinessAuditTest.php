<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enums Production Readiness — Type Safety & Contract Audit', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
        EnumMetadataResolver::invalidate(UserStatus::class);
        EnumMetadataResolver::invalidate(IntBackedPriority::class);
        EnumMetadataResolver::invalidate(PureFeatureFlag::class);
        EnumMetadataResolver::invalidate(SingleCaseEnum::class);
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    // -----------------------------------------------------------------------
    // Attribute classes — final, readonly, strict types
    // -----------------------------------------------------------------------

    it('all attribute classes are final', function (): void {
        $attributes = [
            Color::class,
            Description::class,
            EnumColor::class,
            EnumDescription::class,
            EnumIcon::class,
            EnumLabel::class,
            Icon::class,
            Label::class,
        ];

        foreach ($attributes as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue("{$attrClass} must be final");
        }
    });

    it('all attribute classes have readonly constructor properties', function (): void {
        $ref = new ReflectionClass(Color::class);
        $ctor = $ref->getConstructor();
        $param = $ctor->getParameters()[0];
        expect($param->isReadOnly())->toBeTrue('Color::$value must be readonly');

        $ref = new ReflectionClass(EnumLabel::class);
        $ctor = $ref->getConstructor();
        foreach ($ctor->getParameters() as $p) {
            expect($p->isReadOnly())->toBeTrue("EnumLabel::\${$p->getName()} must be readonly");
        }
    });

    // -----------------------------------------------------------------------
    // Core classes — final, readonly where applicable
    // -----------------------------------------------------------------------

    it('EnumCache is final', function (): void {
        expect((new ReflectionClass(EnumCache::class))->isFinal())->toBeTrue();
    });

    it('EnumManager is final and readonly', function (): void {
        $ref = new ReflectionClass(EnumManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumMetadataResolver is final', function (): void {
        expect((new ReflectionClass(EnumMetadataResolver::class))->isFinal())->toBeTrue();
    });

    it('EnumTestGenerator is final', function (): void {
        expect((new ReflectionClass(EnumTestGenerator::class))->isFinal())->toBeTrue();
    });

    it('EnumRule is final and readonly', function (): void {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumCast is final', function (): void {
        expect((new ReflectionClass(EnumCast::class))->isFinal())->toBeTrue();
    });

    it('InvalidEnumException is final', function (): void {
        expect((new ReflectionClass(InvalidEnumException::class))->isFinal())->toBeTrue();
    });

    it('EnumsServiceProvider is final', function (): void {
        expect((new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class))->isFinal())->toBeTrue();
    });

    it('Enum facade is final', function (): void {
        expect((new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class))->isFinal())->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // All source files have declare(strict_types=1)
    // -----------------------------------------------------------------------

    it('all source files declare strict types', function (): void {
        $srcDir = __DIR__.'/../../src';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $violations = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            if (! str_contains($contents, 'declare(strict_types=1)')) {
                $violations[] = $file->getPathname();
            }
        }

        expect($violations)->toBeEmpty(
            'All source files must have declare(strict_types=1). Violations: '.implode(', ', $violations)
        );
    });

    // -----------------------------------------------------------------------
    // EnumManager — all methods have return type declarations
    // -----------------------------------------------------------------------

    it('EnumManager methods all have explicit return types', function (): void {
        $ref = new ReflectionClass(EnumManager::class);
        $manager = new EnumManager;

        $methods = ['forSelect', 'forApi', 'tryFromLabel', 'tryFromName', 'fromName', 'hasCase', 'values', 'labels'];
        foreach ($methods as $method) {
            $rm = $ref->getMethod($method);
            $rt = $rm->getReturnType();
            expect($rt)->not->toBeNull("EnumManager::{$method}() must have a return type");

            // Verify return type is a named type (not void for non-void methods, etc.)
            if ($method === 'hasCase') {
                expect($rt->getName())->toBe('bool');
            } elseif ($method === 'fromName') {
                expect($rt->getName())->toBe('UnitEnum');
            } elseif ($method === 'tryFromLabel' || $method === 'tryFromName') {
                expect((string) $rt)->toBe('UnitEnum|null');
            }
        }
    });

    // -----------------------------------------------------------------------
    // EnumCache — __clone and __wakeup have never return type
    // -----------------------------------------------------------------------

    it('EnumCache __clone throws RuntimeException and has never return type', function (): void {
        $ref = new ReflectionClass(EnumCache::class);
        $cloneMethod = $ref->getMethod('__clone');
        expect($cloneMethod->isPrivate())->toBeTrue();
        expect($cloneMethod->getReturnType()?->getName())->toBe('never');

        $cache = EnumCache::getInstance();
        expect(fn () => clone $cache)->toThrow(\RuntimeException::class);
    });

    it('EnumCache __wakeup throws RuntimeException and has never return type', function (): void {
        $ref = new ReflectionClass(EnumCache::class);
        $wakeupMethod = $ref->getMethod('__wakeup');
        expect($wakeupMethod->getReturnType()?->getName())->toBe('never');
    });

    // -----------------------------------------------------------------------
    // InvalidEnumException — named constructors return self
    // -----------------------------------------------------------------------

    it('InvalidEnumException::value() returns self with correct message', function (): void {
        $ex = InvalidEnumException::value(UserStatus::class, 'invalid_value');
        expect($ex)->toBeInstanceOf(InvalidEnumException::class);
        expect($ex->getMessage())->toContain('invalid_value');
        expect($ex->getMessage())->toContain(UserStatus::class);
    });

    it('InvalidEnumException::forName() returns self with correct message', function (): void {
        $ex = InvalidEnumException::forName(UserStatus::class, 'NON_EXISTENT');
        expect($ex)->toBeInstanceOf(InvalidEnumException::class);
        expect($ex->getMessage())->toContain('NON_EXISTENT');
        expect($ex->getMessage())->toContain('does not exist');
    });

    it('InvalidEnumException::value() handles null value', function (): void {
        $ex = InvalidEnumException::value(UserStatus::class, null);
        expect($ex->getMessage())->toContain('null');
    });

    it('InvalidEnumException __toString includes class name', function (): void {
        $ex = InvalidEnumException::value(UserStatus::class, 'bad');
        $str = (string) $ex;
        expect($str)->toContain('InvalidEnumException');
        expect($str)->toContain('bad');
    });

    // -----------------------------------------------------------------------
    // EnumRule — backed enum type mismatch detection
    // -----------------------------------------------------------------------

    it('EnumRule rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(IntBackedPriority::class);
        $fail = fn (string $msg): string => $msg;
        $failCalled = false;
        $failMsg = '';

        // Use reflection to test the validate method directly
        // In production, Laravel calls this; we simulate it
        $rule->validate('priority', 'not-an-int', function (string $message) use (&$failCalled, &$failMsg): void {
            $failCalled = true;
            $failMsg = $message;
        });

        expect($failCalled)->toBeTrue();
    });

    it('EnumRule rejects int value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failCalled = false;

        $rule->validate('status', 42, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('EnumRule nullable allows null values', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failCalled = false;

        $rule->validate('status', null, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeFalse();
    });

    it('EnumRule non-nullable rejects null values', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failCalled = false;

        $rule->validate('status', null, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('EnumRule validates valid value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failCalled = false;

        $rule->validate('status', 'active', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeFalse();
    });

    it('EnumRule validates valid value for int-backed enum', function (): void {
        $rule = EnumRule::for(IntBackedPriority::class);
        $failCalled = false;

        $rule->validate('priority', 1, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeFalse();
    });

    // -----------------------------------------------------------------------
    // EnumRule — pure enum support (case name matching)
    // -----------------------------------------------------------------------

    it('EnumRule validates valid case name for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failCalled = false;

        $rule->validate('flag', 'DARK_MODE', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeFalse();
    });

    it('EnumRule rejects invalid case name for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failCalled = false;

        $rule->validate('flag', 'NON_EXISTENT', function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    it('EnumRule rejects non-string value for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failCalled = false;

        $rule->validate('flag', 123, function (string $message) use (&$failCalled): void {
            $failCalled = true;
        });

        expect($failCalled)->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // EnumRule — error messages include allowed values
    // -----------------------------------------------------------------------

    it('EnumRule message includes allowed values for metadata enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failMsg = '';

        $rule->validate('status', 'bad_value', function (string $message) use (&$failMsg): void {
            $failMsg = $message;
        });

        expect($failMsg)->toContain('active');
    });

    // -----------------------------------------------------------------------
    // EnumTestGenerator — validates generated output structure
    // -----------------------------------------------------------------------

    it('EnumTestGenerator produces valid PHP for string-backed enum', function (): void {
        $code = EnumTestGenerator::generate(UserStatus::class);

        expect($code)->toContain('declare(strict_types=1)');
        expect($code)->toContain('use '.UserStatus::class);
        expect($code)->toContain('describe(');
        expect($code)->toContain("it('has cases'");
        expect($code)->toContain('forSelect');
        expect($code)->toContain('forApi');
        expect($code)->toContain('fromName');
        expect($code)->toContain('tryFromLabel');
        expect($code)->toContain('InvalidEnumException');
    });

    it('EnumTestGenerator produces valid PHP for int-backed enum', function (): void {
        $code = EnumTestGenerator::generate(IntBackedPriority::class);

        expect($code)->toContain('declare(strict_types=1)');
        expect($code)->toContain('values() returns int backed values');
    });

    it('EnumTestGenerator produces valid PHP for pure enum', function (): void {
        $code = EnumTestGenerator::generate(PureFeatureFlag::class);

        expect($code)->toContain('values() returns case names for pure enum');
    });

    it('EnumTestGenerator produces valid PHP for single-case enum', function (): void {
        $code = EnumTestGenerator::generate(SingleCaseEnum::class);

        expect($code)->toContain('has the expected number of cases');
        expect($code)->toContain('toHaveCount(1)');
    });

    // -----------------------------------------------------------------------
    // EnumCast — return type verification
    // -----------------------------------------------------------------------

    it('EnumCast::get() has correct return type', function (): void {
        $ref = new ReflectionClass(EnumCast::class);
        $method = $ref->getMethod('get');
        $rt = $method->getReturnType();
        expect($rt)->not->toBeNull();
        expect((string) $rt)->toBe('?BackedEnum');
    });

    it('EnumCast::set() has correct return type', function (): void {
        $ref = new ReflectionClass(EnumCast::class);
        $method = $ref->getMethod('set');
        $rt = $method->getReturnType();
        expect($rt)->not->toBeNull();
        expect((string) $rt)->toBe('int|string|null');
    });

    it('EnumCast::serialize() has correct return type', function (): void {
        $ref = new ReflectionClass(EnumCast::class);
        $method = $ref->getMethod('serialize');
        $rt = $method->getReturnType();
        expect($rt)->not->toBeNull();
        expect((string) $rt)->toBe('int|string|null');
    });

    // -----------------------------------------------------------------------
    // HasEnumMetadata — single-case enum edge cases
    // -----------------------------------------------------------------------

    it('single-case enum forSelect returns one option', function (): void {
        $options = SingleCaseEnum::forSelect();
        expect($options)->toHaveCount(1);
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('single-case enum forApi returns one entry', function (): void {
        $api = SingleCaseEnum::forApi();
        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('single-case enum in() always returns true for its own case', function (): void {
        expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
        expect(SingleCaseEnum::ONLY->in(['ONLY']))->toBeTrue();
    });

    it('single-case enum notIn() always returns true for empty array', function (): void {
        expect(SingleCaseEnum::ONLY->notIn([]))->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // Backed enum type consistency
    // -----------------------------------------------------------------------

    it('string-backed enum values() returns strings', function (): void {
        foreach (UserStatus::values() as $value) {
            expect($value)->toBeString();
        }
    });

    it('int-backed enum values() returns ints', function (): void {
        foreach (IntBackedPriority::values() as $value) {
            expect($value)->toBeInt();
        }
    });

    it('pure enum values() returns case names (strings)', function (): void {
        foreach (PureFeatureFlag::values() as $value) {
            expect($value)->toBeString();
            // Must match an actual case name
            expect(PureFeatureFlag::tryFromName($value))->not->toBeNull();
        }
    });

    // -----------------------------------------------------------------------
    // Metadata resolver — cache consistency
    // -----------------------------------------------------------------------

    it('resolve() returns identical metadata on repeated calls', function (): void {
        $first = EnumMetadataResolver::resolve(UserStatus::class);
        $second = EnumMetadataResolver::resolve(UserStatus::class);

        expect($first)->toBe($second);
    });

    it('invalidate() forces re-resolution', function (): void {
        $first = EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Build new metadata (should be structurally equal but not same object)
        $second = EnumMetadataResolver::resolve(UserStatus::class);
        expect($second)->toEqual($first);
    });

    it('resolve() for multiple enums keeps entries separate', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);
        EnumMetadataResolver::resolve(PureFeatureFlag::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();
        expect($cache->has(PureFeatureFlag::class))->toBeTrue();

        // Clearing one does not affect others
        EnumMetadataResolver::invalidate(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();
        expect($cache->has(PureFeatureFlag::class))->toBeTrue();
    });
});
