<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * V33 — EnumCache serialization safety and singleton contract tests.
 *
 * Targets:
 * - __clone() always throws RuntimeException
 * - __wakeup() always throws RuntimeException
 * - __serialize() always throws RuntimeException
 * - __unserialize() always throws RuntimeException with ignored $data
 * - Singleton identity: getInstance() returns same instance
 * - resetInstance creates fresh instance
 * - Concurrent getInstance calls after reset return new instance
 * - EnumMetadataResolver resolves correctly after reset
 * - TTL expiry triggers re-resolution via reflection
 * - Cache get/set round-trip preserves structure
 */
test('__clone throws RuntimeException with descriptive message', function (): void {
    $cache = EnumCache::getInstance();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('singleton and cannot be cloned');

    clone $cache;
});

test('__wakeup throws RuntimeException with descriptive message', function (): void {
    $cache = EnumCache::getInstance();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('singleton and cannot be unserialized');

    $cache->__wakeup();
});

test('__serialize throws RuntimeException with descriptive message', function (): void {
    $cache = EnumCache::getInstance();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('singleton and cannot be serialized');

    $cache->__serialize();
});

test('__unserialize throws RuntimeException and ignores data parameter', function (): void {
    $cache = EnumCache::getInstance();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('singleton and cannot be unserialized');

    // Pass arbitrary data — must be ignored
    $cache->__unserialize(['labels' => ['fake'], 'colors' => ['fake']]);
});

test('getInstance returns same instance on consecutive calls', function (): void {
    EnumCache::resetInstance();

    $a = EnumCache::getInstance();
    $b = EnumCache::getInstance();

    expect($a)->toBe($b);
});

test('resetInstance creates a new distinct instance', function (): void {
    EnumCache::resetInstance();

    $first = EnumCache::getInstance();
    $first->setTtl(999); // Custom TTL to verify it's gone after reset

    EnumCache::resetInstance();

    $second = EnumCache::getInstance();

    // New instance should have default TTL, not 999
    expect($second)->not->toBe($first);
    expect($second->getTtl())->toBe(300);
});

test('cache round-trip preserves metadata structure', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(300);

    $metadata = [
        'labels' => ['active' => 'Active', 'banned' => 'Banned'],
        'descriptions' => ['active' => 'Can login'],
        'colors' => ['active' => 'success', 'banned' => 'danger'],
        'icons' => ['active' => 'heroicon-o-check'],
    ];

    $cache->set('TestEnum', $metadata);

    expect($cache->has('TestEnum'))->toBeTrue();

    $retrieved = $cache->get('TestEnum');
    expect($retrieved)->toBe($metadata);
});

test('cache clear removes entry but leaves other entries intact', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(300);

    $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
    $cache->set('EnumB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

    expect($cache->has('EnumA'))->toBeTrue();
    expect($cache->has('EnumB'))->toBeTrue();

    $cache->clearClass('EnumA');

    expect($cache->has('EnumA'))->toBeFalse();
    expect($cache->has('EnumB'))->toBeTrue();
});

test('flush clears all entries via static method', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(300);

    $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
    $cache->set('EnumB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

    EnumCache::flush();

    expect($cache->has('EnumA'))->toBeFalse();
    expect($cache->has('EnumB'))->toBeFalse();
});

test('TTL expiry causes has() to return false and triggers re-resolution', function (): void {
    EnumCache::resetInstance();
    $cache = EnumCache::getInstance();
    $cache->setTtl(0); // Disable caching

    // Even after explicit set, TTL=0 means always stale
    $cache->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
    expect($cache->has('TestEnum'))->toBeFalse();

    // But resolution still works (bypasses cache when TTL=0)
    $meta = EnumMetadataResolver::resolve(UserStatus::class);
    expect($meta)->toBeArray();
    expect($meta)->toHaveKey('labels');

    // Restore
    $cache->setTtl(300);
});

test('EnumMetadataResolver::resolve returns same array on second call when cached', function (): void {
    EnumCache::resetInstance();

    $first = EnumMetadataResolver::resolve(UserStatus::class);
    $second = EnumMetadataResolver::resolve(UserStatus::class);

    expect($first)->toBe($second); // Same reference (cached)
});

test('EnumMetadataResolver::invalidate followed by resolve produces fresh metadata', function (): void {
    EnumCache::resetInstance();

    $first = EnumMetadataResolver::resolve(UserStatus::class);
    EnumMetadataResolver::invalidate(UserStatus::class);

    // After invalidation, re-resolve should still return correct structure
    $second = EnumMetadataResolver::resolve(UserStatus::class);

    expect($second)->toBeArray();
    expect($second)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    // Content should match (same enum, same resolution)
    expect($second)->toBe($first);
});

test('forSelect returns array with value and label keys for every enum type', function (): void {
    $enums = [
        'string-backed' => UserStatus::class,
        'int-backed' => Priority::class,
        'pure' => PureFeatureFlag::class,
        'single-case' => SingleCaseToggle::class,
        'zero-value' => ZeroPriority::class,
    ];

    foreach ($enums as $type => $enumClass) {
        $select = $enumClass::forSelect();

        expect($select)->not->toBeEmpty();
        foreach ($select as $item) {
            expect($item)->toBeArray();
            expect($item)->toHaveKeys(['value', 'label']);
            expect($item['label'])->toBeString();
            expect($item['label'])->not->toBeEmpty();
        }
    }
});

test('forApi returns array with all six metadata keys for every enum type', function (): void {
    $enums = [
        'string-backed' => UserStatus::class,
        'int-backed' => Priority::class,
        'pure' => PureFeatureFlag::class,
    ];

    foreach ($enums as $type => $enumClass) {
        $api = $enumClass::forApi();

        expect($api)->not->toBeEmpty();
        foreach ($api as $item) {
            expect($item)->toBeArray();
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect(is_string($item['color']))->toBeTrue();
            expect($item['color'])->not->toBeEmpty();
        }
    }
});

test('InvalidEnumException forName includes both class and name in message', function (): void {
    $ex = InvalidEnumException::forName('App\\Enums\\UserStatus', 'INVALID');

    expect($ex->getMessage())->toContain('INVALID');
    expect($ex->getMessage())->toContain('App\\Enums\\UserStatus');
    expect($ex->getMessage())->toContain('does not exist');
});

test('InvalidEnumException is an instance of Exception', function (): void {
    $ex = InvalidEnumException::forName('TestEnum', 'BAD');

    expect($ex)->toBeInstanceOf(\Exception::class);
    expect($ex->getCode())->toBe(0);
});
