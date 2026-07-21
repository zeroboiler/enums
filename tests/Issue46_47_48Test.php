<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TranslatableStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function (): void {
    EnumCache::resetInstance();
    EnumMetadataResolver::resetCache();
});

describe('Issue #46: clearClass() removes orphaned timestamps', function (): void {
    it('clearClass() also removes the timestamp entry', function (): void {
        $cache = EnumCache::getInstance();

        // Populate cache
        EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Clear specific class
        $cache->clearClass(UserStatus::class);

        // Verify the cache entry is gone
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Use reflection to check timestamps are also cleared
        $ref = new ReflectionClass($cache);
        $timestampsProp = $ref->getProperty('cacheTimestamps');
        $timestamps = $timestampsProp->getValue($cache);

        expect($timestamps)->not->toHaveKey(UserStatus::class);
    });

    it('clearClass() does not affect other enum timestamps', function (): void {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(OrderStatus::class);

        $cache->clearClass(UserStatus::class);

        // OrderStatus should still be cached
        expect($cache->has(OrderStatus::class))->toBeTrue();
    });
});

describe('Issue #47: Attribute instantiation memoization', function (): void {
    it('caches attribute instances and avoids re-instantiating', function (): void {
        // Resolve once to populate caches
        EnumMetadataResolver::resolve(UserStatus::class);

        // Use reflection to check the attribute cache is populated
        $ref = new ReflectionClass(EnumMetadataResolver::class);
        $cacheProp = $ref->getProperty('attributeCache');
        $attrCache = $cacheProp->getValue();

        // Should have entries for UserStatus class and its cases
        expect($attrCache)->toHaveKey('class:'.UserStatus::class);
        expect($attrCache)->toHaveKey('case:'.UserStatus::class.':ACTIVE');
        expect($attrCache)->toHaveKey('case:'.UserStatus::class.':BANNED');
    });

    it('produces identical results regardless of memoization', function (): void {
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);

        // Reset just the enum cache (not attribute cache) and re-resolve
        EnumCache::getInstance()->clearClass(UserStatus::class);

        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta1)->toBe($meta2);
    });

    it('resetCache() clears the attribute memoization', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);

        EnumMetadataResolver::resetCache();

        $ref = new ReflectionClass(EnumMetadataResolver::class);
        $cacheProp = $ref->getProperty('attributeCache');
        $attrCache = $cacheProp->getValue();

        expect($attrCache)->toBeEmpty();
    });
});

describe('Issue #48: Translation key support', function (): void {
    it('resolves per-case translation key for Label attribute', function (): void {
        // In test environment without Laravel translator, __() returns the key as-is
        $label = TranslatableStatus::ACTIVE->label();

        expect($label)->toBe('enums.translatable_status.active');
    });

    it('resolves class-level translation keys for EnumLabel attribute', function (): void {
        $label = TranslatableStatus::INACTIVE->label();

        expect($label)->toBe('enums.translatable_status.inactive');
    });

    it('Label with translationKey takes precedence over value', function (): void {
        $meta = EnumMetadataResolver::resolve(TranslatableStatus::class);

        expect($meta['labels']['active'])->toBe('enums.translatable_status.active');
    });

    it('falls back to static label when no translation key is set', function (): void {
        // UserStatus uses #[Label('Active User')] — no translation key
        $label = UserStatus::ACTIVE->label();

        expect($label)->toBe('Active User');
    });
});
