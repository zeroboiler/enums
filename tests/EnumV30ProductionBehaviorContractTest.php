<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\NumericStatusCode;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('V30 Production Behavior Contract', function () {
    // ── String-Backed Enum Real-World Scenarios ─────────────────────────────

    describe('string-backed enum production scenarios', function () {
        it('forSelect preserves declaration order', function () {
            $options = UserStatus::forSelect();
            $values = array_column($options, 'value');

            // UserStatus cases in declaration order: BANNED, ACTIVE, PENDING, SUSPENDED
            expect($values)->toEqual(['banned', 'active', 'pending', 'suspended']);
        });

        it('forApi returns complete metadata shape for every case', function () {
            $api = UserStatus::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString()->not->toBeEmpty();
                // description can be null but icon can be null
                expect($item['description'])->toBeNull()->or()->toBeString();
                expect($item['icon'])->toBeNull()->or()->toBeString();
            }
        });

        it('forSelect values match values() output', function () {
            $selectValues = array_column(UserStatus::forSelect(), 'value');
            expect($selectValues)->toEqual(UserStatus::values());
        });

        it('labels() count matches case count', function () {
            expect(UserStatus::labels())->toHaveCount(count(UserStatus::cases()));
        });

        it('comparison methods work with both instances and strings consistently', function () {
            $active = UserStatus::ACTIVE;

            expect($active->is(UserStatus::ACTIVE))->toBeTrue();
            expect($active->is('ACTIVE'))->toBeTrue();
            expect($active->isNot('BANNED'))->toBeTrue();
            expect($active->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
            expect($active->in(['BANNED', 'SUSPENDED']))->toBeFalse();
            expect($active->notIn(['BANNED', 'SUSPENDED']))->toBeTrue();
            expect($active->notIn(['ACTIVE']))->toBeFalse();
        });

        it('tryFromLabel is case-insensitive and finds auto-generated labels', function () {
            $case = UserStatus::tryFromLabel('active');
            expect($case)->toBe(UserStatus::ACTIVE);

            $case = UserStatus::tryFromLabel('ACTIVE');
            expect($case)->toBe(UserStatus::ACTIVE);

            $case = UserStatus::tryFromLabel('Active');
            expect($case)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel returns null for non-existent labels', function () {
            expect(UserStatus::tryFromLabel('nonexistent-label'))->toBeNull();
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('fromName throws with descriptive message for invalid names', function () {
            expect(fn () => UserStatus::fromName('NON_EXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns correct boolean for existing and non-existing names', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('active'))->toBeFalse(); // case-sensitive
            expect(UserStatus::hasCase('NON_EXISTENT'))->toBeFalse();
        });
    });

    // ── Int-Backed Enum Real-World Scenarios ────────────────────────────────

    describe('int-backed enum production scenarios', function () {
        it('values() returns int values not case names', function () {
            $values = IntBackedPriority::values();
            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('forSelect uses backed value as value key', function () {
            $options = IntBackedPriority::forSelect();
            $values = array_column($options, 'value');

            expect($values)->toEqual(IntBackedPriority::values());
            // All values should be int
            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('forApi includes int backed values correctly', function () {
            $api = IntBackedPriority::forApi();
            foreach ($api as $item) {
                expect($item['value'])->toBeInt();
                expect($item['name'])->toBeString();
            }
        });

        it('handles zero-valued backed enums correctly', function () {
            $zero = ZeroPriority::LOW;
            expect($zero->value)->toBe(0);
            expect($zero->label())->toBeString()->not->toBeEmpty();

            // forSelect should include zero value
            $options = ZeroPriority::forSelect();
            $values = array_column($options, 'value');
            expect(in_array(0, $values, true))->toBeTrue();
        });

        it('numeric string backed values are handled in lookup', function () {
            $case = NumericStatusCode::tryFromName('OK');
            expect($case)->not->toBeNull();
            expect($case->value)->toBe(200);
        });
    });

    // ── Pure Enum Real-World Scenarios ─────────────────────────────────────

    describe('pure enum production scenarios', function () {
        it('values() returns case names for pure enums', function () {
            $values = PureFeatureFlag::values();
            expect($values)->toEqual(['ENABLED', 'DISABLED']);
        });

        it('forSelect uses case names as values', function () {
            $options = PureFeatureFlag::forSelect();
            $values = array_column($options, 'value');
            expect($values)->toEqual(['ENABLED', 'DISABLED']);
        });

        it('color defaults to secondary when not configured', function () {
            // PureFeatureFlag has no color attributes
            expect(PureFeatureFlag::ENABLED->color())->toBe('secondary');
        });

        it('description returns null when not configured', function () {
            expect(PureFeatureFlag::ENABLED->description())->toBeNull();
        });

        it('icon returns null when not configured', function () {
            expect(PureFeatureFlag::ENABLED->icon())->toBeNull();
        });

        it('single-case enum works correctly', function () {
            expect(SingleCaseToggle::cases())->toHaveCount(1);
            expect(SingleCaseToggle::ON->label())->toBeString()->not->toBeEmpty();
            expect(SingleCaseToggle::values())->toEqual(['ON']);
            expect(SingleCaseToggle::forSelect())->toHaveCount(1);
        });

        it('single-case enum comparison works', function () {
            expect(SingleCaseToggle::ON->is(SingleCaseToggle::ON))->toBeTrue();
            expect(SingleCaseToggle::ON->is('ON'))->toBeTrue();
        });
    });

    // ── Class-Level Attribute Resolution ──────────────────────────────────

    describe('class-level attribute resolution priority', function () {
        it('per-case color overrides class-level EnumColor', function () {
            // UserStatus: class-level EnumColor maps 'banned' → danger
            // Per-case #[Color('danger')] also on BANNED — per-case wins
            $banned = UserStatus::BANNED;
            expect($banned->color())->toBe('danger');
        });

        it('class-level EnumColor applies to cases without per-case override', function () {
            // 'active' maps to 'success' via class-level EnumColor
            $active = UserStatus::ACTIVE;
            expect($active->color())->toBe('success');
        });

        it('class-level EnumIcon default applies to all cases', function () {
            $cases = DefaultIconFeature::cases();
            foreach ($cases as $case) {
                expect($case->icon())->not->toBeNull();
            }
        });

        it('class-level EnumLabel provides bulk label mapping', function () {
            $cases = LabelMapEnum::cases();
            foreach ($cases as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });
    });

    // ── EnumCache Behavior ────────────────────────────────────────────────

    describe('EnumCache singleton behavior', function () {
        it('getInstance returns same instance on repeated calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('flush clears all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('clearClass only clears the target class', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0); // disable TTL for deterministic test

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Test'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(TicketStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(TicketStatus::class))->toBeFalse(); // TTL=0 means always stale
        });
    });

    // ── EnumRule Validation ──────────────────────────────────────────────

    describe('EnumRule validation contract', function () {
        it('accepts valid string-backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = fn () => null;

            // Should not call fail
            $called = false;
            $fail = function (string $message) use (&$called): void {
                $called = true;
            };

            $rule->validate('status', 'active', $fail);
            expect($called)->toBeFalse();
        });

        it('rejects invalid string-backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);

            $message = null;
            $fail = function (string $m) use (&$message): void {
                $message = $m;
            };

            $rule->validate('status', 'nonexistent', $fail);
            expect($message)->not->toBeNull();
        });

        it('nullable allows null values', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();

            $called = false;
            $fail = function (string $message) use (&$called): void {
                $called = true;
            };

            $rule->validate('status', null, $fail);
            expect($called)->toBeFalse();
        });

        it('non-nullable rejects null values', function () {
            $rule = EnumRule::for(UserStatus::class);

            $message = null;
            $fail = function (string $m) use (&$message): void {
                $message = $m;
            };

            $rule->validate('status', null, $fail);
            expect($message)->not->toBeNull();
        });

        it('validates int-backed enum against int values', function () {
            $rule = EnumRule::for(IntBackedPriority::class);

            $called = false;
            $fail = function (string $message) use (&$called): void {
                $called = true;
            };

            $rule->validate('priority', 3, $fail);
            expect($called)->toBeFalse();
        });

        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);

            $message = null;
            $fail = function (string $m) use (&$message): void {
                $message = $m;
            };

            $rule->validate('priority', 'high', $fail);
            expect($message)->not->toBeNull();
        });
    });

    // ── EnumCast Contract ─────────────────────────────────────────────────

    describe('EnumCast contract', function () {
        it('casts database string to enum instance', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(
                new class {
                    public $attributes = [];
                },
                'status',
                'active',
                [],
            );

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('returns null for null database value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(
                new class {
                    public $attributes = [];
                },
                'status',
                null,
                [],
            );

            expect($result)->toBeNull();
        });

        it('serializes enum instance to backed value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(
                new class {
                    public $attributes = [];
                },
                'status',
                UserStatus::ACTIVE,
                [],
            );

            expect($result)->toBe('active');
        });

        it('set() validates raw int value for int-backed enum', function () {
            $cast = new EnumCast(IntBackedPriority::class);
            $result = $cast->set(
                new class {
                    public $attributes = [];
                },
                'priority',
                3,
                [],
            );

            expect($result)->toBe(3);
        });
    });

    // ── Cross-Fixture Consistency ──────────────────────────────────────────

    describe('cross-fixture consistency', function () {
        it('all fixtures use HasEnumMetadata trait', function () {
            $fixtures = [
                IntBackedPriority::class,
                IntPriority::class,
                LabelMapEnum::class,
                NumericStatusCode::class,
                OrderStatus::class,
                PaymentStatus::class,
                PureFeatureFlag::class,
                PureSystemState::class,
                SingleCaseToggle::class,
                TicketStatus::class,
                UserStatus::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($fixtures as $fixture) {
                $ref = new ReflectionClass($fixture);
                expect($ref->hasMethod('label'))
                    ->toBeTrue("{$fixture} must have label() method");
                expect($ref->hasMethod('color'))
                    ->toBeTrue("{$fixture} must have color() method");
                expect($ref->hasMethod('forSelect'))
                    ->toBeTrue("{$fixture} must have forSelect() method");
                expect($ref->hasMethod('forApi'))
                    ->toBeTrue("{$fixture} must have forApi() method");
            }
        });

        it('all backed enums have unique values', function () {
            $backedEnums = [
                IntBackedPriority::class,
                IntPriority::class,
                NumericStatusCode::class,
                OrderStatus::class,
                PaymentStatus::class,
                TicketStatus::class,
                UserStatus::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($backedEnums as $enumClass) {
                $values = $enumClass::values();
                $unique = array_unique($values);
                expect(count($values))->toEqual(count($unique),
                    "{$enumClass} has duplicate backed values");
            }
        });
    });
});
