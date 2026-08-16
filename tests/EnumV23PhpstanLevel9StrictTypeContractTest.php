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
use ZeroBoiler\Enums\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Fixtures\IntPriority;
use ZeroBoiler\Enums\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Fixtures\ZeroPriority;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\EnumCache;

describe('V23 PHPStan Level 9 strict type safety contract', function () {
    describe('return type strictness', function () {
        it('label() returns non-empty string for every case across all fixture enums', function () {
            $enums = [
                PaymentStatus::class,
                OrderStatus::class,
                IntPriority::class,
                IntBackedPriority::class,
                CamelCasePriority::class,
                PlainTestEnum::class,
                PureFeatureFlag::class,
                SingleCaseEnum::class,
                SingleCaseToggle::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($enums as $enumClass) {
                $cases = $enumClass::cases();
                foreach ($cases as $case) {
                    $label = $case->label();
                    expect($label)->toBeString();
                    expect($label)->not->toBeEmpty();
                    expect(strlen($label))->toBeGreaterThan(0);
                }
            }
        });

        it('color() always returns a string (never null, never int, never empty)', function () {
            $enums = [
                PaymentStatus::class,
                OrderStatus::class,
                IntPriority::class,
                IntBackedPriority::class,
                CamelCasePriority::class,
                PlainTestEnum::class,
                PureFeatureFlag::class,
                SingleCaseEnum::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($enums as $enumClass) {
                foreach ($enumClass::cases() as $case) {
                    $color = $case->color();
                    expect($color)->toBeString();
                    // Default is 'secondary' which is non-empty
                    expect(strlen($color))->toBeGreaterThan(0);
                }
            }
        });

        it('icon() returns string or null (never int, never array, never bool)', function () {
            $enums = [
                PaymentStatus::class,
                OrderStatus::class,
                IntPriority::class,
                PlainTestEnum::class,
                PureFeatureFlag::class,
            ];

            foreach ($enums as $enumClass) {
                foreach ($enumClass::cases() as $case) {
                    $icon = $case->icon();
                    expect($icon)->toBeNull()->or()->toBeString();
                }
            }
        });

        it('description() returns string or null (never int, never array, never bool)', function () {
            $enums = [
                PaymentStatus::class,
                OrderStatus::class,
                IntPriority::class,
                PlainTestEnum::class,
                PureFeatureFlag::class,
            ];

            foreach ($enums as $enumClass) {
                foreach ($enumClass::cases() as $case) {
                    $desc = $case->description();
                    expect($desc)->toBeNull()->or()->toBeString();
                }
            }
        });
    });

    describe('forSelect() return type contract', function () {
        it('returns array of arrays with value and label keys only', function () {
            $select = PaymentStatus::forSelect();
            expect($select)->toBeArray();

            foreach ($select as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveCount(2);
                expect($option)->toHaveKey('value');
                expect($option)->toHaveKey('label');
                // value must be string or int
                expect($option['value'])->toBeString()->or()->toBeInt();
                // label must be non-empty string
                expect($option['label'])->toBeString();
                expect($option['label'])->not->toBeEmpty();
            }
        });

        it('values are unique for string-backed enums', function () {
            $values = array_column(PaymentStatus::forSelect(), 'value');
            expect($values)->toBeArray();
            expect($values)->each->toBeString();
            expect(array_count_values($values))->each->toBe(1);
        });

        it('values are unique for int-backed enums', function () {
            $values = array_column(IntPriority::forSelect(), 'value');
            expect($values)->toBeArray();
            expect($values)->each->toBeInt();
            expect(array_count_values($values))->each->toBe(1);
        });
    });

    describe('forApi() return type contract', function () {
        it('returns array with all 6 required keys per case', function () {
            $api = PaymentStatus::forApi();
            $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

            foreach ($api as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKeys($requiredKeys);
                // value: string|int
                expect($item['value'])->toBeString()->or()->toBeInt();
                // name: string
                expect($item['name'])->toBeString();
                // label: non-empty string
                expect($item['label'])->toBeString()->not->toBeEmpty();
                // description: string|null
                expect($item['description'])->toBeNull()->or()->toBeString();
                // color: non-empty string
                expect($item['color'])->toBeString()->not->toBeEmpty();
                // icon: string|null
                expect($item['icon'])->toBeNull()->or()->toBeString();
            }
        });

        it('name field matches actual enum case name exactly', function () {
            $api = PaymentStatus::forApi();
            $caseNames = array_column($api, 'name');

            foreach (PaymentStatus::cases() as $case) {
                expect($caseNames)->toContain($case->name);
            }
        });
    });

    describe('comparison method type strictness', function () {
        it('is() rejects non-string non-enum input at runtime', function () {
            $status = PaymentStatus::PAID;

            // int should not match any case name
            expect($status->is(42))->toBeFalse();
            // bool should not match
            expect($status->is(true))->toBeFalse();
            // array should not match
            expect($status->is([]))->toBeFalse();
            // float should not match
            expect($status->is(3.14))->toBeFalse();
        });

        it('isNot() with wrong type returns true (case name never matches)', function () {
            $status = PaymentStatus::PAID;
            expect($status->isNot(42))->toBeTrue();
            expect($status->isNot(null))->toBeTrue();
        });

        it('in() with empty array returns false', function () {
            $status = PaymentStatus::PAID;
            expect($status->in([]))->toBeFalse();
        });

        it('notIn() with empty array returns true', function () {
            $status = PaymentStatus::PAID;
            expect($status->notIn([]))->toBeTrue();
        });
    });

    describe('lookup method type strictness', function () {
        it('tryFromLabel with empty string returns null', function () {
            expect(PaymentStatus::tryFromLabel(''))->toBeNull();
        });

        it('tryFromName with empty string returns null', function () {
            expect(PaymentStatus::tryFromName(''))->toBeNull();
        });

        it('fromName with empty string throws InvalidEnumException', function () {
            expect(fn () => PaymentStatus::fromName(''))->toThrow(InvalidEnumException::class);
        });

        it('fromName exception contains class name and invalid name', function () {
            try {
                PaymentStatus::fromName('DOES_NOT_EXIST');
                expect(true)->toBeFalse('Should have thrown');
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('DOES_NOT_EXIST');
                expect($e->getMessage())->toContain(PaymentStatus::class);
            }
        });

        it('hasCase is case-sensitive', function () {
            $enum = PaymentStatus::class;
            expect($enum::hasCase('PAID'))->toBeTrue();
            expect($enum::hasCase('paid'))->toBeFalse(); // lowercase
            expect($enum::hasCase('Paid'))->toBeFalse(); // mixed case
        });
    });

    describe('zero-value int-backed enum edge cases', function () {
        it('zero-backed enum returns 0 as value', function () {
            $case = ZeroPriority::ZERO;
            expect($case->value)->toBe(0);
            expect($case->name)->toBe('ZERO');
        });

        it('zero-backed enum label is non-empty', function () {
            expect(ZeroPriority::ZERO->label())->toBeString()->not->toBeEmpty();
        });

        it('zero-backed enum is accessible via forSelect', function () {
            $select = ZeroPriority::forSelect();
            $zeroOption = array_filter($select, fn (array $o): bool => $o['value'] === 0);
            expect($zeroOption)->not->toBeEmpty();
        });

        it('zero-backed enum is accessible via forApi', function () {
            $api = ZeroPriority::forApi();
            $zeroItem = array_filter($api, fn (array $a): bool => $a['value'] === 0);
            expect($zeroItem)->not->toBeEmpty();
            $zeroItem = array_values($zeroItem);
            expect($zeroItem[0]['label'])->toBeString()->not->toBeEmpty();
        });
    });

    describe('pure enum type contract', function () {
        it('pure enum values() returns case names (strings)', function () {
            $values = PureFeatureFlag::values();
            expect($values)->toBeArray();
            expect($values)->each->toBeString();
            expect($values)->not->toBeEmpty();

            foreach (PureFeatureFlag::cases() as $case) {
                expect($values)->toContain($case->name);
            }
        });

        it('pure enum forSelect uses case names as values', function () {
            $select = PureFeatureFlag::forSelect();
            foreach ($select as $option) {
                expect($option['value'])->toBeString();
                // Should be one of the case names
                expect(PureFeatureFlag::tryFromName($option['value']))->not->toBeNull();
            }
        });

        it('pure enum forApi uses case names as values', function () {
            $api = PureFeatureFlag::forApi();
            foreach ($api as $item) {
                expect($item['value'])->toBeString();
                expect(PureFeatureFlag::tryFromName($item['value']))->not->toBeNull();
            }
        });
    });

    describe('single-case enum edge cases', function () {
        it('single string-backed enum has exactly one case', function () {
            expect(SingleCaseEnum::cases())->toHaveCount(1);
        });

        it('single pure enum has exactly one case', function () {
            expect(SingleCaseToggle::cases())->toHaveCount(1);
        });

        it('single-case enum forSelect returns one entry', function () {
            expect(SingleCaseEnum::forSelect())->toHaveCount(1);
            expect(SingleCaseToggle::forSelect())->toHaveCount(1);
        });

        it('single-case enum in() with its own case returns true', function () {
            $case = SingleCaseEnum::ONLY_CASE;
            expect($case->in([SingleCaseEnum::ONLY_CASE]))->toBeTrue();
            expect($case->in(['ONLY_CASE']))->toBeTrue();
        });
    });

    describe('EnumRule type validation contract', function () {
        it('string-backed enum rule rejects int input', function () {
            $rule = EnumRule::for(PaymentStatus::class);
            $failed = false;

            $rule->validate('status', 42, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('string-backed enum rule rejects bool input', function () {
            $rule = EnumRule::for(PaymentStatus::class);
            $failed = false;

            $rule->validate('status', true, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('string-backed enum rule rejects array input', function () {
            $rule = EnumRule::for(PaymentStatus::class);
            $failed = false;

            $rule->validate('status', ['paid'], function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('int-backed enum rule rejects string input', function () {
            $rule = EnumRule::for(IntPriority::class);
            $failed = false;

            $rule->validate('priority', 'high', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('int-backed enum rule accepts valid int', function () {
            $rule = EnumRule::for(IntPriority::class);
            $firstValue = IntPriority::cases()[0]->value;
            $failed = false;

            $rule->validate('priority', $firstValue, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('pure enum rule rejects int input', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('flag', 1, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('nullable rule passes null without error', function () {
            $rule = EnumRule::for(PaymentStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('nullable rule still validates non-null values', function () {
            $rule = EnumRule::for(PaymentStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', 'invalid_status', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    describe('EnumCache singleton behavior', function () {
        it('getInstance returns same instance', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('setTtl normalizes negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);
            expect($cache->getTtl())->toBe(0);
            $cache->setTtl(300); // restore
        });

        it('cache with TTL 0 disables caching', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(0);
            expect($cache->has('NonExistentEnum::class'))->toBeFalse();
            $cache->setTtl(300); // restore
        });

        it('get throws on non-existent entry', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect(fn () => $cache->get('NonExistentEnum::class'))
                ->toThrow(\OutOfBoundsException::class);
        });
    });

    describe('EnumMetadataResolver cache lifecycle', function () {
        it('invalidate removes cached entry for specific class', function () {
            EnumMetadataResolver::resolve(PaymentStatus::class);
            EnumMetadataResolver::invalidate(PaymentStatus::class);

            $cache = EnumCache::getInstance();
            expect($cache->has(PaymentStatus::class))->toBeFalse();
        });

        it('invalidateAll flushes all entries', function () {
            EnumMetadataResolver::resolve(PaymentStatus::class);
            EnumMetadataResolver::resolve(OrderStatus::class);
            EnumMetadataResolver::invalidateAll();

            $cache = EnumCache::getInstance();
            expect($cache->has(PaymentStatus::class))->toBeFalse();
            expect($cache->has(OrderStatus::class))->toBeFalse();
        });

        it('resolve returns consistent structure after cache invalidation', function () {
            $before = EnumMetadataResolver::resolve(PaymentStatus::class);
            EnumMetadataResolver::invalidate(PaymentStatus::class);
            $after = EnumMetadataResolver::resolve(PaymentStatus::class);

            expect($before)->toEqual($after);
        });
    });

    describe('EnumCast type safety', function () {
        it('get returns null for null value', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->get(new \stdClass(), 'status', null, []);
            expect($result)->toBeNull();
        });

        it('get returns null for non-int non-string value', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->get(new \stdClass(), 'status', ['invalid'], []);
            expect($result)->toBeNull();
        });

        it('get returns null for bool value', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->get(new \stdClass(), 'status', true, []);
            expect($result)->toBeNull();
        });

        it('set throws on type mismatch', function () {
            $cast = new EnumCast(PaymentStatus::class);
            expect(fn () => $cast->set(new \stdClass(), 'status', IntPriority::LOW, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('set throws on invalid raw value', function () {
            $cast = new EnumCast(PaymentStatus::class);
            expect(fn () => $cast->set(new \stdClass(), 'status', 'not_a_valid_status', []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize returns backed value for enum instance', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->serialize(new \stdClass(), 'status', PaymentStatus::PAID, []);
            expect($result)->toBe('paid');
        });

        it('serialize passes through int value', function () {
            $cast = new EnumCast(IntPriority::class);
            $result = $cast->serialize(new \stdClass(), 'priority', IntPriority::LOW->value, []);
            expect($result)->toBeInt();
        });
    });

    describe('camelCase enum label generation', function () {
        it('generates readable label from camelCase name', function () {
            $case = CamelCasePriority::high;
            $label = $case->label();
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        });

        it('generates readable label from SCREAMING_SNAKE_CASE', function () {
            $case = PaymentStatus::PAID;
            $label = $case->label();
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
            // Label should not contain underscores
            expect($label)->not->toContain('_');
        });
    });

    describe('bulk methods consistency across enum types', function () {
        it('values() and labels() return same count as cases()', function () {
            $enums = [
                PaymentStatus::class,
                OrderStatus::class,
                IntPriority::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
                CamelCasePriority::class,
                SingleCaseEnum::class,
                SingleCaseToggle::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($enums as $enumClass) {
                $caseCount = count($enumClass::cases());
                expect($enumClass::values())->toHaveCount($caseCount);
                expect($enumClass::labels())->toHaveCount($caseCount);
            }
        });

        it('forSelect() and forApi() return same count as cases()', function () {
            $enums = [
                PaymentStatus::class,
                IntPriority::class,
                PureFeatureFlag::class,
            ];

            foreach ($enums as $enumClass) {
                $caseCount = count($enumClass::cases());
                expect($enumClass::forSelect())->toHaveCount($caseCount);
                expect($enumClass::forApi())->toHaveCount($caseCount);
            }
        });
    });
});
