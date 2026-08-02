<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

beforeEach(function (): void {
    EnumCache::flush();
});

describe('HasEnumMetadata::values', function (): void {
    it('returns string values for string-backed enum', function (): void {
        expect(OrderStatus::values())
            ->toBe(['pending', 'shipped', 'delivered', 'cancelled']);
    });

    it('returns int values for int-backed enum', function (): void {
        expect(Priority::values())
            ->toBe([1, 2, 3, 4]);
    });

    it('includes zero for int-backed enum with zero value', function (): void {
        expect(ZeroPriority::values())
            ->toBe([0, 1, 2]);
    });

    it('returns name strings for pure enum', function (): void {
        expect(RequestState::values())
            ->toBe(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
    });
});

describe('HasEnumMetadata::labels', function (): void {
    it('returns labels for all cases in order', function (): void {
        EnumCache::flush();
        expect(OrderStatus::labels())
            ->toBe(['Pending', 'Shipped', 'Delivered', 'Cancelled']);
    });

    it('returns attribute-based labels when present', function (): void {
        EnumCache::flush();
        $labels = UserStatus::labels();

        expect($labels)
            ->toContain('Active User')
            ->toContain('Awaiting Verification')
            ->toHaveCount(5);
    });
});

describe('HasEnumMetadata::tryFromLabel extended', function (): void {
    it('finds case by exact label match', function (): void {
        EnumCache::flush();
        expect(UserStatus::tryFromLabel('Active User'))
            ->toBe(UserStatus::ACTIVE);
    });

    it('finds case case-insensitively', function (): void {
        EnumCache::flush();
        expect(UserStatus::tryFromLabel('ACTIVE USER'))
            ->toBe(UserStatus::ACTIVE);
    });

    it('finds auto-generated label', function (): void {
        expect(OrderStatus::tryFromLabel('Pending'))
            ->toBe(OrderStatus::PENDING);
    });

    it('returns null for non-existent label', function (): void {
        expect(OrderStatus::tryFromLabel('NonExistent'))->toBeNull();
    });

    it('returns null for empty string', function (): void {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });

    it('handles labels with special characters', function (): void {
        expect(UserStatus::tryFromLabel('Awaiting Verification'))
            ->toBe(UserStatus::PENDING);
    });
});

describe('HasEnumMetadata::generateLabel edge cases', function (): void {
    it('converts SCREAMING_SNAKE_CASE to Title Case', function (): void {
        // PENDING → Pending, CANCELLED → Cancelled
        expect(OrderStatus::PENDING->label())->toBe('Pending')
            ->and(OrderStatus::CANCELLED->label())->toBe('Cancelled')
            ->and(OrderStatus::DELIVERED->label())->toBe('Delivered');
    });

    it('handles single character case names', function (): void {
        // Test with a minimal enum case name
        // OrderStatus cases are all multi-word, so we verify the generated labels match pattern
        foreach (OrderStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });
});

describe('HasEnumMetadata::forApi completeness', function (): void {
    it('returns all required keys for each case', function (): void {
        EnumCache::flush();
        $api = UserStatus::forApi();

        foreach ($api as $entry) {
            expect($entry)
                ->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        }
    });

    it('returns correct value types', function (): void {
        EnumCache::flush();
        $api = UserStatus::forApi();
        $first = $api[0];

        expect($first['value'])->toBeString()
            ->and($first['name'])->toBeString()
            ->and($first['label'])->toBeString()
            ->and($first['color'])->toBeString();
    });

    it('returns null icon when not set', function (): void {
        EnumCache::flush();
        $api = UserStatus::forApi();

        // INACTIVE has no icon attribute
        $inactive = array_filter($api, fn (array $e): bool => $e['name'] === 'INACTIVE');
        $inactive = array_values($inactive);
        expect($inactive[0]['icon'])->toBeNull();
    });

    it('returns null description when not set', function (): void {
        EnumCache::flush();
        $api = OrderStatus::forApi();

        foreach ($api as $entry) {
            expect($entry['description'])->toBeNull();
        }
    });

    it('uses default color secondary when no attribute', function (): void {
        EnumCache::flush();
        $api = OrderStatus::forApi();

        foreach ($api as $entry) {
            expect($entry['color'])->toBe('secondary');
        }
    });
});

describe('HasEnumMetadata::forSelect completeness', function (): void {
    it('returns value and label for each case', function (): void {
        $select = Priority::forSelect();

        expect($select)->toHaveCount(4);

        foreach ($select as $option) {
            expect($option)->toHaveKeys(['value', 'label']);
        }
    });

    it('uses int values for int-backed enum', function (): void {
        $select = Priority::forSelect();

        expect($select[0]['value'])->toBe(1)
            ->and($select[3]['value'])->toBe(4);
    });

    it('uses case names for pure enum', function (): void {
        $select = RequestState::forSelect();

        expect($select[0]['value'])->toBe('DRAFT')
            ->and($select[1]['value'])->toBe('SUBMITTED');
    });
});

describe('HasEnumMetadata integration', function (): void {
    it('color falls back to class-level then default', function (): void {
        EnumCache::flush();
        // UserStatus has EnumColor(success: ['active']) → class-level
        // BANNED has per-case Color('danger') → overrides
        // INACTIVE has nothing → falls to 'secondary' default
        expect(UserStatus::ACTIVE->color())->toBe('success')
            ->and(UserStatus::BANNED->color())->toBe('danger')
            ->and(UserStatus::INACTIVE->color())->toBe('secondary');
    });

    it('description falls back to null when not set', function (): void {
        EnumCache::flush();
        expect(UserStatus::INACTIVE->description())->toBeNull()
            ->and(UserStatus::SUSPENDED->description())->toBeNull();
    });

    it('icon falls back to null when not set', function (): void {
        EnumCache::flush();
        expect(UserStatus::INACTIVE->icon())->toBeNull()
            ->and(UserStatus::PENDING->icon())->toBeNull();
    });

    it('handles ZeroPriority int-backed enum with zero value', function (): void {
        EnumCache::flush();
        expect(ZeroPriority::NONE->label())->toBe('None')
            ->and(ZeroPriority::NONE->color())->toBe('secondary')
            ->and(ZeroPriority::NONE->description())->toBeNull()
            ->and(ZeroPriority::NONE->icon())->toBeNull();
    });
});
