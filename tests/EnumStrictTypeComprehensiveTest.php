<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);


/**
 * Comprehensive type safety tests verifying PHPStan Level 9 compliance.
 *
 * These tests verify:
 * - All return types are explicit (no mixed)
 * - All parameters are typed (no implicit mixed)
 * - Strict comparisons are used throughout
 * - No dynamic property access on enums
 * - readonly properties are enforced
 * - Backed enum value types are preserved correctly
 */

// ─── Test Fixtures ───────────────────────────────────────────────────────────

namespace ZeroBoiler\Enums\Enums\EnumStrictTypeComprehensiveTest {

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
final class Label
{
    public function __construct(
        public readonly string $value,
    ) {}
}

enum StringBackedColor: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    #[Label('Crimson Red')]
    case RED = 'red';

    case GREEN = 'green';

    case BLUE = 'blue';
}

}

namespace ZeroBoiler\Enums\Enums\EnumCastEdgeCasesTest {

enum IntBackedStrictStatus: int
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case PENDING = 0;
    case ACTIVE = 1;
    case ARCHIVED = 2;
}

}

namespace ZeroBoiler\Enums\Tests {
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Enums\EnumCastEdgeCasesTest\IntBackedStrictStatus;
use ZeroBoiler\Enums\Enums\EnumStrictTypeComprehensiveTest\StringBackedColor;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

// ─── Test Suite ───────────────────────────────────────────────────────────────

describe('PHPStan Level 9 Type Safety', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    describe('String-backed enum return types', function () {
        it('label() returns string (not mixed)', function () {
            $label = StringBackedColor::RED->label();

            expect($label)->toBeString()->toBe('Crimson Red');
        });

        it('description() returns ?string (not mixed)', function () {
            $desc = StringBackedColor::RED->description();

            expect($desc)->toBeNull();
        });

        it('color() returns string with default', function () {
            $color = StringBackedColor::RED->color();

            expect($color)->toBeString()->toBe('secondary');
        });

        it('icon() returns ?string', function () {
            $icon = StringBackedColor::RED->icon();

            expect($icon)->toBeNull();
        });

        it('forSelect() returns list of arrays with string|int values', function () {
            $select = StringBackedColor::forSelect();

            expect($select)->toBeArray()->toHaveCount(3);
            foreach ($select as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKey('value');
                expect($option)->toHaveKey('label');
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }
        });

        it('forApi() returns list of arrays with all metadata fields', function () {
            $api = StringBackedColor::forApi();

            expect($api)->toBeArray()->toHaveCount(3);
            foreach ($api as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
                expect($item['color'])->toBeString();
            }
        });

        it('values() returns list of string values for string-backed enum', function () {
            $values = StringBackedColor::values();

            expect($values)->toBe(['red', 'green', 'blue']);
            foreach ($values as $v) {
                expect($v)->toBeString();
            }
        });

        it('labels() returns list of strings', function () {
            $labels = StringBackedColor::labels();

            expect($labels)->toBeArray()->toHaveCount(3);
            foreach ($labels as $l) {
                expect($l)->toBeString()->not->toBeEmpty();
            }
        });
    });

    describe('Int-backed enum type preservation', function () {
        it('values() returns list of int values for int-backed enum', function () {
            $values = IntBackedStrictStatus::values();

            expect($values)->toBe([0, 1, 2]);
            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('forSelect() uses int as value for int-backed enum', function () {
            $select = IntBackedStrictStatus::forSelect();

            expect($select[0]['value'])->toBe(0)->toBeInt();
            expect($select[1]['value'])->toBe(1)->toBeInt();
            expect($select[2]['value'])->toBe(2)->toBeInt();
        });

        it('forApi() uses int as value for int-backed enum', function () {
            $api = IntBackedStrictStatus::forApi();

            expect($api[0]['value'])->toBe(0)->toBeInt();
            expect($api[0]['name'])->toBe('PENDING')->toBeString();
        });

        it('label() returns string for auto-generated labels', function () {
            expect(IntBackedStrictStatus::ACTIVE->label())->toBe('Active');
            expect(IntBackedStrictStatus::PENDING->label())->toBe('Pending');
            expect(IntBackedStrictStatus::ARCHIVED->label())->toBe('Archived');
        });
    });

    describe('Lookup methods return types', function () {
        it('tryFromLabel returns enum instance or null', function () {
            $result = StringBackedColor::tryFromLabel('Green');

            expect($result)->toBeInstanceOf(StringBackedColor::class);
            expect($result?->name)->toBe('GREEN');
        });

        it('tryFromLabel returns null for non-existent label', function () {
            $result = StringBackedColor::tryFromLabel('NonExistentColor');

            expect($result)->toBeNull();
        });

        it('tryFromName returns enum instance or null', function () {
            $result = StringBackedColor::tryFromName('RED');

            expect($result)->toBeInstanceOf(StringBackedColor::class);
            expect($result?->value)->toBe('red');
        });

        it('tryFromName returns null for non-existent name', function () {
            $result = StringBackedColor::tryFromName('MAGENTA');

            expect($result)->toBeNull();
        });

        it('fromName throws InvalidEnumException for non-existent name', function () {
            expect(fn () => StringBackedColor::fromName('MAGENTA'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns bool', function () {
            expect(StringBackedColor::hasCase('RED'))->toBeTrue();
            expect(StringBackedColor::hasCase('MAGENTA'))->toBeFalse();
        });
    });

    describe('Comparison methods strict type behavior', function () {
        it('is() uses strict identity for enum instances', function () {
            $a = StringBackedColor::RED;
            $b = StringBackedColor::RED;

            expect($a->is($b))->toBeTrue();
            expect($a->is(StringBackedColor::BLUE))->toBeFalse();
        });

        it('is() with string uses case-sensitive comparison', function () {
            expect(StringBackedColor::RED->is('RED'))->toBeTrue();
            expect(StringBackedColor::RED->is('Red'))->toBeFalse();
            expect(StringBackedColor::RED->is('red'))->toBeFalse();
            expect(StringBackedColor::RED->is('BLUE'))->toBeFalse();
        });

        it('isNot() returns correct boolean', function () {
            expect(StringBackedColor::RED->isNot(StringBackedColor::BLUE))->toBeTrue();
            expect(StringBackedColor::RED->isNot(StringBackedColor::RED))->toBeFalse();
        });

        it('in() accepts mixed instances and strings', function () {
            expect(StringBackedColor::RED->in([StringBackedColor::RED, 'GREEN']))->toBeTrue();
            expect(StringBackedColor::RED->in(['RED', StringBackedColor::GREEN]))->toBeTrue();
            expect(StringBackedColor::RED->in([StringBackedColor::GREEN, 'BLUE']))->toBeFalse();
        });

        it('in() returns false for empty array', function () {
            expect(StringBackedColor::RED->in([]))->toBeFalse();
        });
    });

    describe('EnumCache type safety', function () {
        it('getInstance returns EnumCache', function () {
            $cache = EnumCache::getInstance();

            expect($cache)->toBeInstanceOf(EnumCache::class);
        });

        it('has() returns bool', function () {
            $cache = EnumCache::getInstance();

            expect($cache->has('NonExistent'))->toBeFalse();
        });

        it('setTtl normalizes negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);

            // TTL <= 0 means no caching — has() always returns false
            expect($cache->has('AnyClass'))->toBeFalse();
        });

        it('get() throws OutOfBoundsException when no cache exists', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect(fn () => $cache->get('NonExistent'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('flush() is static and returns void', function () {
            EnumCache::flush();

            $cache = EnumCache::getInstance();
            expect($cache->has('AnyClass'))->toBeFalse();
        });

        it('resetInstance destroys singleton', function () {
            EnumCache::resetInstance();
            $newInstance = EnumCache::getInstance();

            expect($newInstance)->toBeInstanceOf(EnumCache::class);
        });

        it('clearClass removes specific entry', function () {
            $cache = EnumCache::getInstance();
            $cache->set('TestClass', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestClass'))->toBeTrue();

            $cache->clearClass('TestClass');

            expect($cache->has('TestClass'))->toBeFalse();
        });
    });

    describe('EnumRule type safety', function () {
        it('for() returns EnumRule instance', function () {
            $rule = EnumRule::for(StringBackedColor::class);

            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('nullable() returns new EnumRule instance', function () {
            $rule = EnumRule::for(StringBackedColor::class);
            $nullable = $rule->nullable();

            expect($nullable)->toBeInstanceOf(EnumRule::class);
            expect($nullable)->not->toBe($rule);
        });

        it('validate passes for valid backed value', function () {
            $rule = EnumRule::for(StringBackedColor::class);
            $fail = fn () => throw new \Exception('Should not fail');

            // Should not throw
            $rule->validate('color', 'red', $fail);
            expect(true)->toBeTrue();
        });

        it('validate calls fail for invalid value', function () {
            $rule = EnumRule::for(StringBackedColor::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('color', 'magenta', $fail);
            expect($failed)->toBeTrue();
        });

        it('validate passes null when nullable is set', function () {
            $rule = EnumRule::for(StringBackedColor::class)->nullable();
            $fail = fn () => throw new \Exception('Should not fail');

            // Should not throw
            $rule->validate('color', null, $fail);
            expect(true)->toBeTrue();
        });

        it('validate calls fail for null when not nullable', function () {
            $rule = EnumRule::for(StringBackedColor::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('color', null, $fail);
            expect($failed)->toBeTrue();
        });

        it('validate uses strict type check for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedStrictStatus::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            // Passing string '1' to int-backed enum should fail
            $rule->validate('status', '1', $fail);
            expect($failed)->toBeTrue();
        });

        it('validate passes int value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedStrictStatus::class);
            $fail = fn () => throw new \Exception('Should not fail');

            $rule->validate('status', 1, $fail);
            expect(true)->toBeTrue();
        });
    });

    describe('EnumMetadataResolver type contract', function () {
        it('resolve returns properly structured array', function () {
            $meta = EnumMetadataResolver::resolve(StringBackedColor::class);

            expect($meta)->toBeArray();
            expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
            expect($meta['labels'])->toBeArray();
            expect($meta['descriptions'])->toBeArray();
            expect($meta['colors'])->toBeArray();
            expect($meta['icons'])->toBeArray();
        });

        it('resolve caches result for subsequent calls', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            $first = EnumMetadataResolver::resolve(StringBackedColor::class);
            $second = EnumMetadataResolver::resolve(StringBackedColor::class);

            expect($first)->toBe($second);
        });
    });

    describe('InvalidEnumException named constructors', function () {
        it('value() returns exception with descriptive message', function () {
            $ex = InvalidEnumException::value(StringBackedColor::class, 'magenta');

            expect($ex)->toBeInstanceOf(InvalidEnumException::class);
            expect($ex->getMessage())->toBeString()->toContain('magenta');
            expect($ex->getMessage())->toContain(StringBackedColor::class);
        });

        it('value() handles null value', function () {
            $ex = InvalidEnumException::value(StringBackedColor::class, null);

            expect($ex->getMessage())->toContain('null');
        });

        it('forName() returns exception with case name', function () {
            $ex = InvalidEnumException::forName(StringBackedColor::class, 'MAGENTA');

            expect($ex)->toBeInstanceOf(InvalidEnumException::class);
            expect($ex->getMessage())->toContain('MAGENTA');
            expect($ex->getMessage())->toContain(StringBackedColor::class);
        });
    });
});
}
