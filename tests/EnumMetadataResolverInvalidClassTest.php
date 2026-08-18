<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumMetadataResolver invalid class handling', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('throws LogicException when resolving a non-enum class', function () {
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
            ->toThrow(\LogicException::class, 'is not a valid enum class');
    });

    it('throws LogicException when resolving a non-existent class', function () {
        expect(fn () => EnumMetadataResolver::resolve('App\\Enums\\NonExistentEnum'))
            ->toThrow(\LogicException::class);
    });

    it('throws LogicException when resolving a regular class that is not an enum', function () {
        expect(fn () => EnumMetadataResolver::resolve(\LogicException::class))
            ->toThrow(\LogicException::class, 'is not a valid enum class');
    });

    it('resolves successfully for a valid enum class', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta)->toBeArray()
            ->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('caches the result after first resolve', function () {
        EnumCache::getInstance()->setTtl(300);

        EnumMetadataResolver::resolve(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        // Second resolve should come from cache
        $meta = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta)->toBeArray();
    });

    it('invalidate removes cached metadata for a specific class', function () {
        EnumCache::getInstance()->setTtl(300);

        EnumMetadataResolver::resolve(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidate(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('invalidateAll flushes all cached metadata', function () {
        EnumCache::getInstance()->setTtl(300);

        EnumMetadataResolver::resolve(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });
});
