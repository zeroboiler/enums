<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

beforeEach(function () {
    EnumCache::resetInstance();
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('V38 PHPStan L9 structural integrity audit', function () {
    it('EnumCache::getInstance() returns the same singleton across multiple calls', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('EnumCache TTL of 0 disables caching entirely', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set(TestBackedV38::class, [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(TestBackedV38::class))->toBeFalse();
    });

    it('EnumCache negative TTL is normalized to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('EnumCache clear() removes all entries but preserves TTL setting', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(600);
        $cache->set(TestBackedV38::class, $validMeta());
        $cache->set(TestPureV38::class, $validMeta());

        $cache->clear();

        expect($cache->has(TestBackedV38::class))->toBeFalse();
        expect($cache->has(TestPureV38::class))->toBeFalse();
        expect($cache->getTtl())->toBe(600);
    });

    it('EnumCache clearClass() targets a single class only', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(600);
        $cache->set(TestBackedV38::class, $validMeta());
        $cache->set(TestPureV38::class, $validMeta());

        $cache->clearClass(TestBackedV38::class);

        expect($cache->has(TestBackedV38::class))->toBeFalse();
        expect($cache->has(TestPureV38::class))->toBeTrue();
    });

    it('EnumCache::flush() is a static convenience that clears all', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(600);
        $cache->set(TestBackedV38::class, $validMeta());

        EnumCache::flush();

        // getInstance returns a fresh singleton after resetInstance, but flush
        // just clears entries — same singleton, empty cache
        expect($cache->has(TestBackedV38::class))->toBeFalse();
    });

    it('EnumCache __debugInfo hides cache internals', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(TestBackedV38::class, $validMeta());

        $debug = $cache->__debugInfo();

        expect($debug)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
        expect($debug['cachedClasses'])->toBe(1);
        expect($debug['ttl'])->toBe(300);
    });

    it('EnumMetadataResolver::invalidate() removes only the targeted class', function () {
        EnumMetadataResolver::resolve(TestBackedV38::class);
        EnumMetadataResolver::resolve(TestPureV38::class);

        EnumMetadataResolver::invalidate(TestBackedV38::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(TestBackedV38::class))->toBeFalse();
        expect($cache->has(TestPureV38::class))->toBeTrue();
    });

    it('EnumMetadataResolver::invalidateAll() clears entire cache', function () {
        EnumMetadataResolver::resolve(TestBackedV38::class);
        EnumMetadataResolver::resolve(TestPureV38::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(TestBackedV38::class))->toBeFalse();
        expect($cache->has(TestPureV38::class))->toBeFalse();
    });

    it('InvalidEnumException::value() handles null display', function () {
        $ex = InvalidEnumException::value(TestBackedV38::class, null);

        expect($ex->getMessage())->toContain('null');
        expect($ex->__toString())->toContain('InvalidEnumException');
    });

    it('InvalidEnumException::value() handles int display', function () {
        $ex = InvalidEnumException::value(TestIntBackedV38::class, 99);

        expect($ex->getMessage())->toContain('99');
    });

    it('InvalidEnumException::forName() includes both name and class', function () {
        $ex = InvalidEnumException::forName(TestBackedV38::class, 'NONEXISTENT');

        expect($ex->getMessage())->toContain('NONEXISTENT');
        expect($ex->getMessage())->toContain(TestBackedV38::class);
    });
});

describe('V38 backed enum edge cases', function () {
    it('zero-value int-backed enum resolves correctly', function () {
        $case = TestIntBackedV38::ZERO;

        expect($case->value)->toBe(0);
        expect($case->label())->toBe('Zero');
        expect(TestIntBackedV38::values())->toContain(0);
        expect(TestIntBackedV38::tryFromName('ZERO'))->toBe($case);
    });

    it('tryFromLabel is case-insensitive across all label variants', function () {
        $resolved = TestBackedV38::tryFromLabel('active user');

        expect($resolved)->toBe(TestBackedV38::ACTIVE);
    });

    it('is() rejects different enum of the same type via string comparison', function () {
        $case = TestBackedV38::ACTIVE;

        expect($case->is('INACTIVE'))->toBeFalse();
        expect($case->is('active'))->toBeFalse(); // case-sensitive name, not value
    });

    it('forSelect() returns consistent key order matching cases() order', function () {
        $select = TestBackedV38::forSelect();
        $names = array_column($select, 'value');

        $expectedOrder = array_map(
            fn (TestBackedV38 $c) => $c->value,
            TestBackedV38::cases(),
        );

        expect($names)->toBe($expectedOrder);
    });

    it('forApi() returns all required metadata keys for every case', function () {
        $api = TestBackedV38::forApi();
        $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

        foreach ($api as $entry) {
            foreach ($requiredKeys as $key) {
                expect($entry)->toHaveKey($key);
            }
        }
    });

    it('forApi() color is never empty string', function () {
        $api = TestBackedV38::forApi();

        foreach ($api as $entry) {
            expect($entry['color'])->toBeString();
            expect($entry['color'])->not->toBe('');
        }
    });
});

