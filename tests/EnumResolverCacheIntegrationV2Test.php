<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Integration tests for EnumMetadataResolver + EnumCache interaction.
 *
 * Verifies cache invalidation, multi-enum isolation, TTL expiry,
 * and resolver edge cases across different enum types.
 *
 * These tests complement the existing EnumMetadataResolverTest and
 * EnumCacheBehaviourTest by focusing on cross-resolver interactions
 * and real-world usage patterns.
 */

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumMetadataResolver + EnumCache integration', function (): void {

    beforeEach(function (): void {
        EnumCache::getInstance()->clear();
        EnumCache::getInstance()->setTtl(300);
        EnumMetadataResolver::invalidateAll();
    });

    afterEach(function (): void {
        EnumCache::getInstance()->clear();
        EnumCache::getInstance()->setTtl(300);
        EnumCache::getInstance()->resetInstance();
    });

    // ──────────────────────────────────────────────────────────────
    // Cache invalidation forces rebuild
    // ──────────────────────────────────────────────────────────────

    describe('Cache invalidation rebuilds metadata', function (): void {
        it('invalidate() forces next resolve() to rebuild', function (): void {
            // First resolve — populates cache
            $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta1['labels'])->toHaveKey('active');

            // Invalidate
            EnumMetadataResolver::invalidate(UserStatus::class);
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();

            // Second resolve — rebuilds from reflection
            $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta2)->toBe($meta1);
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        });

        it('invalidateAll() clears all cached enums', function (): void {
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(Priority::class);
            EnumMetadataResolver::resolve(PureFeatureFlag::class);

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
            expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();
            expect(EnumCache::getInstance()->has(PureFeatureFlag::class))->toBeTrue();

            EnumMetadataResolver::invalidateAll();

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(PureFeatureFlag::class))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Multi-enum cache isolation — each enum gets its own entry
    // ──────────────────────────────────────────────────────────────

    describe('Multi-enum cache isolation', function (): void {
        it('different enums do not share metadata', function (): void {
            $userMeta = EnumMetadataResolver::resolve(UserStatus::class);
            $priorityMeta = EnumMetadataResolver::resolve(Priority::class);

            // UserStatus has 'active' label, Priority does not
            expect($userMeta['labels'])->toHaveKey('active');
            expect($priorityMeta['labels'])->not->toHaveKey('active');

            // Priority has '1' (int) value key, UserStatus does not
            expect($priorityMeta['labels'])->toHaveKey(1);
            expect($userMeta['labels'])->not->toHaveKey(1);
        });

        it('string-backed and int-backed enums have distinct value keys', function (): void {
            $stringMeta = EnumMetadataResolver::resolve(UserStatus::class);
            $intMeta = EnumMetadataResolver::resolve(Priority::class);

            // String-backed uses string keys
            expect(isset($stringMeta['colors']['active']))->toBeTrue();

            // Int-backed uses int keys (for IntStatusWithColor)
            EnumMetadataResolver::resolve(IntStatusWithColor::class);
            $intStatusMeta = EnumMetadataResolver::resolve(IntStatusWithColor::class);

            expect(isset($intStatusMeta['colors'][1]))->toBeTrue();
            expect(isset($intStatusMeta['colors'][3]))->toBeTrue();
        });

        it('pure enum uses case names as keys', function (): void {
            $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

            // Pure enums use case names (not backed values)
            expect($meta['labels'])->toHaveKey('DARK_MODE');
            expect($meta['labels'])->toHaveKey('BETA_FEATURES');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // TTL-based cache expiry
    // ──────────────────────────────────────────────────────────────

    describe('TTL-based cache expiry', function (): void {
        it('TTL of 0 disables caching — has() always returns false', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Test'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('negative TTL is normalized to 0', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-100);

            expect($cache->getTtl())->toBe(0);
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('cache entry expires after TTL', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(1); // 1 second

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Cached'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // Should be valid immediately
            expect($cache->has(UserStatus::class))->toBeTrue();

            // Wait for expiry (2 seconds to be safe)
            sleep(2);

            // Should be expired now
            expect($cache->has(UserStatus::class))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // clearClass() only clears one enum's cache
    // ──────────────────────────────────────────────────────────────

    describe('clearClass() selective clearing', function (): void {
        it('clears only the specified class', function (): void {
            $cache = EnumCache::getInstance();

            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(Priority::class);

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(Priority::class))->toBeTrue();

            // Clear only UserStatus
            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeTrue();
        });

        it('clearClass() on non-existent class is a no-op', function (): void {
            $cache = EnumCache::getInstance();

            EnumMetadataResolver::resolve(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeTrue();

            // Clear a class that was never cached
            $cache->clearClass('NonExistentClass');

            expect($cache->has(UserStatus::class))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Resolver handles all fixture enums correctly
    // ──────────────────────────────────────────────────────────────

    describe('Resolver correctness across fixtures', function (): void {
        it('TicketStatus resolves class-level labels', function (): void {
            $meta = EnumMetadataResolver::resolve(TicketStatus::class);

            expect($meta['labels']['open'])->toBe('Open');
            expect($meta['labels']['in_progress'])->toBe('In Progress');
            expect($meta['labels']['closed'])->toBe('Closed');
        });

        it('TicketStatus resolves class-level descriptions', function (): void {
            $meta = EnumMetadataResolver::resolve(TicketStatus::class);

            expect($meta['descriptions']['open'])->toBe('Ticket is open and awaiting response');
            expect($meta['descriptions']['closed'])->toBe('Ticket has been resolved');
        });

        it('TicketStatus resolves default icon for all cases', function (): void {
            $meta = EnumMetadataResolver::resolve(TicketStatus::class);

            expect($meta['icons']['open'])->toBe('heroicon-o-ticket');
            expect($meta['icons']['in_progress'])->toBe('heroicon-o-ticket');
            expect($meta['icons']['closed'])->toBe('heroicon-o-ticket');
        });

        it('AllClassLevelEnum resolves all metadata types from class-level', function (): void {
            $meta = EnumMetadataResolver::resolve(AllClassLevelEnum::class);

            // Should have class-level labels, colors, descriptions, and icons
            expect($meta['labels'])->toBeArray();
            expect($meta['colors'])->toBeArray();
            expect($meta['descriptions'])->toBeArray();
            expect($meta['icons'])->toBeArray();
        });

        it('CamelCaseEnum generates correct labels', function (): void {
            $meta = EnumMetadataResolver::resolve(CamelCaseRole::class);

            // CamelCase cases should still produce Title Case labels
            foreach ($meta['labels'] as $key => $label) {
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });

        it('SingleCaseEnum works with one case', function (): void {
            $meta = EnumMetadataResolver::resolve(SingleCaseEnum::class);

            expect($meta['labels'])->toHaveKey('only');
            expect(SingleCaseEnum::ONLY->label())->toBe('Only');
            expect(SingleCaseEnum::values())->toBe(['only']);
            expect(SingleCaseEnum::forSelect())->toHaveCount(1);
        });

        it('ZeroPriority (int-backed with 0 value) resolves correctly', function (): void {
            $meta = EnumMetadataResolver::resolve(ZeroPriority::class);

            // Zero is a valid int key
            expect($meta['labels'])->toHaveKey(0);
            expect(ZeroPriority::NONE->value)->toBe(0);
            expect(ZeroPriority::values())->toContain(0);
        });

        it('OrderStatus resolves per-case overrides', function (): void {
            $meta = EnumMetadataResolver::resolve(OrderStatus::class);

            // OrderStatus has per-case Label/Color/Description attributes
            expect($meta['labels'])->toBeArray();
            expect(count($meta['labels']))->toBeGreaterThan(0);
        });

        it('PaymentStatus resolves correctly', function (): void {
            $meta = EnumMetadataResolver::resolve(PaymentStatus::class);

            expect($meta['labels'])->toBeArray();
            expect($meta['colors'])->toBeArray();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // HasEnumMetadata edge cases via trait methods
    // ──────────────────────────────────────────────────────────────

    describe('HasEnumMetadata trait edge cases', function (): void {
        it('tryFromLabel is case-insensitive', function (): void {
            $case = UserStatus::tryFromLabel('active user');
            expect($case)->toBe(UserStatus::ACTIVE);

            $case = UserStatus::tryFromLabel('ACTIVE USER');
            expect($case)->toBe(UserStatus::ACTIVE);

            $case = UserStatus::tryFromLabel('Active User');
            expect($case)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel returns null for non-existent labels', function (): void {
            expect(UserStatus::tryFromLabel('nonexistent-label'))->toBeNull();
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('fromName() is case-sensitive', function (): void {
            expect(UserStatus::fromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(fn () => UserStatus::fromName('active'))->toThrow(InvalidEnumException::class);
        });

        it('in() returns false for empty array', function (): void {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('is() rejects case-insensitive string comparison', function (): void {
            // String comparison is case-sensitive
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
        });

        it('forApi() returns consistent ordering with cases()', function (): void {
            $api = UserStatus::forApi();
            $cases = UserStatus::cases();

            expect(count($api))->toBe(count($cases));

            for ($i = 0; $i < count($cases); $i++) {
                expect($api[$i]['name'])->toBe($cases[$i]->name);
            }
        });

        it('values() returns same count as cases()', function (): void {
            expect(count(UserStatus::values()))->toBe(count(UserStatus::cases()));
            expect(count(Priority::values()))->toBe(count(Priority::cases()));
            expect(count(PureFeatureFlag::values()))->toBe(count(PureFeatureFlag::cases()));
        });

        it('labels() returns same count as cases()', function (): void {
            expect(count(UserStatus::labels()))->toBe(count(UserStatus::cases()));
            expect(count(Priority::labels()))->toBe(count(Priority::cases()));
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumCache singleton behavior
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache singleton behavior', function (): void {
        it('getInstance() always returns same instance', function (): void {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('resetInstance() allows new singleton creation', function (): void {
            $a = EnumCache::getInstance();
            EnumCache::resetInstance();
            $b = EnumCache::getInstance();

            // Should be a new instance
            expect($a)->not->toBe($b);
        });

        it('flush() clears everything via static method', function (): void {
            $cache = EnumCache::getInstance();
            $cache->set(UserStatus::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            $cache->set(Priority::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeFalse();
        });

        it('get() throws OutOfBoundsException for missing class', function (): void {
            expect(fn () => EnumCache::getInstance()->get('Nonexistent'))
                ->toThrow(\OutOfBoundsException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Mixed attribute resolution priority (per-case > class-level)
    // ──────────────────────────────────────────────────────────────

    describe('Attribute resolution priority', function (): void {
        it('per-case Label overrides class-level EnumLabel', function (): void {
            // UserStatus::ACTIVE has per-case #[Label('Active User')]
            expect(UserStatus::ACTIVE->label())->toBe('Active User');

            // UserStatus::INACTIVE has no per-case label — auto-generated
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('per-case Color overrides class-level EnumColor', function (): void {
            // UserStatus::BANNED has per-case #[Color('danger')]
            expect(UserStatus::BANNED->color())->toBe('danger');

            // UserStatus::ACTIVE inherits from class-level EnumColor
            expect(UserStatus::ACTIVE->color())->toBe('success');
        });

        it('MixedAttributeStatus class-level attributes resolve correctly', function (): void {
            // Class-level EnumLabel with per-case override
            expect(MixedAttributeStatus::NEW->label())->toBe('Brand New Item');
        });

        it('IntBackedPriority uses int keys for color lookup', function (): void {
            EnumMetadataResolver::invalidate(IntBackedPriority::class);
            $meta = EnumMetadataResolver::resolve(IntBackedPriority::class);

            expect($meta['labels'])->toHaveKey(1);
            expect($meta['labels'])->toHaveKey(2);
            expect($meta['labels'])->toHaveKey(3);
        });
    });
});
