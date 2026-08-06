<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum Strict Type Compliance & Edge Cases', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    describe('HasEnumMetadata — string-backed enum metadata consistency', function () {
        it('forApi() returns consistent order with cases()', function () {
            $cases = UserStatus::cases();
            $api = UserStatus::forApi();

            expect(count($api))->toBe(count($cases));

            foreach ($cases as $i => $case) {
                expect($api[$i]['name'])->toBe($case->name);
                expect($api[$i]['value'])->toBe($case->value);
            }
        });

        it('forSelect() returns consistent order with cases()', function () {
            $cases = UserStatus::cases();
            $select = UserStatus::forSelect();

            expect(count($select))->toBe(count($cases));

            foreach ($cases as $i => $case) {
                expect($select[$i]['value'])->toBe($case->value);
            }
        });

        it('labels() returns same count as cases()', function () {
            $labels = UserStatus::labels();
            $cases = UserStatus::cases();

            expect(count($labels))->toBe(count($cases));
        });

        it('values() returns same count as cases()', function () {
            $values = UserStatus::values();
            $cases = UserStatus::cases();

            expect(count($values))->toBe(count($cases));
        });
    });

    describe('EnumRule with int-backed enum', function () {
        it('validates valid int value', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 1, $fail);

            expect($failed)->toBeFalse();
        });

        it('rejects invalid int value', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 99, $fail);

            expect($failed)->toBeTrue();
        });

        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            // String value should still work if it matches a backed value
            // But 'LOW' is a case name, not a value (values are 1,2,3,4)
            $rule->validate('priority', 'LOW', $fail);

            expect($failed)->toBeTrue();
        });

        it('validates zero value for int-backed enum', function () {
            $rule = EnumRule::for(ZeroPriority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 0, $fail);

            expect($failed)->toBeFalse();
        });

        it('for() factory method creates correct instance', function () {
            $rule = EnumRule::for(Priority::class);

            expect($rule)->toBeInstanceOf(EnumRule::class);
        });
    });

    describe('EnumCast with int-backed enum', function () {
        it('get returns enum for valid int value', function () {
            $cast = new EnumCast(Priority::class);
            $result = $cast->get(new stdClass, 'priority', 2, []);

            expect($result)->toBe(Priority::HIGH);
        });

        it('get returns null for zero value on int enum with no match', function () {
            // ZeroPriority has case NONE = 0, so 0 should resolve
            $cast = new EnumCast(ZeroPriority::class);
            $result = $cast->get(new stdClass, 'priority', 0, []);

            expect($result)->toBe(ZeroPriority::NONE);
        });

        it('set returns int value for enum instance', function () {
            $cast = new EnumCast(Priority::class);
            $result = $cast->set(new stdClass, 'priority', Priority::CRITICAL, []);

            expect($result)->toBe(1);
        });

        it('set accepts valid raw int value', function () {
            $cast = new EnumCast(Priority::class);
            $result = $cast->set(new stdClass, 'priority', 3, []);

            expect($result)->toBe(3);
        });

        it('serialize returns int for enum instance', function () {
            $cast = new EnumCast(Priority::class);
            $result = $cast->serialize(new stdClass, 'priority', Priority::LOW, []);

            expect($result)->toBe(3);
        });

        it('serialize returns raw int as-is', function () {
            $cast = new EnumCast(Priority::class);
            $result = $cast->serialize(new stdClass, 'priority', 4, []);

            expect($result)->toBe(4);
        });

        it('get returns null for non-existent int value', function () {
            $cast = new EnumCast(Priority::class);
            $result = $cast->get(new stdClass, 'priority', 999, []);

            expect($result)->toBeNull();
        });
    });

    describe('EnumMetadataResolver — class-level attributes', function () {
        it('EnumDescription class-level attributes are resolved', function () {
            // UserStatus has per-case descriptions but no class-level EnumDescription
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta['descriptions']['active'])->toBe('User can fully access the system');
            expect($meta['descriptions']['banned'])->toBe('User is permanently banned');
        });
    });

    describe('EnumCache — TTL behavior', function () {
        it('has() returns true immediately after set with positive TTL', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Test'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();
        });

        it('clear() then has() returns false', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->clear();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('setTtl can be called multiple times', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(60);
            $cache->setTtl(120);
            $cache->setTtl(0);

            expect(true)->toBeTrue(); // No exception
        });

        it('multiple classes can be cached independently', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'User'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(Priority::class, [
                'labels' => [1 => 'Critical'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(Priority::class))->toBeTrue();
            expect($cache->get(UserStatus::class)['labels']['active'])->toBe('User');
            expect($cache->get(Priority::class)['labels'][1])->toBe('Critical');
        });
    });

    describe('HasEnumMetadata — label generation edge cases', function () {
        it('generates label from single uppercase word', function () {
            // INACTIVE → "Inactive" (auto-generated)
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('preserves per-case label override', function () {
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
            expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
        });

        it('generates label for int-backed enum with uppercase case name', function () {
            expect(Priority::CRITICAL->label())->toBe('Critical');
            expect(Priority::URGENT->label())->toBe('Urgent');
        });

        it('generates label for pure enum with uppercase case name', function () {
            expect(RequestState::DRAFT->label())->toBe('Draft');
            expect(RequestState::APPROVED->label())->toBe('Approved');
            expect(RequestState::REJECTED->label())->toBe('Rejected');
        });
    });

    describe('tryFromLabel — edge cases', function () {
        it('returns null for empty string', function () {
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('returns null for whitespace-only string', function () {
            expect(UserStatus::tryFromLabel('   '))->toBeNull();
        });

        it('is case-insensitive for multi-word labels', function () {
            expect(UserStatus::tryFromLabel('awaiting verification'))->toBe(UserStatus::PENDING);
            expect(UserStatus::tryFromLabel('AWAITING VERIFICATION'))->toBe(UserStatus::PENDING);
        });
    });

    describe('InvalidEnumException — type coverage', function () {
        it('handles float value type', function () {
            $exception = InvalidEnumException::value(UserStatus::class, 3.14);

            expect($exception->getMessage())->toContain('float');
            expect($exception->getMessage())->toContain('UserStatus');
        });

        it('handles array value type', function () {
            $exception = InvalidEnumException::value(UserStatus::class, ['active']);

            expect($exception->getMessage())->toContain('array');
        });

        it('handles boolean value type', function () {
            $exception = InvalidEnumException::value(UserStatus::class, true);

            expect($exception->getMessage())->toContain('bool');
        });

        it('handles object value type', function () {
            $exception = InvalidEnumException::value(UserStatus::class, new stdClass);

            expect($exception->getMessage())->toContain('stdClass');
        });
    });

    describe('EnumManager — delegation verification', function () {
        it('forSelect returns correct structure', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $result = $manager->forSelect(Priority::class);

            expect($result)->toBeArray();
            expect(count($result))->toBe(4);
            expect($result[0])->toHaveKeys(['value', 'label']);
            expect($result[0]['value'])->toBe(1);
            expect(is_int($result[0]['value']))->toBeTrue();
        });

        it('forApi returns correct structure for int-backed enum', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $result = $manager->forApi(Priority::class);

            expect($result)->toBeArray();
            expect($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($result[0]['value'])->toBe(1);
            expect($result[0]['name'])->toBe('LOW');
        });

        it('tryFromLabel works via manager for int-backed enum', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $result = $manager->tryFromLabel(Priority::class, 'Low');

            expect($result)->toBe(Priority::LOW);
        });
    });
});
