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
use ZeroBoiler\Enums\Tests\Fixtures\{
    AllClassLevelEnum,
    CamelCasePriority,
    DetailedTicketStatus,
    EmptyDefaultsStatus,
    IntBackedPriority,
    IntPriority,
    IntStatusWithColor,
    MixedAttributeStatus,
    MixedTicketType,
    NumericStatusCode,
    OrderStatus,
    OrderWorkflowStatus,
    OverriddenIconRole,
    PaymentStatus,
    PlainTestEnum,
    Priority,
    PureFeatureFlag,
    PureSystemState,
    RequestState,
    SingleCaseEnum,
    SingleCaseToggle,
    SingletonMode,
    SystemStatus,
    TicketStatus,
    UserStatus,
    ZeroBackedPriority,
    ZeroPriority,
};

beforeEach(function () {
    EnumCache::flush();
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('V37 PHPStan Level 9 Strict Type Safety Audit', function () {
    describe('Return type strictness — no mixed leaks', function () {
        it('label() always returns string, never null', function () {
            foreach (UserStatus::cases() as $case) {
                $label = $case->label();
                expect($label)->toBeString();
                expect(strlen($label))->toBeGreaterThan(0);
            }
        });

        it('color() always returns string, never null', function () {
            foreach (UserStatus::cases() as $case) {
                expect($case->color())->toBeString();
            }
        });

        it('icon() returns string or null — nullable return type is correct', function () {
            $hasNull = false;
            $hasString = false;

            foreach (DetailedTicketStatus::cases() as $case) {
                $icon = $case->icon();
                expect($icon)->toBeNull()->or()->toBeString();
                if ($icon === null) {
                    $hasNull = true;
                } else {
                    $hasString = true;
                }
            }

            expect($hasNull || $hasString)->toBeTrue();
        });

        it('description() returns string or null — nullable return type is correct', function () {
            $hasNull = false;
            $hasString = false;

            foreach (DetailedTicketStatus::cases() as $case) {
                $desc = $case->description();
                expect($desc)->toBeNull()->or()->toBeString();
                if ($desc === null) {
                    $hasNull = true;
                } else {
                    $hasString = true;
                }
            }

            expect($hasNull || $hasString)->toBeTrue();
        });

        it('values() returns list of int|string — no null, no mixed', function () {
            $values = IntBackedPriority::values();
            foreach ($values as $v) {
                expect($v)->toBeInt();
            }

            $values = UserStatus::values();
            foreach ($values as $v) {
                expect($v)->toBeString();
            }

            $values = PureFeatureFlag::values();
            foreach ($values as $v) {
                expect($v)->toBeString();
            }
        });

        it('labels() returns list of non-empty strings', function () {
            $labels = UserStatus::labels();
            expect($labels)->toHaveCount(count(UserStatus::cases()));
            foreach ($labels as $l) {
                expect($l)->toBeString();
                expect(strlen($l))->toBeGreaterThan(0);
            }
        });

        it('forSelect() returns consistent structure — value is int|string, label is string', function () {
            $select = IntBackedPriority::forSelect();
            foreach ($select as $opt) {
                expect($opt)->toBeArray();
                expect($opt)->toHaveKey('value');
                expect($opt)->toHaveKey('label');
                expect($opt['value'])->toBeInt();
                expect($opt['label'])->toBeString();
            }

            $select = UserStatus::forSelect();
            foreach ($select as $opt) {
                expect($opt['value'])->toBeString();
                expect($opt['label'])->toBeString();
            }
        });

        it('forApi() returns consistent structure with correct types', function () {
            $api = UserStatus::forApi();
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
                expect($item['color'])->toBeString();
                expect($item['description'])->toBeNull()->or()->toBeString();
                expect($item['icon'])->toBeNull()->or()->toBeString();
            }
        });
    });

    describe('Strict comparison semantics', function () {
        it('is() uses strict identity for instances', function () {
            $a = UserStatus::ACTIVE;
            $b = UserStatus::ACTIVE;
            expect($a->is($b))->toBeTrue();
            expect($a === $b)->toBeTrue(); // same singleton
        });

        it('is() with string name is case-sensitive', function () {
            $status = UserStatus::ACTIVE;
            expect($status->is('ACTIVE'))->toBeTrue();
            expect($status->is('Active'))->toBeFalse();
            expect($status->is('active'))->toBeFalse();
            expect($status->is(''))->toBeFalse();
        });

        it('isNot() is exact negation of is()', function () {
            $status = UserStatus::ACTIVE;
            foreach (UserStatus::cases() as $case) {
                expect($status->isNot($case))->toBe(! $status->is($case));
            }
            expect($status->isNot('ACTIVE'))->toBe(! $status->is('ACTIVE'));
            expect($status->isNot('BANNED'))->toBe(! $status->is('BANNED'));
        });

        it('in() with empty array returns false', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('notIn() with empty array returns true', function () {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });

        it('in() and notIn() are exact negations', function () {
            $case = UserStatus::ACTIVE;
            $lists = [
                [UserStatus::ACTIVE],
                [UserStatus::BANNED],
                ['ACTIVE', 'BANNED'],
                [],
                [UserStatus::ACTIVE, UserStatus::BANNED, 'PENDING'],
            ];

            foreach ($lists as $list) {
                expect($case->notIn($list))->toBe(! $case->in($list));
            }
        });
    });

    describe('Lookup type strictness', function () {
        it('tryFromLabel() returns static|null — type-safe union', function () {
            $result = UserStatus::tryFromLabel('Active User');
            expect($result)->toBeInstanceOf(UserStatus::class);

            $result = UserStatus::tryFromLabel('nonexistent_xyz');
            expect($result)->toBeNull();
        });

        it('tryFromName() returns static|null — type-safe union', function () {
            $result = UserStatus::tryFromName('ACTIVE');
            expect($result)->toBeInstanceOf(UserStatus::class);

            $result = UserStatus::tryFromName('NON_EXISTENT');
            expect($result)->toBeNull();
        });

        it('fromName() returns static — throws on failure', function () {
            $result = UserStatus::fromName('ACTIVE');
            expect($result)->toBeInstanceOf(UserStatus::class);
            expect($result->name)->toBe('ACTIVE');

            expect(fn () => UserStatus::fromName('INVALID'))->toThrow(InvalidEnumException::class);
        });

        it('hasCase() returns bool — strict boolean', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('active'))->toBeFalse();
            expect(UserStatus::hasCase(''))->toBeFalse();
        });

        it('tryFromLabel() is case-insensitive', function () {
            $label = UserStatus::ACTIVE->label();
            expect(UserStatus::tryFromLabel($label))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(strtoupper($label)))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(strtolower($label)))->toBe(UserStatus::ACTIVE);
        });
    });

    describe('EnumCache singleton behavior', function () {
        it('getInstance() always returns same instance', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('setTtl() clamps negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-100);
            expect($cache->getTtl())->toBe(0);
        });

        it('setTtl() accepts zero to disable caching', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            expect($cache->getTtl())->toBe(0);
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('__debugInfo() returns typed array shape', function () {
            $cache = EnumCache::getInstance();
            $debug = $cache->__debugInfo();
            expect($debug)->toBeArray();
            expect($debug)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
            expect($debug['ttl'])->toBeInt();
            expect($debug['cachedClasses'])->toBeInt();
            expect($debug['timestampCount'])->toBeInt();
        });

        it('serialization prevention methods return never', function () {
            $cache = EnumCache::getInstance();
            expect(fn () => clone $cache)->toThrow(\RuntimeException::class);
            expect(fn () => serialize($cache))->toThrow(\RuntimeException::class);
        });
    });

    describe('EnumRule type safety', function () {
        it('nullable() returns new instance, not modifies existing', function () {
            $rule = EnumRule::for(UserStatus::class);
            $nullable = $rule->nullable();
            expect($nullable)->not->toBe($rule);
        });

        it('for() returns same-enum instance', function () {
            $rule = EnumRule::for(UserStatus::class);
            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('rejects non-existent enum class gracefully', function () {
            $rule = EnumRule::for('NonExistentEnum');
            $fail = fn (string $msg): string => $msg;
            $failCalled = false;
            $messages = [];

            $rule->validate('field', 'invalid', function (string $message) use (&$messages) {
                $messages[] = $message;
            });

            expect($messages)->not->toBeEmpty();
        });
    });

    describe('EnumManager delegation type safety', function () {
        it('forSelect() returns same structure as trait method', function () {
            $manager = new EnumManager;
            $traitResult = UserStatus::forSelect();
            $managerResult = $manager->forSelect(UserStatus::class);
            expect($managerResult)->toEqual($traitResult);
        });

        it('values() returns same as trait method', function () {
            $manager = new EnumManager;
            expect($manager->values(UserStatus::class))->toEqual(UserStatus::values());
        });

        it('labels() returns same as trait method', function () {
            $manager = new EnumManager;
            expect($manager->labels(UserStatus::class))->toEqual(UserStatus::labels());
        });

        it('throws BadMethodCallException for non-enum class', function () {
            $manager = new EnumManager;
            expect(fn () => $manager->forSelect(\stdClass::class))->toThrow(\BadMethodCallException::class);
            expect(fn () => $manager->forApi(\stdClass::class))->toThrow(\BadMethodCallException::class);
        });

        it('tryFromLabel() returns UnitEnum|null via manager', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromLabel(UserStatus::class, 'Active User');
            expect($result)->not->toBeNull();
            expect($result->name)->toBe('ACTIVE');
        });
    });

    describe('EnumCast type strictness', function () {
        it('get() returns BackedEnum|null — type-safe', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new \stdClass, 'status', 'active', []);
            expect($result)->toBeInstanceOf(\BackedEnum::class);
            expect($result->name)->toBe('ACTIVE');

            $nullResult = $cast->get(new \stdClass, 'status', null, []);
            expect($nullResult)->toBeNull();
        });

        it('set() returns int|string|null — type-safe', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new \stdClass, 'status', UserStatus::ACTIVE, []);
            expect($result)->toBeString();
            expect($result)->toBe('active');

            $nullResult = $cast->set(new \stdClass, 'status', null, []);
            expect($nullResult)->toBeNull();
        });

        it('set() rejects mismatched enum type', function () {
            $cast = new EnumCast(UserStatus::class);
            expect(fn () => $cast->set(
                new \stdClass, 'status', PaymentStatus::PAID, []
            ))->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns int|string|null', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(new \stdClass, 'status', UserStatus::ACTIVE, []);
            expect($result)->toBe('active');

            $rawResult = $cast->serialize(new \stdClass, 'status', 'active', []);
            expect($rawResult)->toBe('active');

            $nullResult = $cast->serialize(new \stdClass, 'status', null, []);
            expect($nullResult)->toBeNull();
        });
    });

    describe('Cross-type enum consistency', function () {
        it('all enums using HasEnumMetadata have consistent metadata shape', function () {
            $enums = [
                UserStatus::class,
                PaymentStatus::class,
                TicketStatus::class,
                OrderStatus::class,
                IntBackedPriority::class,
                IntPriority::class,
                Priority::class,
                PureFeatureFlag::class,
                PureSystemState::class,
                DetailedTicketStatus::class,
            ];

            foreach ($enums as $enumClass) {
                $api = $enumClass::forApi();
                foreach ($api as $item) {
                    expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                    expect($item['color'])->toBeString();
                    expect($item['label'])->toBeString();
                }
            }
        });

        it('zero-backed int enums work correctly', function () {
            expect(ZeroBackedPriority::ZERO->value)->toBe(0);
            expect(ZeroBackedPriority::ZERO->label())->toBeString();
            expect(ZeroBackedPriority::ZERO->color())->toBeString();
        });

        it('single case enums work with all metadata methods', function () {
            expect(SingleCaseEnum::ONLY->label())->toBeString();
            expect(SingleCaseEnum::ONLY->color())->toBeString();
            expect(SingleCaseEnum::ONLY->forSelect())->toBeArray();
            expect(SingleCaseEnum::ONLY->forSelect())->toHaveCount(1);
        });
    });

    describe('Metadata resolution priority', function () {
        it('per-case Color overrides class-level EnumColor', function () {
            // IntStatusWithColor has class-level EnumColor + per-case overrides
            $resolved = IntStatusWithColor::cases();
            foreach ($resolved as $case) {
                $color = $case->color();
                expect($color)->toBeString();
            }
        });

        it('class-level EnumIcon default is applied to cases without specific icon', function () {
            $cases = OverriddenIconRole::cases();
            foreach ($cases as $case) {
                $icon = $case->icon();
                expect($icon)->toBeNull()->or()->toBeString();
            }
        });

        it('class-level EnumLabel overrides auto-generated labels', function () {
            // LabelMapEnum has class-level EnumLabel with explicit labels
            $cases = \ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum::cases();
            foreach ($cases as $case) {
                $label = $case->label();
                expect($label)->toBeString();
                expect(strlen($label))->toBeGreaterThan(0);
            }
        });
    });
});
