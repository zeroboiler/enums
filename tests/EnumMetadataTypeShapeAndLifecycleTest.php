<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum Metadata Type Shape Contract', function () {
    it('resolve() returns the exact expected keys for a string-backed enum', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta)->toBeArray();
        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('resolve() returns arrays with correct inner types for all metadata keys', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // Each metadata key must be an array<string, string>
        foreach (['labels', 'descriptions', 'colors', 'icons'] as $key) {
            expect($meta[$key])->toBeArray();
            foreach ($meta[$key] as $idx => $val) {
                expect($idx)->toBeString();
                expect($val)->toBeString();
            }
        }
    });

    it('resolve() returns correct shape for an int-backed enum', function () {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        expect($meta)->toBeArray();
        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);

        // Int-backed: values used as keys should be stringified
        foreach ($meta['labels'] as $idx => $val) {
            expect($idx)->toBeString();
            expect($val)->toBeString();
        }
    });

    it('resolve() returns correct shape for a pure enum', function () {
        $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        expect($meta)->toBeArray();
        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);

        // Pure enum: case names used as keys
        expect($meta['labels'])->toHaveKey('TWO_FACTOR_AUTH');
        expect($meta['labels'])->toHaveKey('DARK_MODE');
        expect($meta['labels'])->toHaveKey('BETA_ACCESS');
    });

    it('labels values are always non-empty strings for all enum types', function () {
        $enums = [UserStatus::class, Priority::class, PureFeatureFlag::class];

        foreach ($enums as $enumClass) {
            $meta = EnumMetadataResolver::resolve($enumClass);
            foreach ($meta['labels'] as $value => $label) {
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        }
    });

    it('colors values are always non-empty strings for all enum types', function () {
        $enums = [UserStatus::class, Priority::class, PureFeatureFlag::class];

        foreach ($enums as $enumClass) {
            $meta = EnumMetadataResolver::resolve($enumClass);
            foreach ($meta['colors'] as $value => $color) {
                expect($color)->toBeString();
                expect($color)->not->toBeEmpty();
            }
        }
    });

    it('descriptions and icons are nullable — may be empty arrays', function () {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        // Priority has no Description or Icon attributes
        expect($meta['descriptions'])->toBeArray();
        expect($meta['icons'])->toBeArray();
    });

    it('cache invalidation forces re-resolution with same shape', function () {
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::invalidate(UserStatus::class);
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta1)->toBe($meta2);
        expect($meta2)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('invalidateAll clears all caches and re-resolution preserves shape', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('per-case Color attribute overrides class-level EnumColor', function () {
        $meta = EnumMetadataResolver::resolve(IntStatusWithColor::class);

        // Int-backed: keys are stringified int values, not case names
        // ACTIVE=1, PENDING=2, BANNED=3, DRAFT=4
        // BANNED (value 3) has per-case Color('danger') which should override class-level
        expect($meta['colors'])->toHaveKey('3');
        expect($meta['colors']['3'])->toBe('danger');
    });

    it('class-level EnumColor maps int-backed values to color names', function () {
        $meta = EnumMetadataResolver::resolve(IntStatusWithColor::class);

        // EnumColor maps should be stringified int keys
        foreach ($meta['colors'] as $key => $color) {
            expect($key)->toBeString();
            expect($color)->toBeString();
        }
    });
});

describe('Enum Strict Type Safety Contract', function () {
    it('is() uses strict identity for same enum instance', function () {
        $active = UserStatus::ACTIVE;
        $alsoActive = UserStatus::ACTIVE;

        // Same instance — strict identity
        expect($active)->toBe($alsoActive);
        expect($active->is($alsoActive))->toBeTrue();
    });

    it('is() uses strict identity for different enum instance', function () {
        $active = UserStatus::ACTIVE;
        $banned = UserStatus::BANNED;

        expect($active->is($banned))->toBeFalse();
        expect($active->isNot($banned))->toBeTrue();
    });

    it('is() uses case-sensitive string comparison', function () {
        $active = UserStatus::ACTIVE;

        expect($active->is('ACTIVE'))->toBeTrue();
        expect($active->is('Active'))->toBeFalse();
        expect($active->is('active'))->toBeFalse(); // backed value, not case name
    });

    it('tryFromName is case-sensitive', function () {
        expect(UserStatus::tryFromName('ACTIVE'))->toBeInstanceOf(UserStatus::class);
        expect(UserStatus::tryFromName('Active'))->toBeNull();
        expect(UserStatus::tryFromName('active'))->toBeNull();
    });

    it('fromName throws for non-existent case with correct exception type', function () {
        expect(fn () => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('in() uses strict comparison for each element', function () {
        $active = UserStatus::ACTIVE;

        // All same — true
        expect($active->in([UserStatus::ACTIVE, UserStatus::BANNED]))->toBeTrue();

        // None match — false
        expect($active->in([UserStatus::BANNED, UserStatus::PENDING]))->toBeFalse();

        // Empty array — false
        expect($active->in([]))->toBeFalse();

        // Mixed instances and strings
        expect($active->in([UserStatus::ACTIVE, 'BANNED']))->toBeTrue();
        expect($active->in(['ACTIVE']))->toBeTrue();
    });

    it('values() returns correct types for string-backed enum', function () {
        $values = UserStatus::values();

        foreach ($values as $value) {
            expect($value)->toBeString();
        }
    });

    it('values() returns correct types for int-backed enum', function () {
        $values = Priority::values();

        foreach ($values as $value) {
            expect($value)->toBeInt();
        }
    });

    it('values() returns case names for pure enum', function () {
        $values = PureFeatureFlag::values();
        $expected = array_map(
            fn (\UnitEnum $c): string => $c->name,
            PureFeatureFlag::cases()
        );

        expect($values)->toBe($expected);
    });

    it('forSelect() returns consistent structure across all enum types', function () {
        $enums = [
            UserStatus::class,
            Priority::class,
            PureFeatureFlag::class,
        ];

        foreach ($enums as $enumClass) {
            $select = $enumClass::forSelect();
            expect($select)->toBeArray();
            expect($select)->not->toBeEmpty();

            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        }
    });

    it('forApi() returns consistent structure across all enum types', function () {
        $enums = [
            UserStatus::class,
            Priority::class,
            PureFeatureFlag::class,
        ];

        foreach ($enums as $enumClass) {
            $api = $enumClass::forApi();
            expect($api)->toBeArray();
            expect($api)->not->toBeEmpty();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        }
    });
});

describe('Enum Cache Lifecycle Contract', function () {
    beforeEach(function () {
        EnumMetadataResolver::invalidateAll();
    });

    it('cache is empty after invalidateAll', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::invalidateAll();

        // After invalidation, next resolve should rebuild
        $meta = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta)->toBeArray();
        expect($meta)->toHaveKey('labels');
    });

    it('cache TTL 0 means always stale', function () {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $cache->setTtl(0);

        expect($cache->has(UserStatus::class))->toBeFalse();

        // Restore
        $cache->setTtl(300);
    });

    it('cache respects negative TTL normalization to 0', function () {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Restore
        $cache->setTtl(300);
    });

    it('singleton identity is preserved across multiple getInstance calls', function () {
        $a = \ZeroBoiler\Enums\EnumCache::getInstance();
        $b = \ZeroBoiler\Enums\EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resetInstance creates a fresh singleton', function () {
        $original = \ZeroBoiler\Enums\EnumCache::getInstance();
        $original->setTtl(42);

        \ZeroBoiler\Enums\EnumCache::resetInstance();

        $fresh = \ZeroBoiler\Enums\EnumCache::getInstance();
        // Fresh instance should have default TTL (300)
        expect($fresh->getTtl())->toBe(300);
        // Should be a different object
        expect($fresh)->not->toBe($original);
    });
});
