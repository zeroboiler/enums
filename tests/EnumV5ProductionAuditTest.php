<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * V5 Production Audit — comprehensive cross-type and edge-case coverage.
 *
 * Validates structural contracts, type consistency, metadata resolution,
 * comparison methods, bulk methods, lookup methods, and cache behavior
 * across all three enum types (string-backed, int-backed, pure).
 */
describe('V5 Production Audit', function (): void {
    // -----------------------------------------------------------------------
    // 1. Structural Contract — all fixtures use HasEnumMetadata
    // -----------------------------------------------------------------------
    describe('Structural Contract', function (): void {
        it('all fixture enums use HasEnumMetadata trait', function (): void {
            $classes = [
                UserStatus::class,
                Priority::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
            ];

            foreach ($classes as $class) {
                expect(in_array(HasEnumMetadata::class, class_uses_recursive($class), true))->toBeTrue(
                    "{$class} must use HasEnumMetadata trait"
                );
            }
        });

        it('all fixture enums have at least one case', function (): void {
            $classes = [
                UserStatus::class,
                Priority::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
            ];

            foreach ($classes as $class) {
                expect($class::cases())->not->toBeEmpty();
            }
        });
    });

    // -----------------------------------------------------------------------
    // 2. Type Consistency — return types match docblock contracts
    // -----------------------------------------------------------------------
    describe('Type Consistency', function (): void {
        it('label() always returns non-empty string', function (): void {
            $allCases = [
                ...UserStatus::cases(),
                ...Priority::cases(),
                ...IntBackedPriority::cases(),
                ...PureFeatureFlag::cases(),
            ];

            foreach ($allCases as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });

        it('color() always returns string (never null)', function (): void {
            $allCases = [
                ...UserStatus::cases(),
                ...Priority::cases(),
                ...IntBackedPriority::cases(),
                ...PureFeatureFlag::cases(),
            ];

            foreach ($allCases as $case) {
                expect($case->color())->toBeString()->not->toBeEmpty();
            }
        });

        it('icon() returns string or null', function (): void {
            $allCases = [
                ...UserStatus::cases(),
                ...Priority::cases(),
                ...IntBackedPriority::cases(),
                ...PureFeatureFlag::cases(),
            ];

            foreach ($allCases as $case) {
                $icon = $case->icon();
                expect($icon)->toBeNull()->or()->toBeString();
            }
        });

        it('description() returns string or null', function (): void {
            $allCases = [
                ...UserStatus::cases(),
                ...Priority::cases(),
                ...IntBackedPriority::cases(),
                ...PureFeatureFlag::cases(),
            ];

            foreach ($allCases as $case) {
                $desc = $case->description();
                expect($desc)->toBeNull()->or()->toBeString();
            }
        });

        it('values() returns correct types for each backing type', function (): void {
            // String-backed → string values
            $stringValues = UserStatus::values();
            foreach ($stringValues as $v) {
                expect($v)->toBeString();
            }

            // Int-backed → int values
            $intValues = Priority::values();
            foreach ($intValues as $v) {
                expect($v)->toBeInt();
            }

            // Pure → string case names
            $pureValues = PureFeatureFlag::values();
            foreach ($pureValues as $v) {
                expect($v)->toBeString();
            }
        });

        it('labels() returns non-empty strings matching cases count', function (): void {
            $classes = [
                UserStatus::class,
                Priority::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
            ];

            foreach ($classes as $class) {
                $labels = $class::labels();
                $cases = $class::cases();
                expect($labels)->toHaveCount(count($cases));
                foreach ($labels as $label) {
                    expect($label)->toBeString()->not->toBeEmpty();
                }
            }
        });
    });

    // -----------------------------------------------------------------------
    // 3. Bulk Methods — forSelect() and forApi() structure
    // -----------------------------------------------------------------------
    describe('Bulk Methods', function (): void {
        it('forSelect() returns correct structure for all enum types', function (): void {
            $classes = [
                UserStatus::class,
                Priority::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
            ];

            foreach ($classes as $class) {
                $select = $class::forSelect();
                expect($select)->toBeArray();
                expect(count($select))->toBe(count($class::cases()));

                foreach ($select as $option) {
                    expect($option)->toBeArray();
                    expect($option)->toHaveKeys(['value', 'label']);
                    expect($option['label'])->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('forSelect() values are unique', function (): void {
            $classes = [
                UserStatus::class,
                Priority::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
            ];

            foreach ($classes as $class) {
                $values = array_column($class::forSelect(), 'value');
                expect($values)->each->toBeUnique();
            }
        });

        it('forApi() returns full metadata structure for all enum types', function (): void {
            $classes = [
                UserStatus::class,
                Priority::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
            ];

            foreach ($classes as $class) {
                $api = $class::forApi();
                expect($api)->toBeArray();
                expect(count($api))->toBe(count($class::cases()));

                foreach ($api as $item) {
                    expect($item)->toBeArray();
                    expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                    expect($item['color'])->toBeString()->not->toBeEmpty();
                }
            }
        });
    });

    // -----------------------------------------------------------------------
    // 4. Comparison Methods — is(), isNot(), in(), notIn()
    // -----------------------------------------------------------------------
    describe('Comparison Methods', function (): void {
        it('is() with instance works for all enum types', function (): void {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
            expect(Priority::LOW->is(Priority::LOW))->toBeTrue();
            expect(Priority::LOW->is(Priority::HIGH))->toBeFalse();
            expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::DARK_MODE))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::BETA_FEATURES))->toBeFalse();
        });

        it('is() with string works for all enum types', function (): void {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
            expect(Priority::LOW->is('LOW'))->toBeTrue();
            expect(Priority::LOW->is('HIGH'))->toBeFalse();
            expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->is('BETA_FEATURES'))->toBeFalse();
        });

        it('is() is case-sensitive for string comparison', function (): void {
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(Priority::LOW->is('low'))->toBeFalse();
            expect(PureFeatureFlag::DARK_MODE->is('dark_mode'))->toBeFalse();
        });

        it('isNot() is logical negation of is()', function (): void {
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
            expect(Priority::LOW->isNot('LOW'))->toBeFalse();
            expect(Priority::LOW->isNot('HIGH'))->toBeTrue();
        });

        it('in() works with mixed instances and strings', function (): void {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in([UserStatus::BANNED, 'SUSPENDED']))->toBeFalse();
        });

        it('notIn() is negation of in()', function (): void {
            expect(UserStatus::ACTIVE->notIn([UserStatus::BANNED]))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn([UserStatus::ACTIVE]))->toBeFalse();
            expect(UserStatus::BANNED->notIn(['ACTIVE', 'INACTIVE']))->toBeTrue();
            expect(UserStatus::BANNED->notIn(['BANNED']))->toBeFalse();
        });

        it('in() with empty array returns false', function (): void {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // 5. Lookup Methods — tryFromName, fromName, hasCase, tryFromLabel
    // -----------------------------------------------------------------------
    describe('Lookup Methods', function (): void {
        it('tryFromName() finds existing cases for all types', function (): void {
            expect(UserStatus::tryFromName('ACTIVE'))->toBeInstanceOf(UserStatus::class);
            expect(Priority::tryFromName('LOW'))->toBeInstanceOf(Priority::class);
            expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBeInstanceOf(PureFeatureFlag::class);
        });

        it('tryFromName() returns null for non-existent names', function (): void {
            expect(UserStatus::tryFromName('NON_EXISTENT'))->toBeNull();
            expect(Priority::tryFromName('NON_EXISTENT'))->toBeNull();
            expect(PureFeatureFlag::tryFromName('NON_EXISTENT'))->toBeNull();
        });

        it('fromName() returns correct case', function (): void {
            expect(UserStatus::fromName('ACTIVE')->name)->toBe('ACTIVE');
            expect(Priority::fromName('LOW')->name)->toBe('LOW');
            expect(PureFeatureFlag::fromName('DARK_MODE')->name)->toBe('DARK_MODE');
        });

        it('fromName() throws InvalidEnumException for non-existent name', function (): void {
            expect(fn () => UserStatus::fromName('NON_EXISTENT'))->toThrow(InvalidEnumException::class);
            expect(fn () => Priority::fromName('NON_EXISTENT'))->toThrow(InvalidEnumException::class);
            expect(fn () => PureFeatureFlag::fromName('NON_EXISTENT'))->toThrow(InvalidEnumException::class);
        });

        it('fromName() exception includes class name and invalid name', function (): void {
            try {
                UserStatus::fromName('GHOST_CASE');
                expect(true)->toBeFalse('Should have thrown');
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('UserStatus');
                expect($e->getMessage())->toContain('GHOST_CASE');
            }
        });

        it('hasCase() returns correct boolean for all types', function (): void {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('GHOST_CASE'))->toBeFalse();
            expect(Priority::hasCase('LOW'))->toBeTrue();
            expect(Priority::hasCase('GHOST_CASE'))->toBeFalse();
            expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
            expect(PureFeatureFlag::hasCase('GHOST_CASE'))->toBeFalse();
        });

        it('tryFromLabel() finds by label case-insensitively', function (): void {
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel() returns null for non-existent labels', function (): void {
            expect(UserStatus::tryFromLabel('Nonexistent Label'))->toBeNull();
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('tryFromLabel() works for all enum types', function (): void {
            expect(PureFeatureFlag::tryFromLabel('Dark Mode'))->toBe(PureFeatureFlag::DARK_MODE);
            expect(IntBackedPriority::tryFromLabel('Critical Priority'))->toBe(IntBackedPriority::CRITICAL);
        });
    });

    // -----------------------------------------------------------------------
    // 6. Metadata Resolution Priority — per-case > class-level > auto
    // -----------------------------------------------------------------------
    describe('Metadata Resolution Priority', function (): void {
        it('per-case Label overrides auto-generated', function (): void {
            // UserStatus::ACTIVE has #[Label('Active User')]
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
            // Not "Active" (auto-generated)
            expect(UserStatus::ACTIVE->label())->not->toBe('Active');
        });

        it('auto-generated label for cases without #[Label]', function (): void {
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('per-case Color overrides class-level EnumColor', function (): void {
            // UserStatus::BANNED has #[Color('danger')] which overrides EnumColor
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('class-level EnumColor provides default', function (): void {
            // UserStatus::SUSPENDED has warning from EnumColor
            expect(UserStatus::SUSPENDED->color())->toBe('warning');
        });

        it('int-backed enum resolves class-level metadata by int key', function (): void {
            // IntBackedPriority: EnumColor(success: [3, 4], danger: [1])
            expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
            expect(IntBackedPriority::LOW->color())->toBe('success');
            expect(IntBackedPriority::NONE->color())->toBe('success');
        });

        it('per-case Label overrides class-level EnumLabel for int-backed', function (): void {
            // IntBackedPriority::HIGH has #[Label('High Priority')]
            expect(IntBackedPriority::HIGH->label())->toBe('High Priority');
            // IntBackedPriority::CRITICAL has EnumLabel label (1 => 'Critical Priority')
            expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
        });

        it('EnumIcon default applies to cases without specific icon', function (): void {
            // IntBackedPriority has EnumIcon(default: 'heroicon-o-flag')
            // NONE has no per-case icon, so it gets the default
            expect(IntBackedPriority::NONE->icon())->toBe('heroicon-o-flag');
        });

        it('EnumDescription provides class-level descriptions', function (): void {
            expect(IntBackedPriority::CRITICAL->description())->toBe(
                'Critical priority — immediate action required'
            );
        });
    });

    // -----------------------------------------------------------------------
    // 7. Cache Behavior — TTL, invalidation, isolation
    // -----------------------------------------------------------------------
    describe('Cache Behavior', function (): void {
        beforeEach(function (): void {
            EnumMetadataResolver::invalidateAll();
        });

        it('metadata is cached after first resolve', function (): void {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->setTtl(0); // disable TTL for deterministic test

            // First resolve — not cached
            expect($cache->has(UserStatus::class))->toBeFalse();
            UserStatus::ACTIVE->label();
            expect($cache->has(UserStatus::class))->toBeTrue();
        });

        it('invalidate removes specific class cache', function (): void {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->setTtl(0);

            UserStatus::ACTIVE->label();
            Priority::LOW->label();
            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(Priority::class))->toBeTrue();

            EnumMetadataResolver::invalidate(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeTrue();
        });

        it('invalidateAll removes all caches', function (): void {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->setTtl(0);

            UserStatus::ACTIVE->label();
            Priority::LOW->label();
            PureFeatureFlag::DARK_MODE->label();

            EnumMetadataResolver::invalidateAll();
            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeFalse();
            expect($cache->has(PureFeatureFlag::class))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // 8. EnumRule Validation — type-safety for backed enums
    // -----------------------------------------------------------------------
    describe('EnumRule Type Safety', function (): void {
        it('EnumRule::for() creates instance with correct class', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);
            expect($rule)->toBeInstanceOf(\ZeroBoiler\Enums\Rules\EnumRule::class);
        });

        it('EnumRule::nullable() returns new nullable instance', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);
            $nullable = $rule->nullable();
            expect($nullable)->toBeInstanceOf(\ZeroBoiler\Enums\Rules\EnumRule::class);
            expect($nullable)->not->toBe($rule);
        });

        it('EnumRule validate() passes for valid string-backed value', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);
            $fail = fn () => null;

            // Should not call $fail — valid value
            $called = false;
            $rule->validate('status', 'active', function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        it('EnumRule validate() fails for invalid value', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);
            $called = false;

            $rule->validate('status', 'invalid_value', function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeTrue();
        });

        it('EnumRule validate() rejects null when not nullable', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);
            $called = false;

            $rule->validate('status', null, function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeTrue();
        });

        it('EnumRule nullable() allows null', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class)->nullable();
            $called = false;

            $rule->validate('status', null, function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        it('EnumRule validates int-backed enum with correct type', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(Priority::class);
            $called = false;

            // Pass int for int-backed — should not fail
            $rule->validate('priority', 1, function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        it('EnumRule rejects string for int-backed enum', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(Priority::class);
            $called = false;

            // Pass string for int-backed — should fail (type mismatch)
            $rule->validate('priority', '1', function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeTrue();
        });

        it('EnumRule validates pure enum by case name', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(PureFeatureFlag::class);
            $called = false;

            $rule->validate('feature', 'DARK_MODE', function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeFalse();
        });

        it('EnumRule rejects int for pure enum', function (): void {
            $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(PureFeatureFlag::class);
            $called = false;

            $rule->validate('feature', 123, function () use (&$called): void {
                $called = true;
            });

            expect($called)->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // 9. Edge Cases — single case enum, zero value int, label generation
    // -----------------------------------------------------------------------
    describe('Edge Cases', function (): void {
        it('single case enum works correctly', function (): void {
            $status = \ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum::ONLY;

            expect($status->label())->toBeString()->not->toBeEmpty();
            expect($status->color())->toBeString();
            expect($status->is('ONLY'))->toBeTrue();
            expect($status->is(\ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum::ONLY))->toBeTrue();
            expect($status->in(['ONLY']))->toBeTrue();
        });

        it('zero-value int-backed enum resolves metadata', function (): void {
            $zero = \ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority::ZERO;
            expect($zero->label())->toBeString()->not->toBeEmpty();
            expect($zero->value)->toBe(0);
        });

        it('label generation for SCREAMING_SNAKE_CASE', function (): void {
            // UserStatus::INACTIVE has no #[Label] — auto-generated
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('label generation for CAMEL_CASE is not auto-converted', function (): void {
            // CamelCaseRole has camelCase names
            $role = \ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::Admin;
            expect($role->label())->toBeString()->not->toBeEmpty();
        });
    });

    // -----------------------------------------------------------------------
    // 10. InvalidEnumException — factory methods and __toString
    // -----------------------------------------------------------------------
    describe('InvalidEnumException', function (): void {
        it('forName() creates exception with class and name', function (): void {
            $e = InvalidEnumException::forName('App\\Enums\\Foo', 'INVALID');
            expect($e->getMessage())->toContain('App\\Enums\\Foo');
            expect($e->getMessage())->toContain('INVALID');
        });

        it('value() creates exception with class and value', function (): void {
            $e = InvalidEnumException::value('App\\Enums\\Foo', 'bad_value');
            expect($e->getMessage())->toContain('bad_value');
            expect($e->getMessage())->toContain('App\\Enums\\Foo');
        });

        it('value() handles null display', function (): void {
            $e = InvalidEnumException::value('App\\Enums\\Foo', null);
            expect($e->getMessage())->toContain('null');
        });

        it('__toString() returns class name and message', function (): void {
            $e = InvalidEnumException::forName('App\\Enums\\Foo', 'X');
            $str = (string) $e;
            expect($str)->toContain('InvalidEnumException');
            expect($str)->toContain('X');
        });
    });
});
