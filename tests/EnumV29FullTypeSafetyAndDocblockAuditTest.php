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
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('V29 Full Type Safety and Docblock Audit', function () {
    // ── Attribute Class Structure ─────────────────────────────────────────

    describe('attribute class structure', function () {
        it('all per-case attributes are final with readonly promoted properties', function () {
            $perCaseAttrs = [Color::class, Label::class, Icon::class, Description::class];

            foreach ($perCaseAttrs as $attrClass) {
                $ref = new ReflectionClass($attrClass);

                expect($ref->isFinal())->toBeTrue("{$attrClass} must be final");
                expect($ref->isReadOnly())->toBeFalse("{$attrClass} should NOT be readonly (has constructor)");

                $constructor = $ref->getConstructor();
                expect($constructor)->not->toBeNull("{$attrClass} must have a constructor");

                // Check all constructor parameters are public readonly promoted
                foreach ($constructor->getParameters() as $param) {
                    expect($param->isPromoted())->toBeTrue("{$attrClass}::\${$param->name} must be promoted");
                }

                // Verify property is readonly
                foreach ($ref->getProperties() as $prop) {
                    expect($prop->isReadOnly())->toBeTrue("{$attrClass}::\${$prop->name} must be readonly");
                    expect($prop->isPublic())->toBeTrue("{$attrClass}::\${$prop->name} must be public");
                }
            }
        });

        it('all class-level attributes are final with correct targets', function () {
            $classLevelAttrs = [EnumColor::class, EnumLabel::class, EnumIcon::class, EnumDescription::class];

            foreach ($classLevelAttrs as $attrClass) {
                $ref = new ReflectionClass($attrClass);

                expect($ref->isFinal())->toBeTrue("{$attrClass} must be final");

                // Should target both CLASS and CLASS_CONSTANT
                $attr = $ref->getAttributes()[0] ?? null;
                expect($attr)->not->toBeNull("{$attrClass} must have Attribute attribute");

                $newInstance = $attr->newInstance();
                $targets = $newInstance->getFlags();

                expect($targets & Attribute::TARGET_CLASS)->toBeGreaterThan(0,
                    "{$attrClass} must target CLASS");
                expect($targets & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0,
                    "{$attrClass} must target CLASS_CONSTANT");
            }
        });

        it('per-case attributes only target CLASS_CONSTANT', function () {
            $perCaseAttrs = [Color::class, Label::class, Icon::class, Description::class];

            foreach ($perCaseAttrs as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                $attr = $ref->getAttributes()[0] ?? null;
                expect($attr)->not->toBeNull();

                $newInstance = $attr->newInstance();
                $targets = $newInstance->getFlags();

                expect($targets & Attribute::TARGET_CLASS)->toBe(0,
                    "{$attrClass} must NOT target CLASS");
                expect($targets & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0,
                    "{$attrClass} must target CLASS_CONSTANT");
            }
        });
    });

    // ── Service Class Structure ──────────────────────────────────────────

    describe('service class structure', function () {
        it('EnumManager is final readonly', function () {
            $ref = new ReflectionClass(EnumManager::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('EnumRule is final readonly and implements ValidationRule', function () {
            $ref = new ReflectionClass(EnumRule::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
            expect($ref->implementsInterface(\Illuminate\Contracts\Validation\ValidationRule::class))->toBeTrue();
        });

        it('EnumCache is final with singleton pattern', function () {
            $ref = new ReflectionClass(EnumCache::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeFalse('EnumCache holds mutable state');

            // Constructor must be private
            $constructor = $ref->getConstructor();
            expect($constructor)->not->toBeNull();
            expect($constructor->isPrivate())->toBeTrue();

            // __clone must return never
            $cloneMethod = $ref->getMethod('__clone');
            expect($cloneMethod->isPrivate())->toBeTrue();
            $returnType = $cloneMethod->getReturnType();
            expect($returnType->getName())->toBe('never');

            // __wakeup must return never
            $wakeupMethod = $ref->getMethod('__wakeup');
            expect($wakeupMethod->getReturnType()->getName())->toBe('never');

            // __serialize must return never
            $serializeMethod = $ref->getMethod('__serialize');
            expect($serializeMethod->getReturnType()->getName())->toBe('never');

            // __unserialize must return never
            $unserializeMethod = $ref->getMethod('__unserialize');
            expect($unserializeMethod->getReturnType()->getName())->toBe('never');
        });

        it('EnumMetadataResolver is final and stateless', function () {
            $ref = new ReflectionClass(EnumMetadataResolver::class);

            expect($ref->isFinal())->toBeTrue();

            // All methods should be static
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                expect($method->isStatic())->toBeTrue(
                    "EnumMetadataResolver::{$method->name}() must be static"
                );
            }
        });

        it('InvalidEnumException is final with named constructors', function () {
            $ref = new ReflectionClass(InvalidEnumException::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isSubclassOf(\Exception::class))->toBeTrue();

            // Must have value() and forName() named constructors
            expect($ref->hasMethod('value'))->toBeTrue();
            expect($ref->hasMethod('forName'))->toBeTrue();

            // Named constructors must be static and return self
            $valueMethod = $ref->getMethod('value');
            expect($valueMethod->isStatic())->toBeTrue();
            expect($valueMethod->getReturnType()->getName())->toBe('self');

            $forNameMethod = $ref->getMethod('forName');
            expect($forNameMethod->isStatic())->toBeTrue();
            expect($forNameMethod->getReturnType()->getName())->toBe('self');
        });

        it('EnumCast is final and implements CastsAttributes', function () {
            $ref = new ReflectionClass(EnumCast::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
        });

        it('EnumsServiceProvider is final', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('Enum facade is final', function () {
            $ref = new ReflectionClass(Enum::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isSubclassOf(\Illuminate\Support\Facades\Facade::class))->toBeTrue();
        });
    });

    // ── Return Type Completeness ───────────────────────────────────────────

    describe('return type completeness', function () {
        it('HasEnumMetadata trait methods all have explicit return types', function () {
            $ref = new ReflectionClass(UserStatus::class);
            $traitMethods = ['label', 'description', 'color', 'icon',
                'forSelect', 'forApi', 'tryFromLabel', 'tryFromName',
                'fromName', 'hasCase', 'is', 'isNot', 'in', 'notIn',
                'values', 'labels'];

            foreach ($traitMethods as $methodName) {
                $method = $ref->getMethod($methodName);
                $returnType = $method->getReturnType();

                expect($returnType)->not->toBeNull(
                    "UserStatus::{$methodName}() must have a return type"
                );
            }
        });

        it('EnumManager methods all have explicit return types', function () {
            $ref = new ReflectionClass(EnumManager::class);
            $methods = ['forSelect', 'forApi', 'tryFromLabel', 'tryFromName',
                'hasCase', 'fromName', 'values', 'labels'];

            foreach ($methods as $methodName) {
                $method = $ref->getMethod($methodName);
                $returnType = $method->getReturnType();

                expect($returnType)->not->toBeNull(
                    "EnumManager::{$methodName}() must have a return type"
                );
            }
        });

        it('EnumCache methods all have explicit return types', function () {
            $ref = new ReflectionClass(EnumCache::class);
            $methods = ['getInstance', 'has', 'get', 'set', 'setTtl', 'getTtl',
                'clear', 'clearClass'];

            foreach ($methods as $methodName) {
                $method = $ref->getMethod($methodName);
                $returnType = $method->getReturnType();

                expect($returnType)->not->toBeNull(
                    "EnumCache::{$methodName}() must have a return type"
                );
            }
        });
    });

    // ── Typed Properties ──────────────────────────────────────────────────

    describe('typed properties', function () {
        it('EnumCache has all properties with declared types', function () {
            $ref = new ReflectionClass(EnumCache::class);

            $expectedProps = ['instance', 'cache', 'cacheTimestamps', 'ttl'];

            foreach ($expectedProps as $propName) {
                // Account for static vs instance properties
                $hasProp = false;
                foreach ($ref->getProperties() as $prop) {
                    if ($prop->getName() === $propName || $prop->getName() === strtolower($propName)) {
                        expect($prop->hasType())->toBeTrue(
                            "EnumCache::\${$prop->getName()} must have a declared type"
                        );
                        $hasProp = true;
                        break;
                    }
                }

                // instance is a static property
                if ($propName === 'instance') {
                    $staticProp = $ref->getProperty('instance');
                    expect($staticProp->hasType())->toBeTrue();
                    expect($staticProp->getType()->allowsNull())->toBeTrue();
                }
            }
        });

        it('EnumManager has no properties (stateless readonly class)', function () {
            $ref = new ReflectionClass(EnumManager::class);

            expect($ref->getProperties())->toHaveCount(0);
        });

        it('EnumRule properties are all typed', function () {
            $ref = new ReflectionClass(EnumRule::class);

            foreach ($ref->getProperties() as $prop) {
                expect($prop->hasType())->toBeTrue(
                    "EnumRule::\${$prop->getName()} must have a declared type"
                );
                expect($prop->isReadOnly())->toBeTrue(
                    "EnumRule::\${$prop->getName()} must be readonly"
                );
            }
        });
    });

    // ── Strict Types Enforcement ────────────────────────────────────────

    describe('strict types enforcement', function () {
        it('all source files declare strict_types=1', function () {
            $srcDir = dirname((new ReflectionClass(EnumManager::class))->getFileName());
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            $checked = 0;
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = $file->getContents();
                expect($contents)->toContain('declare(strict_types=1)',
                    "{$file->getFilename()} must declare strict_types=1");
                $checked++;
            }

            expect($checked)->toBeGreaterThan(0);
            expect($checked)->toBeGreaterThanOrEqual(20,
                "Expected at least 20 source files, found {$checked}");
        });
    });

    // ── Comparison Edge Cases ────────────────────────────────────────────

    describe('comparison edge cases', function () {
        it('is() rejects lowercase case names (case-sensitive)', function () {
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        });

        it('is() works with empty array for in()', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('notIn() returns true for empty array', function () {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });

        it('is() with mixed instances and names', function () {
            expect(UserStatus::ACTIVE->in([
                UserStatus::BANNED,
                'PENDING',
            ]))->toBeFalse();

            expect(UserStatus::ACTIVE->in([
                UserStatus::BANNED,
                'ACTIVE',
            ]))->toBeTrue();
        });

        it('forSelect returns consistent structure', function () {
            $select = UserStatus::forSelect();

            expect($select)->toBeArray();
            expect($select)->not->toBeEmpty();

            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
                expect($option['value'])->toBeString();
            }

            // Values must be unique
            $values = array_column($select, 'value');
            expect(array_unique($values))->toHaveCount(count($values));
        });

        it('forApi returns consistent structure with all keys', function () {
            $api = UserStatus::forApi();

            expect($api)->toBeArray();
            expect($api)->not->toBeEmpty();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });
    });

    // ── Int-Backed Enum Type Safety ─────────────────────────────────────

    describe('int-backed enum type safety', function () {
        it('values() returns integers only', function () {
            $values = IntBackedPriority::values();

            foreach ($values as $v) {
                expect(is_int($v))->toBeTrue();
            }
        });

        it('forSelect uses int values not strings', function () {
            $select = IntBackedPriority::forSelect();

            foreach ($select as $option) {
                expect(is_int($option['value']))->toBeTrue();
            }
        });

        it('forApi uses int values', function () {
            $api = IntBackedPriority::forApi();

            foreach ($api as $item) {
                expect(is_int($item['value']))->toBeTrue();
            }
        });

        it('comparison works with int-backed values', function () {
            expect(IntBackedPriority::HIGH->is(IntBackedPriority::HIGH))->toBeTrue();
            expect(IntBackedPriority::HIGH->is('HIGH'))->toBeTrue();
            expect(IntBackedPriority::HIGH->is('high'))->toBeFalse();
            expect(IntBackedPriority::HIGH->isNot('LOW'))->toBeTrue();
        });
    });

    // ── Pure Enum Type Safety ───────────────────────────────────────────

    describe('pure enum type safety', function () {
        it('values() returns case names as strings', function () {
            $values = PureFeatureFlag::values();

            foreach ($values as $v) {
                expect(is_string($v))->toBeTrue();
            }

            expect($values)->toContain('DARK_MODE');
            expect($values)->toContain('BETA_FEATURES');
        });

        it('forSelect uses case names as values', function () {
            $select = PureFeatureFlag::forSelect();

            foreach ($select as $option) {
                expect(is_string($option['value']))->toBeTrue();
            }

            // Case names are the values
            expect(array_column($select, 'value'))->toContain('DARK_MODE');
        });

        it('hasCase works correctly', function () {
            expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
            expect(PureFeatureFlag::hasCase('NONEXISTENT'))->toBeFalse();
        });

        it('tryFromName works correctly', function () {
            expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBeInstanceOf(PureFeatureFlag::class);
            expect(PureFeatureFlag::tryFromName('dark_mode'))->toBeNull();
        });
    });

    // ── Metadata Resolution Priority ─────────────────────────────────────

    describe('metadata resolution priority', function () {
        it('per-case label overrides class-level', function () {
            // ACTIVE has #[Label('Active User')]
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
        });

        it('auto-generated label when no attribute set', function () {
            // INACTIVE has no #[Label] and no class-level EnumLabel
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('per-case color overrides class-level', function () {
            // BANNED has #[Color('danger')]
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('class-level color used when no per-case override', function () {
            // ACTIVE is in EnumColor success group
            expect(UserStatus::ACTIVE->color())->toBe('success');
        });

        it('per-case icon is preserved', function () {
            expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
        });

        it('default color is secondary', function () {
            expect(PureFeatureFlag::MAINTENANCE_MODE->color())->toBe('secondary');
        });
    });

    // ── EnumRule Validation ────────────────────────────────────────────

    describe('EnumRule validation', function () {
        it('creates rule for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);

            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('creates nullable rule', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();

            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('creates rule for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);

            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('creates rule for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);

            expect($rule)->toBeInstanceOf(EnumRule::class);
        });
    });

    // ── EnumCache Behavior ────────────────────────────────────────────────

    describe('EnumCache behavior', function () {
        beforeEach(function () {
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::resetInstance();
        });

        it('singleton returns same instance', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('flush clears all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->set('TestEnum', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeTrue();
            EnumCache::flush();
            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('TTL of 0 disables caching', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);

            $cache->set('TestEnum', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('setTtl clamps negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-10);

            expect($cache->getTtl())->toBe(0);
        });

        it('clearClass only clears specified class', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(9999);

            $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('EnumB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clearClass('EnumA');

            expect($cache->has('EnumA'))->toBeFalse();
            expect($cache->has('EnumB'))->toBeTrue();
        });
    });

    // ── InvalidEnumException ──────────────────────────────────────────────

    describe('InvalidEnumException', function () {
        it('forName creates exception with class and name', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');

            expect($e)->toBeInstanceOf(InvalidEnumException::class);
            expect($e->getMessage())->toContain('UNKNOWN');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('value creates exception with class and value', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'invalid-value');

            expect($e)->toBeInstanceOf(InvalidEnumException::class);
            expect($e->getMessage())->toContain('invalid-value');
        });

        it('value handles null gracefully', function () {
            $e = InvalidEnumException::value(UserStatus::class, null);

            expect($e->getMessage())->toContain('null');
        });

        it('__toString returns class name and message', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'BOGUS');

            $str = (string) $e;
            expect($str)->toContain(InvalidEnumException::class);
            expect($str)->toContain('BOGUS');
        });
    });

    // ── Single-Case Enum ────────────────────────────────────────────────

    describe('single-case enum support', function () {
        it('single case enum has correct metadata', function () {
            expect(SingleCaseEnum::cases())->toHaveCount(1);
            expect(SingleCaseEnum::values())->toHaveCount(1);
            expect(SingleCaseEnum::labels())->toHaveCount(1);
            expect(SingleCaseEnum::forSelect())->toHaveCount(1);
        });
    });

    // ── Cross-Fixture Consistency ────────────────────────────────────────

    describe('cross-fixture consistency', function () {
        it('all fixtures use HasEnumMetadata trait', function () {
            $fixtures = [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class,
                SingleCaseEnum::class, PlainTestEnum::class];

            foreach ($fixtures as $fixtureClass) {
                $ref = new ReflectionClass($fixtureClass);
                expect($ref->hasMethod('label'))->toBeTrue("{$fixtureClass} must have label()");
                expect($ref->hasMethod('forSelect'))->toBeTrue("{$fixtureClass} must have forSelect()");
                expect($ref->hasMethod('values'))->toBeTrue("{$fixtureClass} must have values()");
            }
        });

        it('all backed enums produce unique values', function () {
            $backedEnums = [UserStatus::class, IntBackedPriority::class];

            foreach ($backedEnums as $enumClass) {
                $values = $enumClass::values();
                $unique = array_unique($values);
                expect($values)->toHaveCount(count($unique),
                    "{$enumClass} values must be unique"
                );
            }
        });

        it('all enums have at least one case', function () {
            $fixtures = [UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class,
                SingleCaseEnum::class];

            foreach ($fixtures as $fixtureClass) {
                expect($fixtureClass::cases())->not->toBeEmpty(
                    "{$fixtureClass} must have at least one case"
                );
            }
        });
    });
});
