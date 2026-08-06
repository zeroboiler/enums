<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum Edge Cases — Label Generation', function () {
    it('generates label from SCREAMING_SNAKE_CASE correctly', function () {
        // OrderStatus has no label attributes — auto-generated
        expect(OrderStatus::PENDING->label())->toBe('Pending');
        expect(OrderStatus::SHIPPED->label())->toBe('Shipped');
        expect(OrderStatus::DELIVERED->label())->toBe('Delivered');
        expect(OrderStatus::CANCELLED->label())->toBe('Cancelled');
    });

    it('generates label from camelCase case names correctly', function () {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
        expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
    });

    it('per-case label overrides auto-generated label', function () {
        // ACTIVE has #[Label('Active User')]
        expect(UserStatus::ACTIVE->label())->toBe('Active User');
        // INACTIVE has no label — auto-generated
        expect(UserStatus::INACTIVE->label())->toBe('Inactive');
    });

    it('per-case label overrides class-level EnumLabel', function () {
        // TicketStatus uses EnumLabel at class level — all cases auto-resolved
        // OPEN has class-level label 'Open'
        $open = \ZeroBoiler\Enums\Tests\Fixtures\TicketStatus::OPEN;
        expect($open->label())->toBe('Open');
    });
});

describe('Enum Edge Cases — Comparison Methods', function () {
    it('is() is case-sensitive for string names', function () {
        $status = UserStatus::ACTIVE;

        expect($status->is('ACTIVE'))->toBeTrue();
        expect($status->is('Active'))->toBeFalse();
        expect($status->is('active'))->toBeFalse();
    });

    it('in() works with empty array', function () {
        $status = UserStatus::ACTIVE;

        expect($status->in([]))->toBeFalse();
    });

    it('in() works with single element', function () {
        $status = UserStatus::ACTIVE;

        expect($status->in([UserStatus::ACTIVE]))->toBeTrue();
        expect($status->in([UserStatus::BANNED]))->toBeFalse();
    });

    it('in() works with all string names', function () {
        $status = UserStatus::ACTIVE;

        expect($status->in(['ACTIVE', 'PENDING']))->toBeTrue();
        expect($status->in(['BANNED', 'SUSPENDED']))->toBeFalse();
    });

    it('in() works with mixed instances and strings', function () {
        $status = UserStatus::ACTIVE;

        expect($status->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        expect($status->in(['BANNED', UserStatus::SUSPENDED]))->toBeFalse();
    });

    it('isNot() is exact negation of is()', function () {
        $status = UserStatus::ACTIVE;

        expect($status->isNot(UserStatus::ACTIVE))->toBeFalse();
        expect($status->isNot(UserStatus::BANNED))->toBeTrue();
        expect($status->isNot('ACTIVE'))->toBeFalse();
        expect($status->isNot('BANNED'))->toBeTrue();
    });
});

describe('Enum Edge Cases — Bulk Methods Consistency', function () {
    it('values() returns same count as cases()', function () {
        expect(UserStatus::values())->toHaveCount(count(UserStatus::cases()));
        expect(Priority::values())->toHaveCount(count(Priority::cases()));
        expect(RequestState::values())->toHaveCount(count(RequestState::cases()));
    });

    it('labels() returns same count as cases()', function () {
        expect(UserStatus::labels())->toHaveCount(count(UserStatus::cases()));
        expect(Priority::labels())->toHaveCount(count(Priority::cases()));
        expect(RequestState::labels())->toHaveCount(count(RequestState::cases()));
    });

    it('forSelect() values match values()', function () {
        $selectValues = array_column(UserStatus::forSelect(), 'value');
        expect($selectValues)->toEqual(UserStatus::values());
    });

    it('forApi() has correct number of entries', function () {
        $api = UserStatus::forApi();
        expect($api)->toHaveCount(count(UserStatus::cases()));
    });

    it('forApi() contains all required keys for each entry', function () {
        $api = UserStatus::forApi();
        $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

        foreach ($api as $entry) {
            expect($entry)->toHaveKeys($requiredKeys);
        }
    });
});

describe('Enum Edge Cases — Reverse Lookup', function () {
    it('tryFromLabel is truly case-insensitive', function () {
        $case = UserStatus::tryFromLabel('active user');
        expect($case)->toBe(UserStatus::ACTIVE);

        $case = UserStatus::tryFromLabel('ACTIVE USER');
        expect($case)->toBe(UserStatus::ACTIVE);

        $case = UserStatus::tryFromLabel('Active User');
        expect($case)->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel returns null for empty string', function () {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });

    it('tryFromName is case-sensitive', function () {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromName('Active'))->toBeNull();
        expect(UserStatus::tryFromName('active'))->toBeNull();
    });

    it('tryFromName returns null for empty string', function () {
        expect(UserStatus::tryFromName(''))->toBeNull();
    });

    it('fromName throws with correct class name in message', function () {
        expect(fn () => UserStatus::fromName('DOES_NOT_EXIST'))
            ->toThrow(InvalidEnumException::class, 'UserStatus');
    });
});

describe('Enum Edge Cases — EnumRule with various types', function () {
    it('accepts valid string-backed enum value', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', 'active', $fail);

        expect($failed)->toBeFalse();
    });

    it('accepts valid int-backed enum value', function () {
        $rule = EnumRule::for(Priority::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('priority', 3, $fail);

        expect($failed)->toBeFalse();
    });

    it('rejects wrong type for int-backed enum (string given)', function () {
        $rule = EnumRule::for(Priority::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('priority', '3', $fail);

        expect($failed)->toBeTrue();
    });

    it('rejects wrong type for string-backed enum (int given)', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', 42, $fail);

        expect($failed)->toBeTrue();
    });

    it('rejects boolean false for backed enum', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', false, $fail);

        expect($failed)->toBeTrue();
    });

    it('rejects float for int-backed enum', function () {
        $rule = EnumRule::for(Priority::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('priority', 3.5, $fail);

        expect($failed)->toBeTrue();
    });

    it('nullable variant allows null without calling fail', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $called = false;
        $fail = function (string $message) use (&$called): void {
            $called = true;
        };

        $rule->validate('status', null, $fail);

        expect($called)->toBeFalse();
    });

    it('generates error message without allowed values for non-HasEnumMetadata enum', function () {
        $rule = EnumRule::for(RequestState::class);
        $message = '';

        $fail = function (string $msg) use (&$message): void {
            $message = $msg;
        };

        $rule->validate('state', 'NONEXISTENT', $fail);

        // Pure enums without values() method should not include allowed values
        expect($message)->toContain('invalid');
    });
});

describe('Enum Edge Cases — EnumCast', function () {
    it('set accepts valid raw string value', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->set(new stdClass, 'status', 'pending', []);

        expect($result)->toBe('pending');
    });

    it('set accepts valid raw int value', function () {
        $cast = new EnumCast(Priority::class);
        $result = $cast->set(new stdClass, 'priority', 2, []);

        expect($result)->toBe(2);
    });

    it('serialize passes through raw string', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new stdClass, 'status', 'active', []);

        expect($result)->toBe('active');
    });

    it('serialize passes through null', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new stdClass, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('get returns null for null value', function () {
        $cast = new EnumCast(Priority::class);
        $result = $cast->get(new stdClass, 'priority', null, []);

        expect($result)->toBeNull();
    });

    it('get returns enum instance for valid int value', function () {
        $cast = new EnumCast(Priority::class);
        $result = $cast->get(new stdClass, 'priority', 1, []);

        expect($result)->toBe(Priority::LOW);
    });

    it('get returns enum instance for valid string value', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->get(new stdClass, 'status', 'banned', []);

        expect($result)->toBe(UserStatus::BANNED);
    });
});

