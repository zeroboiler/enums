<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;

describe('Enum Multi-Facade Integration', function () {
    it('can call forSelect on multiple enum types sequentially via facade', function () {
        $userOptions = UserStatus::forSelect();
        $orderOptions = OrderStatus::forSelect();

        expect($userOptions)->toBeArray()->not->toBeEmpty();
        expect($orderOptions)->toBeArray()->not->toBeEmpty();
        expect($userOptions)->toHaveCount(5);
        expect($orderOptions)->toHaveCount(4);
    });

    it('can call forApi on multiple enum types and verify structure', function () {
        $userApi = UserStatus::forApi();
        $orderApi = OrderStatus::forApi();

        foreach ($userApi as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['value'])->toBeString();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString();
            expect($item['color'])->toBeString();
        }

        foreach ($orderApi as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBe('secondary'); // no class-level color
        }
    });

    it('produces consistent values() and forSelect() value extraction', function () {
        $values = UserStatus::values();
        $selectValues = array_column(UserStatus::forSelect(), 'value');

        expect($values)->toBe($selectValues);
    });

    it('produces consistent labels() and forSelect() label extraction', function () {
        $labels = UserStatus::labels();
        $selectLabels = array_column(UserStatus::forSelect(), 'label');

        expect($labels)->toBe($selectLabels);
    });
});

describe('Enum Cross-Type Integration', function () {
    it('string-backed enum returns string values', function () {
        foreach (UserStatus::values() as $value) {
            expect($value)->toBeString();
        }
    });

    it('int-backed enum returns int values', function () {
        foreach (IntBackedPriority::values() as $value) {
            expect($value)->toBeInt();
        }
    });

    it('pure enum returns case names as values', function () {
        $values = PureFeatureFlag::values();
        $names = array_map(fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());

        expect($values)->toBe($names);
    });

    it('each enum type has correct forSelect structure', function () {
        // String-backed
        $stringSelect = UserStatus::forSelect();
        expect($stringSelect[0]['value'])->toBe('active');

        // Int-backed
        $intSelect = IntBackedPriority::forSelect();
        expect($intSelect[0]['value'])->toBeInt();

        // Pure
        $pureSelect = PureFeatureFlag::forSelect();
        expect($pureSelect[0]['value'])->toBeString(); // case name
    });
});

describe('Enum Comparison Chain Integration', function () {
    it('supports chained comparison logic', function () {
        $status = UserStatus::ACTIVE;

        // Active is in positive group, not in negative group
        expect($status->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
        expect($status->notIn([UserStatus::BANNED, UserStatus::SUSPENDED]))->toBeTrue();

        // Banned is not active
        expect(UserStatus::BANNED->isNot(UserStatus::ACTIVE))->toBeTrue();
        expect(UserStatus::BANNED->in([UserStatus::BANNED]))->toBeTrue();
    });

    it('supports mixed instance and string comparisons', function () {
        $status = UserStatus::PENDING;

        expect($status->is('PENDING'))->toBeTrue();
        expect($status->is(UserStatus::PENDING))->toBeTrue();
        expect($status->is('pending'))->toBeFalse(); // case-sensitive name, not value
        expect($status->in(['PENDING', 'SUSPENDED']))->toBeTrue();
        expect($status->in([UserStatus::PENDING, 'SUSPENDED']))->toBeTrue();
        expect($status->notIn(['ACTIVE', 'INACTIVE']))->toBeTrue();
    });
});

describe('Enum Lookup Integration', function () {
    it('tryFromLabel finds all cases case-insensitively', function () {
        foreach (UserStatus::cases() as $case) {
            $label = $case->label();
            $found = UserStatus::tryFromLabel(strtolower($label));
            expect($found)->not->toBeNull();
            expect($found->name)->toBe($case->name);
        }
    });

    it('tryFromName is case-sensitive', function () {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromName('active'))->toBeNull(); // case name ≠ value
        expect(UserStatus::tryFromName('Active'))->toBeNull();
    });

    it('fromName throws for invalid and returns for valid', function () {
        expect(fn () => UserStatus::fromName('ACTIVE'))->not->toThrow(InvalidEnumException::class);
        expect(fn () => UserStatus::fromName('NONEXISTENT'))->toThrow(InvalidEnumException::class);
    });

    it('hasCase works correctly', function () {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('BANNED'))->toBeTrue();
        expect(UserStatus::hasCase('unknown'))->toBeFalse();
        expect(UserStatus::hasCase(''))->toBeFalse();
    });
});

describe('Enum Cache Integration', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('cache is populated after first resolve', function () {
        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Trigger resolution
        UserStatus::ACTIVE->label();

        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('flush clears all cached entries', function () {
        UserStatus::ACTIVE->label();
        OrderStatus::PENDING->label();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        $cache->clear();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('clearClass only clears the specified class', function () {
        UserStatus::ACTIVE->label();
        OrderStatus::PENDING->label();

        $cache = EnumCache::getInstance();
        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeTrue();
    });

    it('TTL of 0 disables caching', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        UserStatus::ACTIVE->label();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('singleton returns same instance', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });
});

describe('Enum Metadata Priority Integration', function () {
    it('per-case Label overrides class-level EnumLabel', function () {
        // ACTIVE has #[Label('Active User')]
        expect(UserStatus::ACTIVE->label())->toBe('Active User');

        // INACTIVE has no Label, no EnumLabel → auto-generated
        expect(UserStatus::INACTIVE->label())->toBe('Inactive');
    });

    it('per-case Color overrides class-level EnumColor', function () {
        // ACTIVE is mapped to 'success' via EnumColor
        expect(UserStatus::ACTIVE->color())->toBe('success');

        // BANNED has per-case #[Color('danger')]
        expect(UserStatus::BANNED->color())->toBe('danger');
    });

    it('description returns null when not defined', function () {
        expect(UserStatus::INACTIVE->description())->toBeNull();
        expect(UserStatus::SUSPENDED->description())->toBeNull();
    });

    it('description returns value when defined', function () {
        expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
        expect(UserStatus::BANNED->description())->toBe('User is permanently banned');
    });
});
