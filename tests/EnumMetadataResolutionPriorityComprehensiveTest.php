<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\EnumCache;

// ── Test Fixtures ──────────────────────────────────────────────────────────

#[EnumColor(success: ['active'], danger: ['banned'], warning: ['pending'])]
#[EnumLabel(labels: ['active' => 'Active Account', 'banned' => 'Banned Account'])]
#[EnumIcon(default: 'heroicon-o-circle')]
#[EnumDescription(descriptions: ['active' => 'Fully active account', 'banned' => 'Permanently banned account'])]
enum RoundtripUserStatus: string
{
    use HasEnumMetadata;

    #[Label('Active Account Override')]
    #[Icon('heroicon-o-check')]
    #[Description('Account is fully active')]
    case ACTIVE = 'active';

    case PENDING = 'pending';

    #[Color('danger')]
    #[Description('Account has been banned')]
    case BANNED = 'banned';
}

enum RoundtripPriority: int
{
    use HasEnumMetadata;

    #[Color('danger')]
    case CRITICAL = 1;

    #[Color('warning')]
    case HIGH = 2;

    #[Color('success')]
    case LOW = 3;

    case NONE = 4;
}

enum RoundtripFeatureFlag
{
    use HasEnumMetadata;

    #[Icon('heroicon-o-shield-check')]
    #[Description('Two-factor authentication')]
    case TWO_FACTOR_AUTH;

    #[Description('Dark mode theme')]
    case DARK_MODE;
}

// ── Tests ─────────────────────────────────────────────────────────────────

describe('Enum metadata resolution priority', function (): void {
    it('per-case Label overrides class-level EnumLabel', function (): void {
        // Class-level has 'active' => 'Active Account', but per-case has 'Active Account Override'
        expect(RoundtripUserStatus::ACTIVE->label())->toBe('Active Account Override');
    });

    it('class-level EnumLabel applies when no per-case override', function (): void {
        // Class-level has 'banned' => 'Banned Account'
        expect(RoundtripUserStatus::BANNED->label())->toBe('Banned Account');
    });

    it('auto-generated label for case without class-level or per-case label', function (): void {
        // 'pending' has no per-case Label and no class-level EnumLabel entry
        expect(RoundtripUserStatus::PENDING->label())->toBe('Pending');
    });

    it('per-case Color overrides class-level EnumColor', function (): void {
        expect(RoundtripUserStatus::ACTIVE->color())->toBe('success');
        expect(RoundtripUserStatus::BANNED->color())->toBe('danger'); // per-case override
        expect(RoundtripUserStatus::PENDING->color())->toBe('warning'); // class-level
    });

    it('color defaults to secondary when not defined', function (): void {
        // RoundtripPriority has no class-level EnumColor for NONE
        expect(RoundtripPriority::NONE->color())->toBe('secondary');
    });

    it('per-case Icon overrides class-level EnumIcon default', function (): void {
        expect(RoundtripUserStatus::ACTIVE->icon())->toBe('heroicon-o-check');
        expect(RoundtripUserStatus::PENDING->icon())->toBe('heroicon-o-circle'); // class-level default
        expect(RoundtripUserStatus::BANNED->icon())->toBe('heroicon-o-circle'); // class-level default
    });

    it('per-case Description overrides class-level EnumDescription', function (): void {
        expect(RoundtripUserStatus::ACTIVE->description())->toBe('Account is fully active');
        expect(RoundtripUserStatus::BANNED->description())->toBe('Account has been banned');
        expect(RoundtripUserStatus::PENDING->description())->toBeNull(); // no class-level entry for pending
    });
});

describe('Enum forSelect() and forApi()', function (): void {
    it('forSelect returns value-label pairs for string-backed enum', function (): void {
        $select = RoundtripUserStatus::forSelect();

        expect($select)->toBeArray();
        expect($select)->toHaveCount(3);
        expect($select[0])->toHaveKeys(['value', 'label']);
        expect($select[0]['value'])->toBe('active');
    });

    it('forSelect returns int values for int-backed enum', function (): void {
        $select = RoundtripPriority::forSelect();

        expect($select[0]['value'])->toBe(1);
        expect($select[1]['value'])->toBe(2);
    });

    it('forSelect returns case names for pure enum', function (): void {
        $select = RoundtripFeatureFlag::forSelect();

        expect($select[0]['value'])->toBe('TWO_FACTOR_AUTH');
        expect($select[0]['label'])->toBe('Two Factor Auth');
    });

    it('forApi returns full metadata', function (): void {
        $api = RoundtripUserStatus::forApi();

        expect($api)->toBeArray();
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });
});