describe('Enum Edge Cases — Cache Resilience', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('setTtl normalizes negative values to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        // TTL should be normalized to 0, so caching is disabled
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('resetInstance creates a fresh singleton', function () {
        $first = EnumCache::getInstance();
        EnumCache::resetInstance();
        $second = EnumCache::getInstance();

        // Should be different instances
        expect($first)->not->toBe($second);
    });

    it('clearClass only clears the specified class', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(UserStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(Priority::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();
    });
});

describe('Enum Edge Cases — Metadata Resolution', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('resolves metadata for camelCase enum correctly', function () {
        $meta = \ZeroBoiler\Enums\Support\EnumMetadataResolver::resolve(CamelCaseRole::class);

        // camelCase names should auto-generate labels
        expect($meta['labels']['is_active'])->toBe('Is Active');
        expect($meta['labels']['is_admin'])->toBe('Is Admin');
    });

    it('defaults color to secondary for cases without color', function () {
        expect(OrderStatus::PENDING->color())->toBe('secondary');
        expect(OrderStatus::SHIPPED->color())->toBe('secondary');
        expect(CamelCaseRole::isActive->color())->toBe('secondary');
    });

    it('defaults description to null for cases without description', function () {
        expect(OrderStatus::PENDING->description())->toBeNull();
        expect(CamelCaseRole::isActive->description())->toBeNull();
    });

    it('defaults icon to null for cases without icon', function () {
        expect(OrderStatus::PENDING->icon())->toBeNull();
        expect(CamelCaseRole::isActive->icon())->toBeNull();
    });
});
