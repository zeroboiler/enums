<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumMetadataResolver cross-enum cache isolation', function (): void {
    beforeEach(function (): void {
        EnumCache::flush();
    });

    afterEach(function (): void {
        EnumCache::flush();
    });

    it('resolves multiple enum classes without cache cross-contamination', function (): void {
        $userMeta = EnumMetadataResolver::resolve(UserStatus::class);
        $priorityMeta = EnumMetadataResolver::resolve(Priority::class);

        // UserStatus labels should contain string-backed keys
        expect($userMeta['labels'])->toHaveKey('active');

        // Priority labels should contain int-backed keys (cast to string)
        expect($priorityMeta['labels'])->toHaveKey('1');

        // Labels should be different
        expect($userMeta['labels'])->not->toBe($priorityMeta['labels']);
    });

    it('invalidating one enum does not affect another', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        // Invalidate only UserStatus
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Priority should still be cached
        $cache = EnumCache::getInstance();
        expect($cache->has(Priority::class))->toBeTrue();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('invalidateAll clears all enum caches', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('clearing a single class removes its cache entry only', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumCache::getInstance()->clearClass(UserStatus::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });
});

describe('EnumMetadataResolver with single-case enum', function (): void {
    beforeEach(function (): void {
        EnumCache::flush();
    });

    afterEach(function (): void {
        EnumCache::flush();
    });

    it('resolves metadata for single-case enum', function (): void {
        $meta = EnumMetadataResolver::resolve(SingleCaseEnum::class);

        expect($meta['labels'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
        expect($meta['colors'])->toBeArray();
        expect($meta['icons'])->toBeArray();
    });

    it('single-case enum forSelect returns single entry', function (): void {
        $select = SingleCaseEnum::forSelect();

        expect($select)->toHaveCount(1);
        expect($select[0])->toHaveKey('value');
        expect($select[0])->toHaveKey('label');
    });

    it('single-case enum forApi returns single entry', function (): void {
        $api = SingleCaseEnum::forApi();

        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKey('value');
        expect($api[0])->toHaveKey('name');
        expect($api[0])->toHaveKey('label');
    });

    it('single-case enum values returns single value', function (): void {
        $values = SingleCaseEnum::values();

        expect($values)->toHaveCount(1);
    });
});

describe('EnumMetadataResolver cache TTL behavior with multiple enums', function (): void {
    beforeEach(function (): void {
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(0); // Disable caching
    });

    afterEach(function (): void {
        EnumCache::getInstance()->setTtl(300); // Reset to default
        EnumCache::flush();
    });

    it('with TTL=0, every resolve call rebuilds metadata', function (): void {
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        // Both should be identical in structure
        expect($meta1)->toBe($meta2);
    });
});

describe('EnumMetadataResolver with CamelCase enum', function (): void {
    beforeEach(function (): void {
        EnumCache::flush();
    });

    afterEach(function (): void {
        EnumCache::flush();
    });

    it('resolves metadata for camelCase enum names', function (): void {
        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::class);

        expect($meta['labels'])->toBeArray();
    });

    it('camelCase enum generates correct labels', function (): void {
        $label = \ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::ADMIN->label();

        expect($label)->toBeString();
        expect($label)->not->toBeEmpty();
    });
});