describe('Enum values() and labels()', function (): void {
    it('values returns backed values for string-backed enum', function (): void {
        expect(RoundtripUserStatus::values())->toBe(['active', 'pending', 'banned']);
    });

    it('values returns int values for int-backed enum', function (): void {
        expect(RoundtripPriority::values())->toBe([1, 2, 3, 4]);
    });

    it('values returns case names for pure enum', function (): void {
        expect(RoundtripFeatureFlag::values())->toBe(['TWO_FACTOR_AUTH', 'DARK_MODE']);
    });

    it('labels returns resolved labels in order', function (): void {
        $labels = RoundtripUserStatus::labels();

        expect($labels)->toHaveCount(3);
        expect($labels[0])->toBe('Active Account Override');
    });
});

describe('Enum comparison methods', function (): void {
    it('is() works with instance', function (): void {
        expect(RoundtripUserStatus::ACTIVE->is(RoundtripUserStatus::ACTIVE))->toBeTrue();
        expect(RoundtripUserStatus::ACTIVE->is(RoundtripUserStatus::BANNED))->toBeFalse();
    });

    it('is() works with case name string', function (): void {
        expect(RoundtripUserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(RoundtripUserStatus::ACTIVE->is('active'))->toBeFalse(); // value ≠ name
    });

    it('is() works with int-backed enum', function (): void {
        expect(RoundtripPriority::CRITICAL->is(RoundtripPriority::CRITICAL))->toBeTrue();
        expect(RoundtripPriority::CRITICAL->is('CRITICAL'))->toBeTrue();
    });

    it('is() works with pure enum', function (): void {
        expect(RoundtripFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
    });

    it('isNot() is the negation of is()', function (): void {
        expect(RoundtripUserStatus::ACTIVE->isNot(RoundtripUserStatus::BANNED))->toBeTrue();
        expect(RoundtripUserStatus::ACTIVE->isNot('ACTIVE'))->toBeFalse();
    });

    it('in() accepts mixed instances and strings', function (): void {
        expect(RoundtripUserStatus::ACTIVE->in([RoundtripUserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        expect(RoundtripUserStatus::ACTIVE->in(['PENDING', 'BANNED']))->toBeFalse();
        expect(RoundtripUserStatus::BANNED->in([RoundtripUserStatus::ACTIVE, RoundtripUserStatus::BANNED]))->toBeTrue();
    });

    it('in() returns false for empty array', function (): void {
        expect(RoundtripUserStatus::ACTIVE->in([]))->toBeFalse();
    });
});

describe('Enum reverse lookup', function (): void {
    it('tryFromLabel finds by label (case-insensitive)', function (): void {
        expect(RoundtripUserStatus::tryFromLabel('Active Account Override'))->toBe(RoundtripUserStatus::ACTIVE);
        expect(RoundtripUserStatus::tryFromLabel('active account override'))->toBe(RoundtripUserStatus::ACTIVE);
        expect(RoundtripUserStatus::tryFromLabel('Banned Account'))->toBe(RoundtripUserStatus::BANNED);
    });

    it('tryFromLabel returns null for non-existent label', function (): void {
        expect(RoundtripUserStatus::tryFromLabel('NonExistent'))->toBeNull();
    });

    it('tryFromName finds by exact case name', function (): void {
        expect(RoundtripUserStatus::tryFromName('ACTIVE'))->toBe(RoundtripUserStatus::ACTIVE);
        expect(RoundtripUserStatus::tryFromName('active'))->toBeNull(); // case-sensitive
    });

    it('fromName throws on non-existent name', function (): void {
        expect(fn (): mixed => RoundtripUserStatus::fromName('NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase checks existence', function (): void {
        expect(RoundtripUserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(RoundtripUserStatus::hasCase('NONEXISTENT'))->toBeFalse();
    });
});

describe('EnumCache behavior', function (): void {
    it('cache can be flushed', function (): void {
        EnumCache::flush();

        // This should not throw
        RoundtripUserStatus::ACTIVE->label();

        expect(true)->toBeTrue();
    });

    it('cache TTL can be set', function (): void {
        $cache = EnumCache::getInstance();
        $originalTtl = 300; // default

        $cache->setTtl(60);
        $cache->setTtl(0); // disable

        // Restore
        $cache->setTtl($originalTtl);

        expect(true)->toBeTrue();
    });

    it('resetInstance creates a fresh singleton', function (): void {
        EnumCache::resetInstance();

        $cache = EnumCache::getInstance();
        expect($cache)->toBeInstanceOf(EnumCache::class);
    });
});

describe('Auto-generated label formats', function (): void {
    it('SCREAMING_SNAKE_CASE converts to Title Case', function (): void {
        expect(RoundtripUserStatus::PENDING->label())->toBe('Pending');
        expect(RoundtripPriority::CRITICAL->label())->toBe('Critical');
    });

    it('Pure enum case names also auto-generate labels', function (): void {
        expect(RoundtripFeatureFlag::TWO_FACTOR_AUTH->label())->toBe('Two Factor Auth');
        expect(RoundtripFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
    });
});
