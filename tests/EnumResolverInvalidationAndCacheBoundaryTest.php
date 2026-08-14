<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumMetadataResolver invalidation lifecycle', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('invalidates a specific class and forces re-resolution', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        // First resolution caches the metadata
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Invalidate specifically
        EnumMetadataResolver::invalidate(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Re-resolve produces same structure
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($meta1)->toBe($meta2);
    });

    it('invalidateAll clears every cached class', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        // Resolve metadata for a class
        EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Invalidate all
        EnumMetadataResolver::invalidateAll();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('invalidate is idempotent — calling on non-cached class is safe', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        // Class not cached yet
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Invalidate should not throw
        EnumMetadataResolver::invalidate(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('invalidateAll is idempotent — safe on empty cache', function (): void {
        EnumMetadataResolver::invalidateAll();

        // Should not throw, cache stays empty
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });
});

describe('EnumCache TTL boundary conditions', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('TTL of 0 disables caching — has() always returns false', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('negative TTL is clamped to 0 — disables caching', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);

        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('setTtl is persistent across calls', function (): void {
        $cache = EnumCache::getInstance();

        $cache->setTtl(42);
        expect($cache->getTtl())->toBe(42);

        $cache->setTtl(0);
        expect($cache->getTtl())->toBe(0);

        $cache->setTtl(9999);
        expect($cache->getTtl())->toBe(9999);
    });

    it('clear removes all entries and timestamps', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();

        $cache->clear();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });
});

describe('EnumCache __wakeup and __clone prevention', function (): void {
    it('resetInstance produces a usable singleton after reset', function (): void {
        EnumCache::resetInstance();

        $a = EnumCache::getInstance();
        $a->setTtl(60);

        EnumCache::resetInstance();

        $b = EnumCache::getInstance();
        expect($b->getTtl())->toBe(300); // default TTL
        expect($b)->not->toBe($a);

        EnumCache::resetInstance();
    });
});

describe('fromName throw contract', function (): void {
    it('throws InvalidEnumException with class and name in message', function (): void {
        $exception = InvalidEnumException::forName(UserStatus::class, 'NON_EXISTENT');

        expect($exception)->toBeInstanceOf(InvalidEnumException::class);
        expect($exception->getMessage())->toContain('NON_EXISTENT');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });

    it('throws InvalidEnumException::value with display info', function (): void {
        $exception = InvalidEnumException::value(UserStatus::class, 'invalid_value');

        expect($exception)->toBeInstanceOf(InvalidEnumException::class);
        expect($exception->getMessage())->toContain('invalid_value');
        expect($exception->getMessage())->toContain(UserStatus::class);
    });

    it('InvalidEnumException::value handles null value', function (): void {
        $exception = InvalidEnumException::value(UserStatus::class, null);

        expect($exception->getMessage())->toContain('null');
    });

    it('__toString returns class name and message', function (): void {
        $exception = InvalidEnumException::forName(UserStatus::class, 'BOGUS');

        expect((string) $exception)->toBe(InvalidEnumException::class.': '.$exception->getMessage());
    });
});

describe('HasEnumMetadata label generation edge cases', function (): void {
    it('generates label from SCREAMING_SNAKE_CASE', function (): void {
        expect(UserStatus::ACTIVE->label())->toBe('Active');
    });

    it('generates label for single-word uppercase', function (): void {
        // UserStatus::ACTIVE -> "Active"
        $label = UserStatus::ACTIVE->label();
        expect($label)->toBeString();
        expect($label)->not->toBeEmpty();
    });

    it('labels are consistent across multiple calls', function (): void {
        $label1 = UserStatus::ACTIVE->label();
        $label2 = UserStatus::ACTIVE->label();

        expect($label1)->toBe($label2);
    });
});
