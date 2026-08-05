<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Pure enum (non-backed) with HasEnumMetadata', function (): void {
    it('generates labels from case names', function (): void {
        expect(RequestState::DRAFT->label())->toBe('Draft');
        expect(RequestState::SUBMITTED->label())->toBe('Submitted');
        expect(RequestState::APPROVED->label())->toBe('Approved');
        expect(RequestState::REJECTED->label())->toBe('Rejected');
    });

    it('forSelect uses case names as values', function (): void {
        $options = RequestState::forSelect();

        expect($options)->toHaveCount(4);
        expect($options[0])->toBe(['value' => 'DRAFT', 'label' => 'Draft']);
    });

    it('forApi uses case names as values', function (): void {
        $api = RequestState::forApi();

        expect($api[0]['value'])->toBe('DRAFT');
        expect($api[0]['name'])->toBe('DRAFT');
    });

    it('values() returns case names for pure enums', function (): void {
        $values = RequestState::values();

        expect($values)->toBe(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
    });

    it('defaults color to secondary', function (): void {
        expect(RequestState::DRAFT->color())->toBe('secondary');
    });

    it('tryFromLabel works for pure enums', function (): void {
        expect(RequestState::tryFromLabel('Draft'))->toBe(RequestState::DRAFT);
        expect(RequestState::tryFromLabel('draft'))->toBe(RequestState::DRAFT);
        expect(RequestState::tryFromLabel('UNKNOWN'))->toBeNull();
    });
});

describe('tryFromName / fromName / hasCase', function (): void {
    it('resolves backed enum by name', function (): void {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromName('BANNED'))->toBe(UserStatus::BANNED);
        expect(UserStatus::tryFromName('NONEXISTENT'))->toBeNull();
    });

    it('resolves pure enum by name', function (): void {
        expect(RequestState::tryFromName('DRAFT'))->toBe(RequestState::DRAFT);
        expect(RequestState::tryFromName('REJECTED'))->toBe(RequestState::REJECTED);
        expect(RequestState::tryFromName('UNKNOWN'))->toBeNull();
    });

    it('fromName throws on invalid name', function (): void {
        expect(fn (): mixed => UserStatus::fromName('INVALID'))
            ->toThrow(InvalidEnumException::class, 'Case name [INVALID] does not exist on enum');
    });

    it('hasCase returns true for existing cases', function (): void {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('INACTIVE'))->toBeTrue();
    });

    it('hasCase returns false for non-existing cases', function (): void {
        expect(UserStatus::hasCase('GHOST'))->toBeFalse();
        expect(UserStatus::hasCase(''))->toBeFalse();
    });
});

describe('Int-backed enum with zero value', function (): void {
    it('works correctly with zero as a valid value', function (): void {
        expect(ZeroPriority::NONE->value)->toBe(0);
        expect(ZeroPriority::NONE->label())->toBe('None');
        expect(ZeroPriority::LOW->label())->toBe('Low');
        expect(ZeroPriority::HIGH->label())->toBe('High');
    });

    it('forSelect includes zero value', function (): void {
        $options = ZeroPriority::forSelect();

        expect($options)->toHaveCount(3);
        expect($options[0])->toBe(['value' => 0, 'label' => 'None']);
        expect($options[1])->toBe(['value' => 1, 'label' => 'Low']);
    });

    it('values() returns int values including zero', function (): void {
        expect(ZeroPriority::values())->toBe([0, 1, 2]);
    });
});

describe('EnumCache TTL and invalidation', function (): void {
    it('disables caching when TTL is zero', function (): void {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $originalTtl = 300;

        // Save original state
        $reflection = new \ReflectionClass($cache);
        $ttlProperty = $reflection->getProperty('ttl');
        $ttlProperty->setAccessible(true);
        $originalTtl = $ttlProperty->getValue($cache);

        // Set TTL to 0 (disabled)
        $cache->setTtl(0);

        // has() should return false when TTL <= 0
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Fake'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();

        // Restore
        $cache->setTtl($originalTtl);
        $cache->clearClass(UserStatus::class);
    });

    it('clearClass removes a specific class', function (): void {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $cache->setTtl(9999);

        $metadata = [
            'labels' => ['DRAFT' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];
        $cache->set(RequestState::class, $metadata);

        expect($cache->has(RequestState::class))->toBeTrue();

        $cache->clearClass(RequestState::class);

        expect($cache->has(RequestState::class))->toBeFalse();

        // Restore
        $cache->setTtl(300);
    });

    it('flush clears everything via static method', function (): void {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $cache->setTtl(9999);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(Priority::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        \ZeroBoiler\Enums\EnumCache::flush();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();

        // Restore
        $cache->setTtl(300);
    });
});

describe('forSelect key consistency', function (): void {
    it('all values in forSelect are unique', function (): void {
        $values = array_column(UserStatus::forSelect(), 'value');
        expect($values)->each->toBeUnique();
    });

    it('all values in forSelect for Priority are unique ints', function (): void {
        $values = array_column(Priority::forSelect(), 'value');
        expect(array_unique($values))->toHaveCount(count($values));
        expect($values)->each->toBeInt();
    });
});

describe('forApi structure consistency', function (): void {
    it('all forApi entries have consistent keys', function (): void {
        $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

        foreach (UserStatus::forApi() as $entry) {
            expect($entry)->toHaveKeys($requiredKeys);
        }
    });

    it('description and icon can be null', function (): void {
        $api = UserStatus::forApi();

        // INACTIVE has no description or icon
        $inactive = $api[1];
        expect($inactive['description'])->toBeNull();
        expect($inactive['icon'])->toBeNull();
    });
});

describe('EnumManager facade interactions', function (): void {
    it('forSelect delegates correctly', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $options = $manager->forSelect(UserStatus::class);

        expect($options)->toBeArray();
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('tryFromLabel delegates correctly', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $case = $manager->tryFromLabel(UserStatus::class, 'Active User');

        expect($case)->toBe(UserStatus::ACTIVE);
    });

    it('throws BadMethodCallException for non-metadata enums', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        // Standard PHP enum without HasEnumMetadata
        expect(fn (): mixed => $manager->forSelect(OrderStatus::class))
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('Edge cases', function (): void {
    it('tryFromLabel is case-insensitive for all cases', function (): void {
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
    });

    it('labels() returns correct count', function (): void {
        $labels = UserStatus::labels();
        expect($labels)->toHaveCount(count(UserStatus::cases()));
    });

    it('values() matches case count', function (): void {
        $values = UserStatus::values();
        expect($values)->toHaveCount(count(UserStatus::cases()));
    });
});
