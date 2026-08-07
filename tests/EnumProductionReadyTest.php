<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumCache singleton behaviour', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('returns same instance on multiple calls', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('flushes all entries via static flush()', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set(OrderStatus::class, [
            'labels' => ['pending' => 'Pending'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(OrderStatus::class))->toBeTrue();

        EnumCache::flush();

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('flushes a specific class entry via clearClass()', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set(OrderStatus::class, [
            'labels' => ['pending' => 'Pending'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(Priority::class, [
            'labels' => ['1' => 'Low'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });

    it('throws OutOfBoundsException when getting uncached class', function (): void {
        EnumCache::getInstance()->get(OrderStatus::class);
    })->throws(\OutOfBoundsException::class);

    it('respects TTL — entries expire after TTL seconds', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // disable caching — always stale

        $cache->set(OrderStatus::class, [
            'labels' => ['pending' => 'Cached Label'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(OrderStatus::class))->toBeFalse(); // TTL 0 → always stale
    });

    it('normalizes negative TTL to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5); // negative → normalized to 0

        $cache->set(OrderStatus::class, [
            'labels' => ['pending' => 'Cached'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });
});

describe('InvalidEnumException named constructors', function (): void {
    it('creates value exception with null', function (): void {
        $e = InvalidEnumException::value(OrderStatus::class, null);

        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain(OrderStatus::class);
    });

    it('creates value exception with int', function (): void {
        $e = InvalidEnumException::value(Priority::class, 99);

        expect($e->getMessage())->toContain('99');
        expect($e->getMessage())->toContain(Priority::class);
    });

    it('creates value exception with string', function (): void {
        $e = InvalidEnumException::value(OrderStatus::class, 'nonexistent');

        expect($e->getMessage())->toContain('nonexistent');
    });

    it('creates forName exception', function (): void {
        $e = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

        expect($e->getMessage())->toContain('NONEXISTENT');
        expect($e->getMessage())->toContain(UserStatus::class);
    });
});

describe('HasEnumMetadata — fromName / hasCase', function (): void {
    it('fromName resolves by exact case name', function (): void {
        expect(OrderStatus::fromName('PENDING'))->toBe(OrderStatus::PENDING);
        expect(Priority::fromName('HIGH'))->toBe(Priority::HIGH);
    });

    it('fromName throws on invalid name', function (): void {
        OrderStatus::fromName('NONEXISTENT');
    })->throws(InvalidEnumException::class);

    it('tryFromName returns null for invalid name', function (): void {
        expect(OrderStatus::tryFromName('NONEXISTENT'))->toBeNull();
        expect(OrderStatus::tryFromName(''))->toBeNull();
    });

    it('hasCase returns true for existing case', function (): void {
        expect(OrderStatus::hasCase('PENDING'))->toBeTrue();
        expect(OrderStatus::hasCase('SHIPPED'))->toBeTrue();
    });

    it('hasCase returns false for non-existing case', function (): void {
        expect(OrderStatus::hasCase('NONEXISTENT'))->toBeFalse();
    });
});

describe('HasEnumMetadata — comparison methods', function (): void {
    it('is() works with instance', function (): void {
        expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
        expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
    });

    it('is() works with string name', function (): void {
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
    });

    it('isNot() negates is()', function (): void {
        expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
        expect(UserStatus::ACTIVE->isNot('ACTIVE'))->toBeFalse();
    });

    it('in() matches against multiple cases', function (): void {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
        expect(UserStatus::BANNED->in(['ACTIVE', 'PENDING']))->toBeFalse();
        expect(UserStatus::PENDING->in(['PENDING']))->toBeTrue();
    });

    it('in() returns false for empty list', function (): void {
        expect(OrderStatus::PENDING->in([]))->toBeFalse();
    });
});

describe('Pure enum — RequestState', function (): void {
    it('has no backed value', function (): void {
        // Pure enums don't have ->value
        expect(RequestState::DRAFT->name)->toBe('DRAFT');
    });

    it('forSelect uses case names as values', function (): void {
        $options = RequestState::forSelect();

        expect($options[0]['value'])->toBe('DRAFT');
        expect($options[0]['label'])->toBe('Draft');
    });

    it('values() returns case names', function (): void {
        $values = RequestState::values();

        expect($values)->toBe(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
    });

    it('forApi returns case names as values', function (): void {
        $api = RequestState::forApi();

        expect($api[0]['value'])->toBe('DRAFT');
        expect($api[0]['name'])->toBe('DRAFT');
    });

    it('default color is secondary', function (): void {
        expect(RequestState::DRAFT->color())->toBe('secondary');
    });
});

describe('Int-backed enum with zero value — ZeroPriority', function (): void {
    it('handles zero value correctly', function (): void {
        expect(ZeroPriority::NONE->value)->toBe(0);
        expect(ZeroPriority::NONE->label())->toBe('None');
    });

    it('forSelect includes zero value', function (): void {
        $options = ZeroPriority::forSelect();

        expect($options[0]['value'])->toBe(0);
        expect($options[0]['label'])->toBe('None');
    });

    it('values() includes zero', function (): void {
        expect(ZeroPriority::values())->toBe([0, 1, 2]);
    });
});

describe('Single-case enum edge case', function (): void {
    it('works with only one case', function (): void {
        expect(SingleCaseEnum::ONLY->label())->toBe('Only');
        expect(SingleCaseEnum::ONLY->value)->toBe('only');
    });

    it('forSelect returns single entry', function (): void {
        $options = SingleCaseEnum::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0])->toBe(['value' => 'only', 'label' => 'Only']);
    });

    it('fromName works for the single case', function (): void {
        expect(SingleCaseEnum::fromName('ONLY'))->toBe(SingleCaseEnum::ONLY);
    });

    it('in() works with single-element list', function (): void {
        expect(SingleCaseEnum::ONLY->in(['ONLY']))->toBeTrue();
    });
});

describe('CamelCase enum label generation', function (): void {
    it('converts camelCase to Title Case', function (): void {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
        expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
    });

    it('uses backed value for metadata lookup', function (): void {
        // Labels should still be generated from case name, not value
        $label = CamelCaseRole::isActive->label();

        expect($label)->toBe('Is Active');
    });
});

describe('Class-level EnumLabel with per-case override', function (): void {
    it('uses class-level labels when no per-case override', function (): void {
        expect(TicketStatus::OPEN->label())->toBe('Open');
        expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
        expect(TicketStatus::CLOSED->label())->toBe('Closed');
    });

    it('uses class-level descriptions', function (): void {
        expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
        expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
    });

    it('returns null for description when class-level omits the case', function (): void {
        expect(TicketStatus::IN_PROGRESS->description())->toBeNull();
    });

    it('uses class-level icon as default for all cases', function (): void {
        expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
        expect(TicketStatus::IN_PROGRESS->icon())->toBe('heroicon-o-ticket');
        expect(TicketStatus::CLOSED->icon())->toBe('heroicon-o-ticket');
    });
});

describe('AllClassLevelEnum — all metadata from class attributes', function (): void {
    it('resolves labels from EnumLabel', function (): void {
        expect(AllClassLevelEnum::OPEN->label())->toBe('Open Status');
        expect(AllClassLevelEnum::IN_PROGRESS->label())->toBe('In Progress');
        expect(AllClassLevelEnum::DONE->label())->toBe('Done');
    });

    it('resolves descriptions from EnumDescription', function (): void {
        expect(AllClassLevelEnum::OPEN->description())->toBe('Task is open');
        expect(AllClassLevelEnum::IN_PROGRESS->description())->toBe('Task is being worked on');
        expect(AllClassLevelEnum::DONE->description())->toBe('Task is complete');
    });

    it('resolves icon from EnumIcon default', function (): void {
        expect(AllClassLevelEnum::OPEN->icon())->toBe('heroicon-o-circle');
        expect(AllClassLevelEnum::DONE->icon())->toBe('heroicon-o-circle');
    });

    it('forApi returns complete metadata for all cases', function (): void {
        $api = AllClassLevelEnum::forApi();

        expect($api)->toHaveCount(3);
        foreach ($api as $case) {
            expect($case)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($case['label'])->toBeString()->not->toBeEmpty();
            expect($case['description'])->toBeString()->not->toBeEmpty();
            expect($case['icon'])->toBeString()->not->toBeEmpty();
        }
    });
});

describe('Enum forSelect value uniqueness', function (): void {
    it('all backed values in forSelect are unique', function (): void {
        $options = UserStatus::forSelect();
        $values = array_column($options, 'value');

        expect($values)->each->toBeUnique();
    });

    it('count of forSelect matches count of cases', function (): void {
        expect(UserStatus::forSelect())->toHaveCount(count(UserStatus::cases()));
        expect(Priority::forSelect())->toHaveCount(count(Priority::cases()));
        expect(RequestState::forSelect())->toHaveCount(count(RequestState::cases()));
    });
});

describe('Enum values() and labels() consistency', function (): void {
    it('values() and labels() have same count as cases', function (): void {
        foreach ([OrderStatus::class, Priority::class, UserStatus::class, RequestState::class] as $enumClass) {
            expect($enumClass::values())->toHaveCount(count($enumClass::cases()));
            expect($enumClass::labels())->toHaveCount(count($enumClass::cases()));
        }
    });

    it('labels() returns non-empty strings', function (): void {
        $labels = UserStatus::labels();

        expect($labels)->each->toBeString()->not->toBeEmpty();
    });
});

describe('tryFromLabel case-insensitivity edge cases', function (): void {
    it('matches with mixed case', function (): void {
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
    });

    it('does not match partial labels', function (): void {
        expect(UserStatus::tryFromLabel('Active'))->toBeNull();
    });

    it('returns null for empty string', function (): void {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });
});
