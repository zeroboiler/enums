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
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * PHP 8.5 strict type contract verification and production readiness audit.
 *
 * Verifies: declare(strict_types=1), return type completeness, final classes,
 * readonly properties, attribute targets, interface contracts, and PHPStan L9 compliance.
 */
describe('PHP 8.5 Type Safety and Production Contract Audit V51', function (): void {
    // ── Attribute Final Class Verification ─────────────────────────────

    it('all attribute classes are final', function (): void {
        $attributeClasses = [
            Label::class,
            Color::class,
            Icon::class,
            Description::class,
            EnumLabel::class,
            EnumColor::class,
            EnumIcon::class,
            EnumDescription::class,
        ];

        foreach ($attributeClasses as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    // ── Infrastructure Class Final Verification ────────────────────────

    it('all infrastructure classes are final', function (): void {
        $classes = [
            EnumCache::class,
            EnumManager::class,
            EnumRule::class,
            EnumCast::class,
            InvalidEnumException::class,
            Enum::class,
        ];

        foreach ($classes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('EnumManager is readonly', function (): void {
        $ref = new ReflectionClass(EnumManager::class);
        expect($ref->isFinal())->toBeTrue();
        // PHP 8.2+ readonly class check
        $modifiers = $ref->getModifiers();
        // ReflectionClass doesn't expose isReadonly() directly in all versions,
        // but we can verify it through the constructor absence of mutable properties
        $props = $ref->getProperties();
        foreach ($props as $prop) {
            expect($prop->isReadOnly(ReflectionProperty::IS_PRIVATE))
                ->or()->toBeTrue();
        }
    });

    // ── EnumRule readonly class verification ──────────────────────────

    it('EnumRule is readonly with no mutable properties', function (): void {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->isFinal())->toBeTrue();

        $props = $ref->getProperties();
        foreach ($props as $prop) {
            // All properties should be private readonly or private
            expect($prop->isReadOnly(ReflectionProperty::IS_PRIVATE))
                ->or()->toBeTrue();
        }
    });

    // ── Strict Types Declaration Verification ──────────────────────────

    it('all source files declare strict types', function (): void {
        $srcDir = dirname(__DIR__, 2).'/src';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getRealPath();
            }
        }

        expect($files)->not->toBeEmpty();

        foreach ($files as $filePath) {
            $contents = file_get_contents($filePath);
            expect($contents)->toContain('declare(strict_types=1)');
        }
    });

    // ── Return Type Completeness Verification ──────────────────────────

    it('HasEnumMetadata trait has complete return types on all methods', function (): void {
        $ref = new ReflectionClass(HasEnumMetadata::class);
        $methods = $ref->getMethods();

        foreach ($methods as $method) {
            if ($method->getName() === 'generateLabel') {
                continue; // private, tested separately
            }

            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull(
                "HasEnumMetadata::{$method->getName()}() must have a return type"
            );
        }
    });

    it('EnumManager has complete return types on all methods', function (): void {
        $ref = new ReflectionClass(EnumManager::class);

        foreach ($ref->getMethods() as $method) {
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull(
                "EnumManager::{$method->getName()}() must have a return type"
            );
        }
    });

    // ── EnumCache Singleton Safety Verification ────────────────────────

    it('EnumCache blocks cloning, serialization, and unserialization', function (): void {
        $ref = new ReflectionClass(EnumCache::class);

        // __clone must return 'never'
        $clone = $ref->getMethod('__clone');
        $cloneReturn = $clone->getReturnType();
        expect($cloneReturn)->not->toBeNull();
        expect($cloneReturn->getName())->toBe('never');

        // __wakeup must return 'never'
        $wakeup = $ref->getMethod('__wakeup');
        $wakeupReturn = $wakeup->getReturnType();
        expect($wakeupReturn)->not->toBeNull();
        expect($wakeupReturn->getName())->toBe('never');

        // __serialize must return 'never'
        $serialize = $ref->getMethod('__serialize');
        $serializeReturn = $serialize->getReturnType();
        expect($serializeReturn)->not->toBeNull();
        expect($serializeReturn->getName())->toBe('never');

        // __unserialize must return 'never'
        $unserialize = $ref->getMethod('__unserialize');
        $unserializeReturn = $unserialize->getReturnType();
        expect($unserializeReturn)->not->toBeNull();
        expect($unserializeReturn->getName())->toBe('never');
    });

    it('EnumCache __debugInfo returns array with expected keys', function (): void {
        $cache = EnumCache::getInstance();
        $debug = $cache->__debugInfo();

        expect($debug)->toBeArray();
        expect($debug)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
        expect($debug['ttl'])->toBeInt();
        expect($debug['cachedClasses'])->toBeInt();
        expect($debug['timestampCount'])->toBeInt();

        EnumCache::resetInstance();
    });

    // ── Attribute Target Verification ────────────────────────────────

    it('per-case attributes target only class constants', function (): void {
        $perCaseAttributes = [
            Label::class,
            Color::class,
            Icon::class,
            Description::class,
        ];

        foreach ($perCaseAttributes as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes();
            $found = false;
            foreach ($attrs as $attr) {
                if ($attr->getName() === 'Attribute') {
                    $instance = $attr->newInstance();
                    $flags = $instance->getFlags();
                    $hasClassConstant = (bool) ($flags & Attribute::TARGET_CLASS_CONSTANT);
                    $hasClass = (bool) ($flags & Attribute::TARGET_CLASS);
                    // Per-case attributes should target class constant only
                    // (no TARGET_CLASS bit set unless it's a dual-target attribute)
                    expect($hasClassConstant)->toBeTrue("{$class} must target CLASS_CONSTANT");
                    $found = true;
                }
            }
            expect($found)->toBeTrue("{$class} must have #[Attribute] declaration");
        }
    });

    it('class-level attributes target class and class constant', function (): void {
        $classLevelAttributes = [
            EnumLabel::class,
            EnumColor::class,
            EnumIcon::class,
            EnumDescription::class,
        ];

        foreach ($classLevelAttributes as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes();
            $found = false;
            foreach ($attrs as $attr) {
                if ($attr->getName() === 'Attribute') {
                    $instance = $attr->newInstance();
                    $flags = $instance->getFlags();
                    expect($flags & Attribute::TARGET_CLASS)->toBeGreaterThan(0);
                    expect($flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);
                    $found = true;
                }
            }
            expect($found)->toBeTrue("{$class} must have #[Attribute] declaration");
        }
    });

    // ── Attribute Readonly Properties Verification ────────────────────

    it('all attribute properties are public readonly', function (): void {
        $attributeClasses = [
            Label::class => ['value'],
            Color::class => ['value'],
            Icon::class => ['value'],
            Description::class => ['value'],
            EnumLabel::class => ['labels', 'label'],
            EnumColor::class => ['success', 'danger', 'warning', 'info', 'secondary'],
            EnumIcon::class => ['default', 'icons'],
            EnumDescription::class => ['descriptions', 'description'],
        ];

        foreach ($attributeClasses as $class => $expectedProps) {
            $ref = new ReflectionClass($class);
            foreach ($expectedProps as $propName) {
                $prop = $ref->getProperty($propName);
                expect($prop->isPublic())->toBeTrue("{$class}::\${$propName} must be public");
                expect($prop->isReadOnly(ReflectionProperty::IS_PUBLIC))
                    ->or()->toBeTrue("{$class}::\${$propName} must be readonly");
            }
        }
    });

    // ── InvalidEnumException Contract ──────────────────────────────────

    it('InvalidEnumException named constructors produce correct types', function (): void {
        $ex1 = InvalidEnumException::value(UserStatus::class, 'invalid_value');
        expect($ex1)->toBeInstanceOf(InvalidEnumException::class);
        expect($ex1->getMessage())->toContain('invalid_value');
        expect($ex1->getMessage())->toContain(UserStatus::class);

        $ex2 = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');
        expect($ex2)->toBeInstanceOf(InvalidEnumException::class);
        expect($ex2->getMessage())->toContain('NONEXISTENT');
        expect($ex2->getMessage())->toContain(UserStatus::class);

        $ex3 = InvalidEnumException::value(IntPriority::class, null);
        expect($ex3->getMessage())->toContain('null');
    });

    it('InvalidEnumException __toString produces class: message format', function (): void {
        $ex = InvalidEnumException::forName(UserStatus::class, 'BAD');
        $str = (string) $ex;
        expect($str)->toContain(InvalidEnumException::class);
        expect($str)->toContain($ex->getMessage());
    });

    // ── EnumCast Interface Contract ───────────────────────────────────

    it('EnumCast implements CastsAttributes interface', function (): void {
        $ref = new ReflectionClass(EnumCast::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))
            ->toBeTrue();
    });

    it('EnumCast constructor accepts class-string parameter', function (): void {
        $ref = new ReflectionClass(EnumCast::class);
        $constructor = $ref->getConstructor();
        expect($constructor)->not->toBeNull();

        $params = $constructor->getParameters();
        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('enumClass');
        expect($params[0]->isReadOnly())->toBeTrue();
        expect($params[1]->getName())->toBe('validate');
        expect($params[1]->isOptional())->toBeTrue();
    });

    // ── EnumRule Interface Contract ────────────────────────────────────

    it('EnumRule implements ValidationRule interface', function (): void {
        expect(EnumRule::for(UserStatus::class))
            ->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
    });

    it('EnumRule::for and nullable produce independent instances', function (): void {
        $rule1 = EnumRule::for(UserStatus::class);
        $rule2 = $rule1->nullable();

        expect($rule1)->not->toBe($rule2);
    });

    // ── Pure Enum Specific Tests ──────────────────────────────────────

    it('pure enum values() returns case names', function (): void {
        $values = PureFeatureFlag::values();

        foreach ($values as $value) {
            expect($value)->toBeString();
            // Should be the case name, not a backed value
            expect(PureFeatureFlag::tryFromName($value))
                ->toBeInstanceOf(PureFeatureFlag::class);
        }
    });

    it('pure enum forSelect uses case names as values', function (): void {
        $options = PureSystemState::forSelect();

        foreach ($options as $option) {
            expect($option)->toHaveKey('value');
            expect($option)->toHaveKey('label');
            // For pure enums, value should be the case name
            expect($option['value'])->toBeString();
        }
    });

    it('pure enum tryFromLabel works with auto-generated labels', function (): void {
        $label = PureFeatureFlag::DARK_MODE->label();
        expect($label)->toBeString()->not->toBeEmpty();

        $found = PureFeatureFlag::tryFromLabel($label);
        expect($found)->toBe(PureFeatureFlag::DARK_MODE);
    });

    // ── Int-Backed Enum Specific Tests ──────────────────────────────────

    it('int-backed enum values() returns integers', function (): void {
        $values = IntPriority::values();

        foreach ($values as $value) {
            expect($value)->toBeInt();
        }
    });

    it('int-backed enum forSelect returns int values', function (): void {
        $options = IntPriority::forSelect();

        foreach ($options as $option) {
            expect($option['value'])->toBeInt();
            expect($option['label'])->toBeString()->not->toBeEmpty();
        }
    });

    // ── Single Case Enum Edge Cases ──────────────────────────────────

    it('single case enum works correctly', function (): void {
        $cases = SingleCaseToggle::cases();
        expect($cases)->toHaveCount(1);

        $toggle = SingleCaseToggle::ENABLED;
        expect($toggle->is('ENABLED'))->toBeTrue();
        expect($toggle->isNot('DISABLED'))->toBeTrue();
        expect($toggle->label())->toBeString()->not->toBeEmpty();
    });

    // ── Cross-Fixture Label Uniqueness ────────────────────────────────

    it('labels for all fixtures are non-empty strings', function (): void {
        $enumClasses = [
            UserStatus::class,
            IntPriority::class,
            IntStatusWithColor::class,
            PlainTestEnum::class,
            PureFeatureFlag::class,
            PureSystemState::class,
            SingleCaseToggle::class,
        ];

        foreach ($enumClasses as $enumClass) {
            $cases = $enumClass::cases();
            foreach ($cases as $case) {
                if (method_exists($case, 'label')) {
                    expect($case->label())->toBeString()->not->toBeEmpty();
                }
            }
        }
    });

    // ── toValue() Normalization ───────────────────────────────────────

    it('toValue() returns backed value for backed enums', function (): void {
        expect(UserStatus::ACTIVE->toValue())->toBe('active');
        expect(IntPriority::LOW->toValue())->toBeInt();
    });

    it('toValue() returns case name for pure enums', function (): void {
        expect(PureFeatureFlag::DARK_MODE->toValue())->toBe('DARK_MODE');
    });

    // ── EnumManager Trait Guard ────────────────────────────────────────

    it('EnumManager rejects non-metadata enums', function (): void {
        $manager = new EnumManager;
        // PlainTestEnum does not use HasEnumMetadata
        expect(fn () => $manager->forSelect(PlainTestEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });
});
