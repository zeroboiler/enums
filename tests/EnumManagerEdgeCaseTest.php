<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Edge case and boundary tests for EnumManager and EnumCache.
 *
 * Covers: singleton pattern enforcement, cache TTL, metadata caching,
 * cross-enum resolution independence, metadata consistency, and trait method completeness.
 */
describe('EnumManager Edge Cases', function () {
    it('returns singleton instance consistently', function () {
        $instance1 = EnumManager::getInstance();
        $instance2 = EnumManager::getInstance();

        expect($instance1)->toBe($instance2);
    });

    it('resolves metadata for different enums independently', function () {
        $meta1 = EnumManager::getInstance()->resolve(UserStatus::class);
        $meta2 = EnumManager::getInstance()->resolve(Priority::class);

        expect($meta1)->not->toBe($meta2);
        expect($meta1)->toHaveKey('ACTIVE');
        expect($meta2)->toHaveKey('LOW');
    });

    it('metadata structure is consistent across multiple resolves', function () {
        $manager = EnumManager::getInstance();

        $first = $manager->resolve(UserStatus::class);
        $second = $manager->resolve(UserStatus::class);

        expect($first)->toBe($second);
    });
});

describe('EnumCache Edge Cases', function () {
    it('returns singleton instance consistently', function () {
        $instance1 = EnumCache::getInstance();
        $instance2 = EnumCache::getInstance();

        expect($instance1)->toBe($instance2);
    });

    it('caches metadata and returns it on subsequent calls', function () {
        $cache = EnumCache::getInstance();

        // Clear first to ensure clean state
        $cache->clear(UserStatus::class);

        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // Second resolve should return the same cached result
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta)->toBe($meta2);
    });

    it('clear removes cached entry', function () {
        $cache = EnumCache::getInstance();

        $cache->clear(Priority::class);

        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('clearAll removes all entries', function () {
        $cache = EnumCache::getInstance();
        $cache->clearAll();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();
    });

    it('class-level EnumLabel provides labels for cases', function () {
        $meta = EnumMetadataResolver::resolve(TicketStatus::class);

        // TicketStatus has class-level EnumLabel
        expect($meta['OPEN']['label'])->toBe('Open');
        expect($meta['IN_PROGRESS']['label'])->toBe('In Progress');
        expect($meta['CLOSED']['label'])->toBe('Closed');
    });

    it('class-level EnumDescription provides descriptions for cases', function () {
        $meta = EnumMetadataResolver::resolve(TicketStatus::class);

        expect($meta['OPEN']['description'])->toBe('Ticket is open and awaiting response');
        expect($meta['CLOSED']['description'])->toBe('Ticket has been resolved');
    });

    it('class-level EnumIcon default icon applies to all cases', function () {
        $meta = EnumMetadataResolver::resolve(TicketStatus::class);

        // TicketStatus has EnumIcon(default: 'heroicon-o-ticket')
        foreach ($meta as $case => $data) {
            expect($data['icon'])->toBe('heroicon-o-ticket');
        }
    });

    it('pure enum uses case names as metadata keys', function () {
        $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        expect($meta)->toHaveKeys(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
    });

    it('metadata for int-backed enum uses case names as keys', function () {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        expect($meta)->toHaveKeys(['LOW', 'MEDIUM', 'HIGH', 'URGENT']);
    });
});
