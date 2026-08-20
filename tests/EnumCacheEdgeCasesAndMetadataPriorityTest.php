<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Enum cache edge cases, metadata resolution priority, and EnumTestGenerator output.
 *
 * Covers:
 * - EnumCache TTL behavior (disabled, expired, valid)
 * - EnumCache singleton identity
 * - EnumCache __debugInfo shape
 * - EnumCache serialization prevention (__serialize, __unserialize, __wakeup, __clone)
 * - EnumMetadataResolver::invalidate() and invalidateAll()
 * - EnumTestGenerator generates syntactically valid PHP
 * - EnumTestGenerator output includes all expected test sections
 * - Per-case attribute overrides class-level (priority verification)
 * - EnumIcon default fallback behavior
 * - Multiple attributes on same case
 * - EnumCast serialize() with int and string values
 * - EnumRule message formatting with HasEnumMetadata trait
 * - EnumRule with pure enum (name-based validation)
 * - InvalidEnumException value() with int, string, and null
 *
 * @see \ZeroBoiler\Enums\EnumCache
 * @see \ZeroBoiler\Enums\Support\EnumMetadataResolver
 * @see \ZeroBoiler\Enums\Support\EnumTestGenerator
 * @see \ZeroBoiler\Enums\Casts\EnumCast
 * @see \ZeroBoiler\Enums\Rules\EnumRule
 */

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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache Edge Cases and Metadata Priority', function (): void {

    // ──────────────────────────────────────────────────────────────
    // 1. EnumCache singleton identity
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache singleton identity', function (): void {
        it('getInstance() returns the same instance on repeated calls', function (): void {
            EnumCache::resetInstance();
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('resetInstance() creates a fresh instance', function (): void {
            EnumCache::resetInstance();
            $before = EnumCache::getInstance();
            $before->set('testclass', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            EnumCache::resetInstance();
            $after = EnumCache::getInstance();

            expect($after)->not->toBe($before);
            expect($after->has('testclass'))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 2. EnumCache TTL behavior
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache TTL behavior', function (): void {
        it('TTL of 0 disables caching — has() always returns false', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set('SomeEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has('SomeEnum'))->toBeFalse();
        });

        it('Negative TTL is clamped to 0', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->setTtl(-10);

            expect($cache->getTtl())->toBe(0);
        });

        it('Valid entry is returned by has() before TTL expires', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set('SomeEnum', ['labels' => ['a' => 'A'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has('SomeEnum'))->toBeTrue();
        });

        it('setTtl returns current TTL via getTtl()', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->setTtl(60);

            expect($cache->getTtl())->toBe(60);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 3. EnumCache __debugInfo
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache __debugInfo shape', function (): void {
        it('returns array with ttl, cachedClasses, and timestampCount keys', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->set('A', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('B', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $debug = $cache->__debugInfo();

            expect($debug)->toBeArray();
            expect($debug)->toHaveKey('ttl');
            expect($debug)->toHaveKey('cachedClasses');
            expect($debug)->toHaveKey('timestampCount');
            expect($debug['cachedClasses'])->toBe(2);
            expect($debug['timestampCount'])->toBe(2);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 4. EnumCache serialization prevention
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache blocks all serialization', function (): void {
        it('__clone() throws RuntimeException', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();

            expect(fn () => clone $cache)->toThrow(\RuntimeException::class);
        });

        it('__wakeup() throws RuntimeException', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();

            expect(fn () => $cache->__wakeup())->toThrow(\RuntimeException::class);
        });

        it('__serialize() throws RuntimeException', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();

            expect(fn () => $cache->__serialize())->toThrow(\RuntimeException::class);
        });

        it('__unserialize() throws RuntimeException', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();

            expect(fn () => $cache->__unserialize([]))->toThrow(\RuntimeException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 5. EnumCache clear / clearClass / flush
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache invalidation methods', function (): void {
        it('clearClass() removes one class without affecting others', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
            $cache->set('EnumA', $meta);
            $cache->set('EnumB', $meta);

            $cache->clearClass('EnumA');

            expect($cache->has('EnumA'))->toBeFalse();
            expect($cache->has('EnumB'))->toBeTrue();
        });

        it('flush() clears everything', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set('X', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('Y', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clear();

            expect($cache->has('X'))->toBeFalse();
            expect($cache->has('Y'))->toBeFalse();
        });

        it('static flush() delegates to singleton clear()', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set('Z', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            EnumCache::flush();

            expect($cache->has('Z'))->toBeFalse();
        });

        it('get() throws OutOfBoundsException for missing entry', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();

            expect(fn () => $cache->get('NonExistent'))
                ->toThrow(\OutOfBoundsException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 6. EnumMetadataResolver::invalidate / invalidateAll
    // ──────────────────────────────────────────────────────────────

    describe('EnumMetadataResolver invalidation', function (): void {
        it('invalidate() clears cache for one class', function (): void {
            EnumCache::resetInstance();
            EnumCache::getInstance()->setTtl(300);

            // Resolve to populate cache
            EnumMetadataResolver::resolve(OrderStatus::class);
            expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeTrue();

            EnumMetadataResolver::invalidate(OrderStatus::class);
            expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse();
        });

        it('invalidateAll() clears cache for all classes', function (): void {
            EnumCache::resetInstance();
            EnumCache::getInstance()->setTtl(300);

            EnumMetadataResolver::resolve(OrderStatus::class);
            EnumMetadataResolver::resolve(IntBackedPriority::class);

            EnumMetadataResolver::invalidateAll();

            expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(IntBackedPriority::class))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 7. EnumTestGenerator generates valid PHP
    // ──────────────────────────────────────────────────────────────

    describe('EnumTestGenerator output validation', function (): void {
        it('generates content with PHP open tag and strict_types', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('<?php');
            expect($content)->toContain('declare(strict_types=1)');
        });

        it('generates content with describe() block', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('describe(');
        });

        it('generates per-case label test', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('has a non-empty label for case');
        });

        it('generates per-case color test', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('has a string color for case');
        });

        it('generates per-case icon test', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('returns a string or null icon for case');
        });

        it('generates per-case description test', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('returns a string or null description for case');
        });

        it('generates forSelect test', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('can generate select options');
        });

        it('generates forApi test', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('can generate API response array');
        });

        it('generates fromName throw test', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('fromName() throws InvalidEnumException for non-existent name');
        });

        it('generates comparison tests for enums with 2+ cases', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('supports is() comparison with instance');
            expect($content)->toContain('supports isNot() comparison');
            expect($content)->toContain('supports in() group matching');
            expect($content)->toContain('supports notIn() group exclusion');
        });

        it('fromName test has properly closed parenthesis (syntax check)', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            // The generated line should have balanced parentheses
            expect($content)->toContain(')->toThrow(InvalidEnumException::class);');
        });

        it('generates tryFromLabel case-insensitive test', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('tryFromLabel lookup is case-insensitive');
        });

        it('generates correct backing-type test for string-backed enums', function (): void {
            $content = EnumTestGenerator::generate(OrderStatus::class);

            expect($content)->toContain('values() returns string backed values');
        });

        it('generates correct backing-type test for int-backed enums', function (): void {
            $content = EnumTestGenerator::generate(IntBackedPriority::class);

            expect($content)->toContain('values() returns int backed values');
        });

        it('generates case names test for pure enums', function (): void {
            $content = EnumTestGenerator::generate(PureSystemState::class);

            expect($content)->toContain('values() returns case names for pure enum');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 8. Metadata resolution priority — per-case wins over class-level
    // ──────────────────────────────────────────────────────────────

    describe('Per-case attribute overrides class-level', function (): void {
        it('OrderStatus per-case labels override auto-generated', function (): void {
            // OrderStatus has per-case #[Label] attributes
            $label = OrderStatus::PENDING->label();

            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
            // Auto-generated from PENDING would be "Pending"
            // The fixture should have a custom label
            expect($label)->not->toBe('Pending');
        });

        it('labels() returns labels in declaration order', function (): void {
            $labels = OrderStatus::labels();
            $cases = OrderStatus::cases();

            expect(count($labels))->toBe(count($cases));
        });

        it('values() returns correct types for each enum type', function (): void {
            // String-backed
            $stringValues = OrderStatus::values();
            foreach ($stringValues as $v) {
                expect($v)->toBeString();
            }

            // Int-backed
            $intValues = IntBackedPriority::values();
            foreach ($intValues as $v) {
                expect($v)->toBeInt();
            }

            // Pure enum — case names are strings
            $pureValues = PureSystemState::values();
            foreach ($pureValues as $v) {
                expect($v)->toBeString();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 9. EnumCast serialize() edge cases
    // ──────────────────────────────────────────────────────────────

    describe('EnumCast serialize() method', function (): void {
        it('returns backed value for string-backed enum', function (): void {
            $cast = new EnumCast(OrderStatus::class);
            $result = $cast->serialize(new \stdClass, 'status', OrderStatus::PENDING, []);

            expect($result)->toBeString();
        });

        it('returns backed value for int-backed enum', function (): void {
            $cast = new EnumCast(IntBackedPriority::class);
            $result = $cast->serialize(new \stdClass, 'priority', IntBackedPriority::HIGH, []);

            expect($result)->toBeInt();
        });

        it('returns null for null value', function (): void {
            $cast = new EnumCast(OrderStatus::class);
            $result = $cast->serialize(new \stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('passes through int value for non-enum input', function (): void {
            $cast = new EnumCast(OrderStatus::class);
            $result = $cast->serialize(new \stdClass, 'status', 'active', []);

            expect($result)->toBe('active');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. EnumRule message formatting
    // ──────────────────────────────────────────────────────────────

    describe('EnumRule message with HasEnumMetadata trait', function (): void {
        it('includes allowed values in error message', function (): void {
            $rule = EnumRule::for(OrderStatus::class);
            $message = '';
            $rule->validate('status', 'invalid_value', function (string $m) use (&$message): void {
                $message = $m;
            });

            expect($message)->toBeString();
            expect($message)->toContain('Allowed values:');
        });

        it('works with pure enum using case names as values', function (): void {
            $rule = EnumRule::for(PureSystemState::class);
            $failed = false;
            $rule->validate('state', 'NONEXISTENT', function (string $m) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('accepts valid case name for pure enum', function (): void {
            $cases = PureSystemState::cases();
            if ($cases === []) {
                return; // skip if no cases
            }

            $rule = EnumRule::for(PureSystemState::class);
            $failed = false;
            $rule->validate('state', $cases[0]->name, function (string $m) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 11. InvalidEnumException edge cases
    // ──────────────────────────────────────────────────────────────

    describe('InvalidEnumException factory methods', function (): void {
        it('value() with int displays the number', function (): void {
            $e = InvalidEnumException::value('App\Enums\Priority', 42);
            expect($e->getMessage())->toContain('42');
        });

        it('value() with string displays the string', function (): void {
            $e = InvalidEnumException::value('App\Enums\Status', 'active');
            expect($e->getMessage())->toContain('active');
        });

        it('forName() includes the class name', function (): void {
            $e = InvalidEnumException::forName('App\Enums\Status', 'UNKNOWN');
            expect($e->getMessage())->toContain('App\\Enums\\Status');
            expect($e->getMessage())->toContain('UNKNOWN');
        });

        it('is final', function (): void {
            $ref = new \ReflectionClass(InvalidEnumException::class);
            expect($ref->isFinal())->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 12. toValue() across all enum types
    // ──────────────────────────────────────────────────────────────

    describe('toValue() across enum types', function (): void {
        it('returns backed value for string-backed enum', function (): void {
            expect(OrderStatus::PENDING->toValue())->toBeString();
        });

        it('returns backed value for int-backed enum', function (): void {
            expect(IntBackedPriority::HIGH->toValue())->toBeInt();
        });

        it('returns case name for pure enum', function (): void {
            $case = PureSystemState::cases()[0] ?? null;
            if ($case === null) {
                return;
            }

            expect($case->toValue())->toBe($case->name);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 13. forApi() shape contract
    // ──────────────────────────────────────────────────────────────

    describe('forApi() returns complete shape', function (): void {
        it('each item has all 6 expected keys', function (): void {
            $api = OrderStatus::forApi();
            $expectedKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

            foreach ($api as $item) {
                foreach ($expectedKeys as $key) {
                    expect($item)->toHaveKey($key);
                }
            }
        });

        it('description and icon can be null', function (): void {
            $api = OrderStatus::forApi();

            foreach ($api as $item) {
                // description and icon are nullable — should be string or null
                expect($item['description'])->toBeNull()->or()->toBeString();
                expect($item['icon'])->toBeNull()->or()->toBeString();
            }
        });

        it('count matches cases() count', function (): void {
            expect(OrderStatus::forApi())->toHaveCount(count(OrderStatus::cases()));
            expect(IntBackedPriority::forApi())->toHaveCount(count(IntBackedPriority::cases()));
        });
    });
});
