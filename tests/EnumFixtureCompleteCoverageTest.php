<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderWorkflowStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

// ── EnumMetadataResolver: Cache Invalidation ────────────────────────────

describe('EnumMetadataResolver cache invalidation', function (): void {
    beforeEach(function (): void {
        EnumMetadataResolver::invalidateAll();
        EnumCache::flush();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('invalidates a specific class cache entry', function (): void {
        // First resolve — populates cache
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta1)->toBeArray();
        expect($meta1)->toHaveKey('labels');

        // Invalidate specific class
        EnumMetadataResolver::invalidate(UserStatus::class);

        // Cache should be empty for this class
        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('invalidates all class cache entries', function (): void {
        // Resolve multiple enums
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(OrderStatus::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeTrue();

        // Invalidate all
        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(OrderStatus::class))->toBeFalse();
    });

    it('rebuilds metadata after invalidation', function (): void {
        $before = EnumMetadataResolver::resolve(PaymentStatus::class);
        EnumMetadataResolver::invalidate(PaymentStatus::class);
        $after = EnumMetadataResolver::resolve(PaymentStatus::class);

        expect($before)->toEqual($after);
    });
});

// ── EnumCache: TTL Behavior ────────────────────────────────────────────

describe('EnumCache TTL behavior', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('respects TTL expiration', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();

        // Wait for TTL to expire
        sleep(2);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('disables caching when TTL is 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('normalizes negative TTL to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('clears a specific class entry', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->set(OrderStatus::class, [
            'labels' => ['pending' => 'Pending'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeTrue();
    });

    it('throws OutOfBoundsException when getting non-existent entry', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn (): mixed => $cache->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('prevents cloning via __clone', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn () => clone $cache)->toThrow(\RuntimeException::class);
    });

    it('prevents unserialization via __wakeup', function (): void {
        $serialized = serialize(EnumCache::getInstance());

        expect(fn () => unserialize($serialized))
            ->toThrow(\RuntimeException::class);
    });
});

// ── Cross-Fixture: All Enum Types ──────────────────────────────────────

describe('All fixtures produce valid forSelect output', function (): void {
    $fixtures = [
        UserStatus::class,
        OrderStatus::class,
        PaymentStatus::class,
        TicketStatus::class,
        IntBackedPriority::class,
        PureFeatureFlag::class,
        SingleCaseEnum::class,
        ZeroPriority::class,
        ZeroBackedPriority::class,
        MixedAttributeStatus::class,
        AllClassLevelEnum::class,
        CamelCaseRole::class,
        DetailedTicketStatus::class,
        IntStatusWithColor::class,
        LabelMapEnum::class,
        OrderWorkflowStatus::class,
        OverriddenIconRole::class,
        RequestState::class,
        SystemStatus::class,
    ];

    it('every fixture generates non-empty forSelect with value+label keys', function () use ($fixtures): void {
        foreach ($fixtures as $fixture) {
            $select = $fixture::forSelect();

            expect($select)->not->toBeEmpty();
            foreach ($select as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        }
    });

    it('every fixture generates non-empty forApi with all required keys', function () use ($fixtures): void {
        $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

        foreach ($fixtures as $fixture) {
            $api = $fixture::forApi();

            expect($api)->not->toBeEmpty();
            foreach ($api as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKeys($requiredKeys);
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        }
    });

    it('forSelect values match values() output', function () use ($fixtures): void {
        foreach ($fixtures as $fixture) {
            $selectValues = array_column($fixture::forSelect(), 'value');
            $rawValues = $fixture::values();

            expect($selectValues)->toBe($rawValues);
        }
    });
});

// ── Int-Backed Enum Edge Cases ─────────────────────────────────────────

describe('Int-backed enum edge cases', function (): void {
    it('zero value is a valid backed value', function (): void {
        expect(ZeroPriority::NONE->value)->toBe(0);
        expect(ZeroPriority::NONE->label())->toBeString()->not->toBeEmpty();
        expect(ZeroPriority::values())->toContain(0);
    });

    it('int-backed forSelect uses int values', function (): void {
        $select = IntBackedPriority::forSelect();

        foreach ($select as $option) {
            expect($option['value'])->toBeInt();
        }
    });

    it('int-backed forApi uses int values', function (): void {
        $api = IntBackedPriority::forApi();

        foreach ($api as $item) {
            expect($item['value'])->toBeInt();
        }
    });

    it('class-level EnumColor maps correctly for int values', function (): void {
        expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
        expect(IntBackedPriority::HIGH->color())->toBe('warning');
        expect(IntBackedPriority::LOW->color())->toBe('success');
        expect(IntBackedPriority::NONE->color())->toBe('success');
    });

    it('class-level EnumLabel maps correctly for int values', function (): void {
        expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
        expect(IntBackedPriority::HIGH->label())->toBe('High Priority');
        expect(IntBackedPriority::LOW->label())->toBe('Low Priority');
    });

    it('class-level EnumDescription maps correctly for int values', function (): void {
        expect(IntBackedPriority::CRITICAL->description())->toBe('Critical priority — immediate action required');
        expect(IntBackedPriority::LOW->description())->toBe('Low priority — handle when convenient');
    });

    it('per-case override wins over class-level for int-backed', function (): void {
        // HIGH has per-case Color('warning'), class-level also has warning for 2
        expect(IntBackedPriority::HIGH->color())->toBe('warning');
        // CRITICAL has per-case Color('danger'), class-level also has danger for 1
        expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
    });
});

// ── Pure Enum Specifics ────────────────────────────────────────────────

describe('Pure enum specifics', function (): void {
    it('uses case name as value in forSelect', function (): void {
        $select = PureFeatureFlag::forSelect();

        expect($select[0]['value'])->toBe('DARK_MODE');
        expect($select[1]['value'])->toBe('BETA_FEATURES');
        expect($select[2]['value'])->toBe('MAINTENANCE_MODE');
    });

    it('uses case name as value in forApi', function (): void {
        $api = PureFeatureFlag::forApi();

        expect($api[0]['value'])->toBe('DARK_MODE');
        expect($api[0]['name'])->toBe('DARK_MODE');
    });

    it('values() returns case names', function (): void {
        expect(PureFeatureFlag::values())->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
    });

    it('tryFromName works with case names', function (): void {
        expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBe(PureFeatureFlag::DARK_MODE);
        expect(PureFeatureFlag::tryFromName('NONEXISTENT'))->toBeNull();
    });

    it('auto-generated labels for cases without attributes', function (): void {
        expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
        expect(PureFeatureFlag::MAINTENANCE_MODE->color())->toBe('secondary');
        expect(PureFeatureFlag::MAINTENANCE_MODE->icon())->toBeNull();
        expect(PureFeatureFlag::MAINTENANCE_MODE->description())->toBeNull();
    });
});

// ── Single Case Enum ────────────────────────────────────────────────────

describe('Single case enum edge case', function (): void {
    it('works with single case enums', function (): void {
        expect(SingleCaseEnum::cases())->toHaveCount(1);
        expect(SingleCaseEnum::ONLY->label())->toBe('Only');
        expect(SingleCaseEnum::ONLY->value)->toBe('only');
    });

    it('generates valid forSelect with one entry', function (): void {
        $select = SingleCaseEnum::forSelect();

        expect($select)->toHaveCount(1);
        expect($select[0])->toBe(['value' => 'only', 'label' => 'Only']);
    });

    it('in() with single element list works', function (): void {
        expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
        expect(SingleCaseEnum::ONLY->in(['ONLY']))->toBeTrue();
    });
});

// ── Mixed Attribute Resolution Priority ─────────────────────────────────

describe('Mixed attribute resolution priority', function (): void {
    it('class-level EnumLabel provides label for unattributed cases', function (): void {
        expect(MixedAttributeStatus::NEW->label())->toBe('Brand New Item');
        expect(MixedAttributeStatus::USED->label())->toBe('Previously Owned');
    });

    it('auto-generates label when no class-level or per-case attribute', function (): void {
        expect(MixedAttributeStatus::DELETED->label())->toBe('Deleted');
        expect(MixedAttributeStatus::ACTIVE->label())->toBe('Active');
    });

    it('class-level EnumColor maps correctly', function (): void {
        expect(MixedAttributeStatus::ACTIVE->color())->toBe('success');
        expect(MixedAttributeStatus::PENDING->color())->toBe('warning');
        expect(MixedAttributeStatus::ARCHIVED->color())->toBe('danger');
    });

    it('class-level EnumDescription provides description', function (): void {
        expect(MixedAttributeStatus::ACTIVE->description())->toBe('Currently active');
        expect(MixedAttributeStatus::PENDING->description())->toBe('Awaiting review');
    });

    it('class-level EnumIcon default applies to cases without per-case icon', function (): void {
        expect(MixedAttributeStatus::ACTIVE->icon())->toBe('heroicon-o-document');
        expect(MixedAttributeStatus::DELETED->icon())->toBe('heroicon-o-document');
    });
});

// ── CamelCase Label Generation ───────────────────────────────────────────

describe('CamelCase label generation', function (): void {
    it('converts camelCase case names to Title Case', function (): void {
        // CamelCaseRole should have camelCase case names
        foreach (CamelCaseRole::cases() as $case) {
            $label = $case->label();

            expect($label)->toBeString()->not->toBeEmpty();
            // Label should not contain underscores (it's Title Case, not SCREAMING_SNAKE)
        }
    });
});

// ── fromName / hasCase Consistency ─────────────────────────────────────

describe('fromName and hasCase consistency across fixtures', function (): void {
    it('hasCase returns true for all case names', function (): void {
        foreach (UserStatus::cases() as $case) {
            expect(UserStatus::hasCase($case->name))->toBeTrue();
        }
    });

    it('hasCase returns false for invalid names', function (): void {
        expect(UserStatus::hasCase(''))->toBeFalse();
        expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
    });

    it('fromName returns correct case for every case name', function (): void {
        foreach (OrderStatus::cases() as $case) {
            $resolved = OrderStatus::fromName($case->name);
            expect($resolved)->toBe($case);
        }
    });

    it('fromName throws for invalid name with correct class in message', function (): void {
        expect(fn () => OrderStatus::fromName('INVALID_CASE'))
            ->toThrow(InvalidEnumException::class);
    });
});

// ── InvalidEnumException Factory Methods ───────────────────────────────

describe('InvalidEnumException factory methods', function (): void {
    it('value() factory creates correct message', function (): void {
        $ex = InvalidEnumException::value(UserStatus::class, 'invalid');

        expect($ex->getMessage())->toContain('invalid');
        expect($ex->getMessage())->toContain(UserStatus::class);
    });

    it('value() factory handles null value', function (): void {
        $ex = InvalidEnumException::value(UserStatus::class, null);

        expect($ex->getMessage())->toContain('null');
    });

    it('value() factory handles int value', function (): void {
        $ex = InvalidEnumException::value(IntBackedPriority::class, 99);

        expect($ex->getMessage())->toContain('99');
    });

    it('forName() factory creates correct message', function (): void {
        $ex = InvalidEnumException::forName(UserStatus::class, 'GHOST');

        expect($ex->getMessage())->toContain('GHOST');
        expect($ex->getMessage())->toContain('does not exist');
        expect($ex->getMessage())->toContain(UserStatus::class);
    });

    it('__toString returns class name and message', function (): void {
        $ex = InvalidEnumException::forName(UserStatus::class, 'GHOST');

        $str = (string) $ex;
        expect($str)->toContain(InvalidEnumException::class);
        expect($str)->toContain('GHOST');
    });
});