describe('V38 pure enum edge cases', function () {
    it('pure enum values() returns case names, not backed values', function () {
        $values = TestPureV38::values();

        expect($values)->toBe(['ACTIVE', 'INACTIVE', 'DRAFT']);
    });

    it('pure enum forSelect() uses case names as values', function () {
        $select = TestPureV38::forSelect();

        foreach ($select as $option) {
            expect($option['value'])->toBeIn(['ACTIVE', 'INACTIVE', 'DRAFT']);
        }
    });

    it('pure enum forApi() uses case names as values', function () {
        $api = TestPureV38::forApi();

        foreach ($api as $entry) {
            expect($entry['value'])->toBeIn(['ACTIVE', 'INACTIVE', 'DRAFT']);
            expect($entry['name'])->toBe($entry['value']);
        }
    });

    it('pure enum comparison works with string case names', function () {
        expect(TestPureV38::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(TestPureV38::ACTIVE->isNot('DRAFT'))->toBeTrue();
        expect(TestPureV38::ACTIVE->in(['ACTIVE', 'DRAFT']))->toBeTrue();
        expect(TestPureV38::ACTIVE->notIn(['INACTIVE', 'DRAFT']))->toBeTrue();
    });
});

describe('V38 EnumRule validation', function () {
    it('string-backed rule rejects non-string values', function () {
        $rule = EnumRule::for(TestBackedV38::class);
        $failed = false;

        $rule->validate('status', 123, function (string $msg) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('int-backed rule rejects string values', function () {
        $rule = EnumRule::for(TestIntBackedV38::class);
        $failed = false;

        $rule->validate('priority', 'high', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('int-backed rule rejects float values', function () {
        $rule = EnumRule::for(TestIntBackedV38::class);
        $failed = false;

        $rule->validate('priority', 1.5, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('pure enum rule rejects non-string values', function () {
        $rule = EnumRule::for(TestPureV38::class);
        $failed = false;

        $rule->validate('state', 123, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('pure enum rule accepts valid case name strings', function () {
        $rule = EnumRule::for(TestPureV38::class);
        $failed = false;

        $rule->validate('state', 'ACTIVE', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('nullable rule passes for null values', function () {
        $rule = EnumRule::for(TestBackedV38::class)->nullable();
        $failed = false;

        $rule->validate('status', null, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('non-nullable rule rejects null values', function () {
        $rule = EnumRule::for(TestBackedV38::class);
        $failed = false;

        $rule->validate('status', null, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('nullable rule still validates non-null invalid values', function () {
        $rule = EnumRule::for(TestBackedV38::class)->nullable();
        $failed = false;

        $rule->validate('status', 'nonexistent', function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

// ── Helper ─────────────────────────────────────────────────────

function validMeta(): array
{
    return [
        'labels' => ['a' => 'A'],
        'descriptions' => [],
        'colors' => [],
        'icons' => [],
    ];
}

// ── Fixtures ───────────────────────────────────────────────────

enum TestBackedV38: string
{
    use HasEnumMetadata;

    #[\ZeroBoiler\Enums\Attributes\Label('Active User')]
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DRAFT = 'draft';
}

enum TestIntBackedV38: int
{
    use HasEnumMetadata;

    #[\ZeroBoiler\Enums\Attributes\Label('Zero')]
    case ZERO = 0;
    case LOW = 1;
    case HIGH = 2;
}

enum TestPureV38
{
    use HasEnumMetadata;

    case ACTIVE;
    case INACTIVE;
    case DRAFT;
}
