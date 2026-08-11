<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

beforeEach(function () {
    EnumCache::resetInstance();
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('Enum singleton lifecycle and reset behavior', function () {
    it('resetInstance creates a fresh singleton', function () {
        $cache1 = EnumCache::getInstance();
        EnumCache::resetInstance();
        $cache2 = EnumCache::getInstance();

        // Different instances (singleton was reset)
        expect($cache1)->not->toBe($cache2);
    });

    it('getInstance returns same instance across multiple calls', function () {
        $cache1 = EnumCache::getInstance();
        $cache2 = EnumCache::getInstance();
        $cache3 = EnumCache::getInstance();

        expect($cache1)->toBe($cache2)->toBe($cache3);
    });

    it('flush clears all entries but keeps singleton alive', function () {
        $cache = EnumCache::getInstance();
        $cache->set(TestLifecycleEnum::class, [
            'labels' => ['a' => 'Test'],
            'descriptions' => [],
            'colors' => ['a' => 'success'],
            'icons' => [],
        ]);

        expect($cache->has(TestLifecycleEnum::class))->toBeTrue();

        EnumCache::flush();

        expect($cache->has(TestLifecycleEnum::class))->toBeFalse();
        // Singleton is still the same instance
        expect($cache)->toBe(EnumCache::getInstance());
    });

    it('clearClass removes only the specified class', function () {
        $cache = EnumCache::getInstance();
        $cache->set(TestLifecycleEnum::class, [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(TestLifecycleEnum2::class, [
            'labels' => ['x' => 'X'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(TestLifecycleEnum::class);

        expect($cache->has(TestLifecycleEnum::class))->toBeFalse();
        expect($cache->has(TestLifecycleEnum2::class))->toBeTrue();
    });

    it('throws OutOfBoundsException when get() called without has()', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('get() returns correct cached metadata', function () {
        $cache = EnumCache::getInstance();
        $metadata = [
            'labels' => ['a' => 'Alpha', 'b' => 'Beta'],
            'descriptions' => ['a' => 'First'],
            'colors' => ['a' => 'success', 'b' => 'danger'],
            'icons' => [],
        ];
        $cache->set(TestLifecycleEnum::class, $metadata);

        $result = $cache->get(TestLifecycleEnum::class);

        expect($result)->toBe($metadata);
    });

    it('invalidateAll via resolver clears everything', function () {
        $cache = EnumCache::getInstance();
        $cache->set(TestLifecycleEnum::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumMetadataResolver::invalidateAll();

        expect($cache->has(TestLifecycleEnum::class))->toBeFalse();
    });

    it('fromName() throws InvalidEnumException with correct message', function () {
        try {
            TestLifecycleEnum::fromName('NON_EXISTENT');
            expect(true)->toBeFalse(); // Should not reach here
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('NON_EXISTENT');
            expect($e->getMessage())->toContain(TestLifecycleEnum::class);
        }
    });

    it('value() factory method formats null correctly', function () {
        $exception = InvalidEnumException::value(TestLifecycleEnum::class, null);

        expect($exception->getMessage())->toContain('null');
        expect($exception->getMessage())->toContain(TestLifecycleEnum::class);
    });

    it('value() factory method formats int and string values', function () {
        $intException = InvalidEnumException::value(TestLifecycleIntEnum::class, 999);
        $strException = InvalidEnumException::value(TestLifecycleEnum::class, 'unknown');

        expect($intException->getMessage())->toContain('999');
        expect($strException->getMessage())->toContain('unknown');
    });

    it('ttl of 0 makes has() always return false', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(TestLifecycleEnum::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(TestLifecycleEnum::class))->toBeFalse();
    });

    it('negative ttl is normalized to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
    });
});

// ── Fixtures ────────────────────────────────────────────────────

enum TestLifecycleEnum: string
{
    use HasEnumMetadata;

    case A = 'a';
    case B = 'b';
}

enum TestLifecycleEnum2: string
{
    use HasEnumMetadata;

    case X = 'x';
}

enum TestLifecycleIntEnum: int
{
    use HasEnumMetadata;

    case LOW = 1;
    case HIGH = 10;
}
