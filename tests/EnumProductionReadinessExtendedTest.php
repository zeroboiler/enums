<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumTestGenerator output structure', function (): void {
    it('generates valid PHP for a string-backed enum', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain('declare(strict_types=1)');
        expect($content)->toContain('use '.UserStatus::class.';');
        expect($content)->toContain("describe('UserStatus enum'");
        expect($content)->toContain("it('has cases'");
        expect($content)->toContain("it('can generate select options'");
        expect($content)->toContain("it('can generate API response array'");
        expect($content)->toContain("it('supports tryFromName lookup'");
        expect($content)->toContain("it('fromName() throws InvalidEnumException'");
        expect($content)->toContain('expect(UserStatus::fromName');
    });

    it('generates per-case label and color tests', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain("it('has a non-empty label for case ACTIVE'");
        expect($content)->toContain("it('has a string color for case ACTIVE'");
        expect($content)->toContain("it('returns a string or null icon for case ACTIVE'");
        expect($content)->toContain("it('returns a string or null description for case ACTIVE'");
    });

    it('generates comparison tests when enum has >= 2 cases', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toContain("it('supports is() comparison with instance'");
        expect($content)->toContain("it('supports isNot() comparison'");
        expect($content)->toContain("it('supports in() group matching'");
    });

    it('generates correct case count', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toContain('toHaveCount(4)');
    });

    it('generates int backing type test for int-backed enums', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(Priority::class);

        expect($content)->toContain("it('values() returns int backed values'");
        expect($content)->toContain('expect($values)->each->toBeInt()');
    });

    it('generates label case-insensitivity test', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain("it('tryFromLabel lookup is case-insensitive'");
    });
});

describe('EnumCache TTL precision', function (): void {
    it('entries expire exactly at TTL boundary', function (): void {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Cached Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Immediately after set, entry should exist
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->get(UserStatus::class)['labels']['active'])->toBe('Cached Active');

        // Wait for TTL to expire
        sleep(2);

        // After TTL, entry should no longer exist
        expect($cache->has(UserStatus::class))->toBeFalse();

        EnumCache::resetInstance();
    });

    it('TTL of 0 disables caching — has() always returns false', function (): void {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();

        EnumCache::resetInstance();
    });

    it('negative TTL is normalized to 0', function (): void {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);

        EnumCache::resetInstance();
    });

    it('flush() clears all entries including timestamps', function (): void {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(OrderStatus::class, [
            'labels' => ['pending' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clear();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeFalse();

        EnumCache::resetInstance();
    });

    it('clearClass() only clears the specified class', function (): void {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'User'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(OrderStatus::class, [
            'labels' => ['pending' => 'Order'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        EnumCache::resetInstance();
    });
});

describe('EnumMetadataResolver invalidation', function (): void {
    it('invalidate forces rebuild on next resolve', function (): void {
        EnumCache::resetInstance();

        // First resolve populates cache
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta1['labels']['active'] ?? null)->toBe('Active User');

        // Invalidate
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Re-resolve should rebuild (not return stale)
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta2['labels']['active'] ?? null)->toBe('Active User');

        EnumCache::resetInstance();
    });

    it('invalidateAll clears every cached class', function (): void {
        EnumCache::resetInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(OrderStatus::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeFalse();

        EnumCache::resetInstance();
    });
});

describe('EnumRule nullable + backed type mismatch', function (): void {
    it('nullable instance passes for null value', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class)->nullable();

        $fail = false;
        $failMessage = null;

        $rule->validate('status', null, function (string $message) use (&$fail, &$failMessage): void {
            $fail = true;
            $failMessage = $message;
        });

        expect($fail)->toBeFalse();
    });

    it('nullable instance fails for invalid value', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class)->nullable();

        $fail = false;

        $rule->validate('status', 'nonexistent_value', function (): void {
            $fail = true;
        });

        expect($fail)->toBeTrue();
    });

    it('rejects string value for int-backed enum', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(Priority::class);

        $fail = false;

        // Priority is int-backed, passing a string should fail
        $rule->validate('priority', 'high', function (): void {
            $fail = true;
        });

        expect($fail)->toBeTrue();
    });

    it('accepts correct int value for int-backed enum', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(Priority::class);

        $fail = false;

        $rule->validate('priority', 1, function (): void {
            $fail = true;
        });

        expect($fail)->toBeFalse();
    });
});

describe('InvalidEnumException factory methods', function (): void {
    it('value() formats null display correctly', function (): void {
        $exception = InvalidEnumException::value(UserStatus::class, null);

        expect($exception->getMessage())->toContain('null');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });

    it('value() formats int display correctly', function (): void {
        $exception = InvalidEnumException::value(Priority::class, 99);

        expect($exception->getMessage())->toContain('99');
        expect($exception->getMessage())->toContain(Priority::class);
    });

    it('value() formats string display correctly', function (): void {
        $exception = InvalidEnumException::value(UserStatus::class, 'unknown');

        expect($exception->getMessage())->toContain('unknown');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });

    it('forName() includes both name and class', function (): void {
        $exception = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

        expect($exception->getMessage())->toContain('NONEXISTENT');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });
});

describe('forSelect value uniqueness guarantee', function (): void {
    it('all forSelect values are unique per enum', function (): void {
        $select = UserStatus::forSelect();
        $values = array_column($select, 'value');

        expect($values)->toEqual(array_unique($values));
        expect(count($values))->toBe(count(array_unique($values)));
    });

    it('int-backed enum forSelect has int values', function (): void {
        $select = Priority::forSelect();

        foreach ($select as $option) {
            expect($option['value'])->toBeInt();
        }
    });

    it('string-backed enum forSelect has string values', function (): void {
        $select = UserStatus::forSelect();

        foreach ($select as $option) {
            expect($option['value'])->toBeString();
        }
    });
});

describe('forApi structure completeness', function (): void {
    it('every forApi entry has all six required keys', function (): void {
        $api = UserStatus::forApi();
        $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

        foreach ($api as $entry) {
            foreach ($requiredKeys as $key) {
                expect(array_key_exists($key, $entry))->toBeTrue("Missing key: {$key}");
            }
        }
    });

    it('forApi color is never empty string', function (): void {
        $api = UserStatus::forApi();

        foreach ($api as $entry) {
            expect($entry['color'])->toBeString();
            expect($entry['color'])->not->toBeEmpty();
        }
    });

    it('forApi name matches case name', function (): void {
        $api = Priority::forApi();

        foreach ($api as $entry) {
            $case = Priority::from($entry['value']);
            expect($entry['name'])->toBe($case->name);
        }
    });
});

describe('values/labels count consistency', function (): void {
    it('values count matches cases count', function (): void {
        expect(UserStatus::values())->toHaveCount(count(UserStatus::cases()));
        expect(Priority::values())->toHaveCount(count(Priority::cases()));
        expect(OrderStatus::values())->toHaveCount(count(OrderStatus::cases()));
    });

    it('labels count matches cases count', function (): void {
        expect(UserStatus::labels())->toHaveCount(count(UserStatus::cases()));
        expect(Priority::labels())->toHaveCount(count(Priority::cases()));
    });

    it('every label is a non-empty string', function (): void {
        foreach (UserStatus::labels() as $label) {
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        }

        foreach (Priority::labels() as $label) {
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        }
    });
});
