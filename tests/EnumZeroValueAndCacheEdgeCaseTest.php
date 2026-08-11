<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;

describe('Enum Zero-Value and Cache Edge Cases', function () {
    beforeEach(function () {
        EnumCache::flush();
    });

    afterEach(function () {
        EnumCache::flush();
    });

    describe('ZeroBackedPriority (int-backed with zero value)', function () {
        it('has all three cases', function () {
            expect(ZeroBackedPriority::cases())->toHaveCount(3);
        });

        it('resolves label for zero-backed case correctly', function () {
            expect(ZeroBackedPriority::NONE->label())->toBe('None');
            expect(ZeroBackedPriority::LOW->label())->toBe('Low Priority');
            expect(ZeroBackedPriority::HIGH->label())->toBe('High Priority');
        });

        it('resolves color for zero-backed case from class-level EnumColor', function () {
            // 0 is mapped to 'secondary' in the EnumColor attribute
            expect(ZeroBackedPriority::NONE->color())->toBe('secondary');
            expect(ZeroBackedPriority::LOW->color())->toBe('success');
            expect(ZeroBackedPriority::HIGH->color())->toBe('danger');
        });

        it('returns correct values() including zero', function () {
            $values = ZeroBackedPriority::values();
            expect($values)->toEqual([0, 1, 2]);
            // Ensure zero is an actual int, not a falsy string or null
            expect($values[0])->toBeInt();
            expect($values[0])->toBe(0);
        });

        it('forSelect() includes zero value correctly', function () {
            $select = ZeroBackedPriority::forSelect();
            expect($select)->toHaveCount(3);
            expect($select[0])->toBe(['value' => 0, 'label' => 'None']);
            // Ensure value key is int 0, not string '0'
            expect($select[0]['value'])->toBeInt();
        });

        it('forApi() includes zero value with full metadata', function () {
            $api = ZeroBackedPriority::forApi();
            expect($api)->toHaveCount(3);
            expect($api[0]['value'])->toBe(0);
            expect($api[0]['name'])->toBe('NONE');
            expect($api[0]['label'])->toBe('None');
            expect($api[0]['color'])->toBe('secondary');
        });

        it('tryFromName() works for zero-backed case name', function () {
            expect(ZeroBackedPriority::tryFromName('NONE'))->toBe(ZeroBackedPriority::NONE);
            expect(ZeroBackedPriority::tryFromName('LOW'))->toBe(ZeroBackedPriority::LOW);
        });

        it('fromName() throws for invalid case name', function () {
            expect(fn () => ZeroBackedPriority::fromName('INVALID'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase() correctly identifies existing and non-existing cases', function () {
            expect(ZeroBackedPriority::hasCase('NONE'))->toBeTrue();
            expect(ZeroBackedPriority::hasCase('MEDIUM'))->toBeFalse();
        });

        it('tryFromLabel() resolves zero-backed case by label', function () {
            expect(ZeroBackedPriority::tryFromLabel('None'))->toBe(ZeroBackedPriority::NONE);
            expect(ZeroBackedPriority::tryFromLabel('Low Priority'))->toBe(ZeroBackedPriority::LOW);
            expect(ZeroBackedPriority::tryFromLabel('nonexistent'))->toBeNull();
        });

        it('comparison methods work with zero-backed case', function () {
            $none = ZeroBackedPriority::NONE;

            expect($none->is(ZeroBackedPriority::NONE))->toBeTrue();
            expect($none->is('NONE'))->toBeTrue();
            expect($none->isNot(ZeroBackedPriority::LOW))->toBeTrue();
            expect($none->in([ZeroBackedPriority::NONE, ZeroBackedPriority::LOW]))->toBeTrue();
            expect($none->in(['NONE', 'LOW']))->toBeTrue();
            expect($none->in([ZeroBackedPriority::HIGH]))->toBeFalse();
        });

        it('labels() returns correct labels in declaration order', function () {
            $labels = ZeroBackedPriority::labels();
            expect($labels)->toEqual(['None', 'Low Priority', 'High Priority']);
        });
    });

    describe('ZeroPriority (int-backed without class-level attributes)', function () {
        it('has auto-generated labels', function () {
            expect(ZeroPriority::NONE->label())->toBe('None');
            expect(ZeroPriority::LOW->label())->toBe('Low');
            expect(ZeroPriority::HIGH->label())->toBe('High');
        });

        it('has default color for all cases', function () {
            expect(ZeroPriority::NONE->color())->toBe('secondary');
            expect(ZeroPriority::LOW->color())->toBe('secondary');
            expect(ZeroPriority::HIGH->color())->toBe('secondary');
        });

        it('values() includes zero as int', function () {
            $values = ZeroPriority::values();
            expect($values[0])->toBe(0);
            expect($values[1])->toBe(1);
            expect($values[2])->toBe(2);
        });
    });

    describe('EnumCache edge cases with zero-backed enums', function () {
        it('caches and retrieves metadata for zero-backed enum correctly', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            expect($cache->has(ZeroBackedPriority::class))->toBeFalse();

            // Resolve triggers caching
            ZeroBackedPriority::NONE->label();

            expect($cache->has(ZeroBackedPriority::class))->toBeTrue();

            $meta = $cache->get(ZeroBackedPriority::class);
            expect($meta['labels'])->toHaveKey(0);
            expect($meta['labels'][0])->toBe('None');
        });

        it('clearClass() invalidates only the specified enum', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            ZeroBackedPriority::NONE->label();
            LabelMapEnum::DRAFT->label();

            expect($cache->has(ZeroBackedPriority::class))->toBeTrue();
            expect($cache->has(LabelMapEnum::class))->toBeTrue();

            $cache->clearClass(ZeroBackedPriority::class);

            expect($cache->has(ZeroBackedPriority::class))->toBeFalse();
            expect($cache->has(LabelMapEnum::class))->toBeTrue();
        });

        it('TTL of zero disables caching', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);

            expect($cache->has(ZeroBackedPriority::class))->toBeFalse();

            // Even after resolving, cache should report as not having the entry
            ZeroBackedPriority::NONE->label();

            expect($cache->has(ZeroBackedPriority::class))->toBeFalse();
        });
    });

    describe('EnumRule with zero-backed enum', function () {
        it('validates zero as a valid value', function () {
            $rule = EnumRule::for(ZeroBackedPriority::class);
            $fail = fn (string $message): string => $message;

            // Zero should pass — it's a valid backed value
            $error = null;
            $rule->validate('priority', 0, function (string $msg) use (&$error): void {
                $error = $msg;
            });

            expect($error)->toBeNull();
        });

        it('rejects negative values', function () {
            $rule = EnumRule::for(ZeroBackedPriority::class);

            $error = null;
            $rule->validate('priority', -1, function (string $msg) use (&$error): void {
                $error = $msg;
            });

            expect($error)->not->toBeNull();
        });

        it('rejects float values for int-backed enum', function () {
            $rule = EnumRule::for(ZeroBackedPriority::class);

            $error = null;
            $rule->validate('priority', 1.5, function (string $msg) use (&$error): void {
                $error = $msg;
            });

            expect($error)->not->toBeNull();
        });

        it('rejects string "0" for int-backed enum', function () {
            $rule = EnumRule::for(ZeroBackedPriority::class);

            $error = null;
            $rule->validate('priority', '0', function (string $msg) use (&$error): void {
                $error = $msg;
            });

            expect($error)->not->toBeNull();
        });

        it('nullable() allows null values', function () {
            $rule = EnumRule::for(ZeroBackedPriority::class)->nullable();

            $error = null;
            $rule->validate('priority', null, function (string $msg) use (&$error): void {
                $error = $msg;
            });

            expect($error)->toBeNull();
        });
    });

    describe('Cache invalidation and re-resolution', function () {
        it('rebuilds metadata after invalidation', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            // First resolution
            $label1 = AllClassLevelEnum::ACTIVE->label();

            // Invalidate
            EnumCache::getInstance()->clearClass(AllClassLevelEnum::class);

            // Second resolution should produce same result
            $label2 = AllClassLevelEnum::ACTIVE->label();

            expect($label1)->toBe($label2);
        });

        it('handles rapid sequential resolution of multiple enum types', function () {
            $labels = [];
            $colors = [];

            // Resolve multiple enum types in quick succession
            $labels[] = ZeroBackedPriority::NONE->label();
            $labels[] = LabelMapEnum::DRAFT->label();
            $labels[] = AllClassLevelEnum::ACTIVE->label();
            $labels[] = MixedAttributeStatus::ACTIVE->label();

            $colors[] = ZeroBackedPriority::NONE->color();
            $colors[] = LabelMapEnum::DRAFT->color();
            $colors[] = AllClassLevelEnum::ACTIVE->color();
            $colors[] = MixedAttributeStatus::ACTIVE->color();

            // All should return non-empty strings
            foreach ($labels as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }

            foreach ($colors as $color) {
                expect($color)->toBeString()->not->toBeEmpty();
            }
        });
    });
});
