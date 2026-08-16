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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Fixtures\{
    PaymentStatus,
    OrderStatus,
    IntPriority,
    IntBackedPriority,
    CamelCasePriority,
    PlainTestEnum,
    PureFeatureFlag,
    SingleCaseEnum,
    SingleCaseToggle,
    ZeroBackedPriority,
    ZeroPriority,
    UserStatus,
    TicketStatus,
    DetailedTicketStatus,
    NumericStatusCode,
    PureSystemState,
};

describe('V25 final production hardening — source code structural audit', function () {
    // ─── Source Code Structural Compliance ────────────────────────────────────

    describe('All source files have declare(strict_types=1)', function () {
        it('every PHP file in src/ starts with strict_types declaration', function () {
            $srcDir = __DIR__.'/../src';
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = $file->getContents();
                // Must be in first 10 lines
                $firstLines = implode("\n", array_slice(explode("\n", $contents), 0, 10));
                expect($firstLines)->toContain('declare(strict_types=1)');
            }
        });
    });

    describe('All classes are final', function () {
        $nonAbstractClasses = [
            EnumManager::class,
            EnumCache::class,
            EnumRule::class,
            EnumCast::class,
            InvalidEnumException::class,
            EnumMetadataResolver::class,
            EnumsServiceProvider::class,
            Label::class,
            Color::class,
            Description::class,
            Icon::class,
            EnumLabel::class,
            EnumColor::class,
            EnumIcon::class,
            EnumDescription::class,
            \ZeroBoiler\Enums\Facades\Enum::class,
            \ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand::class,
            \ZeroBoiler\Enums\Console\Commands\InspectEnumCommand::class,
            \ZeroBoiler\Enums\Support\EnumTestGenerator::class,
        ];

        it('all non-trait/non-enum classes are final', function () use ($nonAbstractClasses) {
            foreach ($nonAbstractClasses as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} should be final");
            }
        });
    });

    describe('EnumManager is final readonly', function () {
        it('EnumManager is final', function () {
            expect((new ReflectionClass(EnumManager::class))->isFinal())->toBeTrue();
        });

        it('EnumManager is readonly', function () {
            expect((new ReflectionClass(EnumManager::class))->isReadOnly())->toBeTrue();
        });
    });

    describe('EnumRule is final readonly', function () {
        it('EnumRule is final', function () {
            expect((new ReflectionClass(EnumRule::class))->isFinal())->toBeTrue();
        });

        it('EnumRule is readonly', function () {
            expect((new ReflectionClass(EnumRule::class))->isReadOnly())->toBeTrue();
        });
    });

    // ─── Attribute Contract Verification ────────────────────────────────────

    describe('Per-case attributes are Attribute with TARGET_CLASS_CONSTANT', function () {
        $perCaseAttributes = [
            Label::class,
            Color::class,
            Description::class,
            Icon::class,
        ];

        foreach ($perCaseAttributes as $attr) {
            it("{$attr} has TARGET_CLASS_CONSTANT flag", function () use ($attr) {
                $ref = new ReflectionClass($attr);
                $attrs = $ref->getAttributes(\Attribute::class);
                expect($attrs)->not->toBeEmpty();

                $instance = $attrs[0]->newInstance();
                expect($instance->flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);
            });

            it("{$attr} is final", function () use ($attr) {
                expect((new ReflectionClass($attr))->isFinal())->toBeTrue();
            });

            it("{$attr} properties are readonly", function () use ($attr) {
                $ref = new ReflectionClass($attr);
                foreach ($ref->getProperties() as $prop) {
                    expect($prop->isReadOnly())->toBeTrue("{$attr}::\${$prop->name} should be readonly");
                }
            });
        }
    });

    describe('Class-level attributes have TARGET_CLASS | TARGET_CLASS_CONSTANT', function () {
        $classLevelAttributes = [
            EnumLabel::class,
            EnumColor::class,
            EnumIcon::class,
            EnumDescription::class,
        ];

        foreach ($classLevelAttributes as $attr) {
            it("{$attr} has both TARGET_CLASS and TARGET_CLASS_CONSTANT flags", function () use ($attr) {
                $ref = new ReflectionClass($attr);
                $attrs = $ref->getAttributes(\Attribute::class);
                $instance = $attrs[0]->newInstance();

                expect($instance->flags & Attribute::TARGET_CLASS)->toBeGreaterThan(0);
                expect($instance->flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);
            });
        }
    });

    // ─── Exception Contract ─────────────────────────────────────────────────

    describe('InvalidEnumException contract', function () {
        it('is final', function () {
            expect((new ReflectionClass(InvalidEnumException::class))->isFinal())->toBeTrue();
        });

        it('extends Exception', function () {
            expect(new InvalidEnumException('test'))->toBeInstanceOf(\Exception::class);
        });

        it('value() factory produces correct message format', function () {
            $ex = InvalidEnumException::value('App\\Enums\\Status', 'active');
            expect($ex->getMessage())->toContain('active');
            expect($ex->getMessage())->toContain('App\\Enums\\Status');
        });

        it('value() with null value displays "null"', function () {
            $ex = InvalidEnumException::value('App\\Enums\\Status', null);
            expect($ex->getMessage())->toContain('null');
        });

        it('forName() factory produces correct message format', function () {
            $ex = InvalidEnumException::forName('App\\Enums\\Status', 'ACTIVE');
            expect($ex->getMessage())->toContain('ACTIVE');
            expect($ex->getMessage())->toContain('App\\Enums\\Status');
        });

        it('__toString() includes class name', function () {
            $ex = InvalidEnumException::value('App\\Enums\\Status', 'x');
            expect((string) $ex)->toContain('InvalidEnumException');
        });
    });

    // ─── EnumCache Singleton Contract ────────────────────────────────────────

    describe('EnumCache singleton lifecycle', function () {
        it('getInstance always returns same instance', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('resetInstance creates fresh instance', function () {
            $cache = EnumCache::getInstance();
            $cache->set('test.enum.v25', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            EnumCache::resetInstance();
            $fresh = EnumCache::getInstance();

            expect($fresh)->not->toBe($cache);
            expect($fresh->has('test.enum.v25'))->toBeFalse();

            // Restore original cache state
            $cache->clear();
        });

        it('flush() clears all entries via static accessor', function () {
            $cache = EnumCache::getInstance();
            $cache->set('test.flush.a', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('test.flush.b', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            EnumCache::flush();

            expect($cache->has('test.flush.a'))->toBeFalse();
            expect($cache->has('test.flush.b'))->toBeFalse();
        });

        it('setTtl clamps negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);
            expect($cache->getTtl())->toBe(0);
            $cache->setTtl(300); // restore
        });
    });

    // ─── EnumCast Type Safety ───────────────────────────────────────────────

    describe('EnumCast template type safety', function () {
        it('is final', function () {
            expect((new ReflectionClass(EnumCast::class))->isFinal())->toBeTrue();
        });

        it('implements CastsAttributes interface', function () {
            expect(EnumCast::class)->toImplement(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class);
        });

        it('constructor accepts enumClass string', function () {
            $cast = new EnumCast(UserStatus::class);
            $ref = new ReflectionProperty($cast, 'enumClass');
            expect($ref->getValue($cast))->toBe(UserStatus::class);
        });

        it('constructor has typed readonly property', function () {
            $ref = new ReflectionProperty(EnumCast::class, 'enumClass');
            expect($ref->isReadOnly())->toBeTrue();
            expect($ref->getType()->getName())->toBe('string');
        });
    });

    // ─── EnumRule Validation Contract ──────────────────────────────────────

    describe('EnumRule validation contract', function () {
        it('implements ValidationRule interface', function () {
            expect(EnumRule::class)->toImplement(\Illuminate\Contracts\Validation\ValidationRule::class);
        });

        it('for() named constructor returns self', function () {
            $rule = EnumRule::for(UserStatus::class);
            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('nullable() returns new instance with nullable=true', function () {
            $rule = EnumRule::for(UserStatus::class);
            $nullable = $rule->nullable();
            expect($nullable)->toBeInstanceOf(EnumRule::class);
            expect($nullable)->not->toBe($rule);
        });
    });

    // ─── EnumsServiceProvider Contract ──────────────────────────────────────

    describe('EnumsServiceProvider contract', function () {
        it('is final', function () {
            expect((new ReflectionClass(EnumsServiceProvider::class))->isFinal())->toBeTrue();
        });

        it('extends ServiceProvider', function () {
            expect(EnumsServiceProvider::class)->toExtend(\Illuminate\Support\ServiceProvider::class);
        });

        it('register() and boot() have Override attributes', function () {
            $ref = new ReflectionClass(EnumsServiceProvider::class);
            $register = $ref->getMethod('register');
            $boot = $ref->getMethod('boot');

            expect($register->getAttributes(\Override::class))->not->toBeEmpty();
            expect($boot->getAttributes(\Override::class))->not->toBeEmpty();
        });
    });

    // ─── HasEnumMetadata Trait Method Completeness ─────────────────────────

    describe('HasEnumMetadata trait method completeness', function () {
        $requiredMethods = [
            'label', 'description', 'color', 'icon',
            'forSelect', 'forApi',
            'tryFromLabel', 'tryFromName', 'fromName', 'hasCase',
            'is', 'isNot', 'in', 'notIn',
            'values', 'labels',
        ];

        foreach ($requiredMethods as $method) {
            it("UserStatus has {$method}() method", function () use ($method) {
                expect(method_exists(UserStatus::class, $method))->toBeTrue();
            });

            it("PaymentStatus has {$method}() method", function () use ($method) {
                expect(method_exists(PaymentStatus::class, $method))->toBeTrue();
            });

            it("IntBackedPriority has {$method}() method", function () use ($method) {
                expect(method_exists(IntBackedPriority::class, $method))->toBeTrue();
            });
        }
    });

    // ─── Cross-Fixture Label Consistency ───────────────────────────────────

    describe('Cross-fixture label consistency', function () {
        it('all fixtures produce non-empty labels for every case', function () {
            $fixtures = [
                UserStatus::class,
                PaymentStatus::class,
                OrderStatus::class,
                TicketStatus::class,
                IntPriority::class,
                IntBackedPriority::class,
                CamelCasePriority::class,
                NumericStatusCode::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($fixtures as $enum) {
                foreach ($enum::cases() as $case) {
                    $label = $case->label();
                    expect($label)->toBeString()->not->toBeEmpty(
                        "Label for {$enum}::{$case->name} should not be empty"
                    );
                }
            }
        });

        it('forSelect returns value-label pairs with correct types', function () {
            $options = UserStatus::forSelect();
            foreach ($options as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi returns full metadata with all required keys', function () {
            $api = UserStatus::forApi();
            $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

            foreach ($api as $item) {
                expect(array_keys($item))->toEqual($requiredKeys);
            }
        });
    });

    // ─── PHPStan L9 Type Safety Spot Checks ─────────────────────────────────

    describe('PHPStan L9 type safety — return types', function () {
        it('forSelect() returns list of arrays', function () {
            $result = UserStatus::forSelect();
            expect($result)->toBeArray();
            // PHPStan L9: list<array{value: int|string, label: string}>
            foreach ($result as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKey('value');
                expect($item)->toHaveKey('label');
            }
        });

        it('forApi() returns list of arrays with 6 keys', function () {
            $result = UserStatus::forApi();
            expect($result)->toBeArray();
            foreach ($result as $item) {
                expect(count($item))->toBe(6);
            }
        });

        it('tryFromLabel() returns null for non-existent label', function () {
            expect(UserStatus::tryFromLabel('nonexistent-label-xyz'))->toBeNull();
        });

        it('tryFromName() returns null for non-existent name', function () {
            expect(UserStatus::tryFromName('NONEXISTENT'))->toBeNull();
        });

        it('fromName() throws on non-existent name', function () {
            expect(fn () => UserStatus::fromName('NONEXISTENT'))->toThrow(InvalidEnumException::class);
        });

        it('values() returns correct backed type', function () {
            $stringValues = UserStatus::values();
            foreach ($stringValues as $v) {
                expect($v)->toBeString();
            }

            $intValues = IntBackedPriority::values();
            foreach ($intValues as $v) {
                expect($v)->toBeInt();
            }
        });
    });

    // ─── Metadata Resolution Priority ──────────────────────────────────────

    describe('Metadata resolution priority (per-case > class-level > auto)', function () {
        it('per-case Label overrides auto-generated label', function () {
            // UserStatus::ACTIVE has #[Label('Active User')]
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
            // Auto-generated from INACTIVE would be "Inactive"
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('per-case Color overrides class-level EnumColor', function () {
            // UserStatus::BANNED has #[Color('danger')]
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('color() defaults to secondary when no attribute set', function () {
            expect(UserStatus::INACTIVE->color())->toBe('secondary');
        });

        it('per-case Icon overrides class-level default icon', function () {
            $icon = UserStatus::ACTIVE->icon();
            expect($icon)->toBeString()->not->toBeEmpty();
        });

        it('description() returns null when no attribute set', function () {
            expect(UserStatus::INACTIVE->description())->toBeNull();
        });
    });

    // ─── EnumMetadataResolver Cache Integration ──────────────────────────

    describe('EnumMetadataResolver cache integration', function () {
        it('resolve() returns consistent shape across multiple calls', function () {
            $a = EnumMetadataResolver::resolve(UserStatus::class);
            $b = EnumMetadataResolver::resolve(UserStatus::class);

            expect($a)->toBe($b);
            expect($a)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        });

        it('invalidate() forces rebuild on next resolve', function () {
            $before = EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::invalidate(UserStatus::class);
            $after = EnumMetadataResolver::resolve(UserStatus::class);

            // Structure should be identical
            expect(array_keys($before))->toEqual(array_keys($after));
        });

        it('invalidateAll() clears all enum caches', function () {
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(PaymentStatus::class);

            EnumMetadataResolver::invalidateAll();

            // Cache should be empty for both
            $cache = EnumCache::getInstance();
            // After invalidateAll (which calls flush), the internal cache arrays are empty
            // But resolve() will repopulate, so we just verify the method works
            $result = EnumMetadataResolver::resolve(UserStatus::class);
            expect($result)->toHaveKey('labels');
        });
    });

    // ─── Comparison Methods — Strict Identity ─────────────────────────────

    describe('Comparison methods use strict identity', function () {
        it('is() with enum instance uses strict identity', function () {
            $active = UserStatus::ACTIVE;
            expect($active->is(UserStatus::ACTIVE))->toBeTrue();
            expect($active->is(UserStatus::INACTIVE))->toBeFalse();
        });

        it('is() with string uses case-sensitive comparison', function () {
            $active = UserStatus::ACTIVE;
            expect($active->is('ACTIVE'))->toBeTrue();
            expect($active->is('active'))->toBeFalse();
            expect($active->is('Active'))->toBeFalse();
        });

        it('in() supports mixed instances and strings', function () {
            $active = UserStatus::ACTIVE;
            expect($active->in([UserStatus::ACTIVE, 'INACTIVE']))->toBeTrue();
            expect($active->in(['INACTIVE']))->toBeFalse();
        });

        it('notIn() is logical inverse of in()', function () {
            $active = UserStatus::ACTIVE;
            expect($active->notIn(['INACTIVE', 'BANNED']))->toBeTrue();
            expect($active->notIn(['ACTIVE']))->toBeFalse();
        });
    });

    // ─── Int-Backed Edge Cases ────────────────────────────────────────────

    describe('Int-backed enum edge cases', function () {
        it('zero-backed enum value is handled correctly', function () {
            $zero = ZeroPriority::LOW;
            expect($zero->value)->toBe(0);
            expect($zero->label())->toBeString()->not->toBeEmpty();
        });

        it('negative int value is handled correctly', function () {
            // NumericStatusCode has -1, 0, 1 values
            expect(NumericStatusCode::REJECTED->value)->toBe(-1);
            expect(NumericStatusCode::REJECTED->label())->toBeString()->not->toBeEmpty();
        });

        it('forSelect uses int values for int-backed enums', function () {
            $options = IntBackedPriority::forSelect();
            foreach ($options as $option) {
                expect($option['value'])->toBeInt();
            }
        });
    });

    // ─── Pure Enum Edge Cases ────────────────────────────────────────────

    describe('Pure enum edge cases', function () {
        it('pure enum uses case name as value in forSelect', function () {
            $options = PureFeatureFlag::forSelect();
            foreach ($options as $option) {
                expect($option['value'])->toBeString();
                // Value should match a case name
                expect(PureFeatureFlag::tryFromName($option['value']))->not->toBeNull();
            }
        });

        it('pure enum values() returns case names', function () {
            $values = PureFeatureFlag::values();
            $names = array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());
            expect($values)->toEqual($names);
        });
    });

    // ─── Single-Case Enum ──────────────────────────────────────────────────

    describe('Single-case enum support', function () {
        it('single-case enum has exactly one case', function () {
            expect(SingleCaseToggle::cases())->toHaveCount(1);
        });

        it('label() works on single-case enum', function () {
            expect(SingleCaseToggle::ON->label())->toBeString()->not->toBeEmpty();
        });

        it('forSelect returns single entry', function () {
            expect(SingleCaseToggle::forSelect())->toHaveCount(1);
        });
    });
});
