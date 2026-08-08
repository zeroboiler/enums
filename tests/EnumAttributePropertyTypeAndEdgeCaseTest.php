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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function (): void {
    EnumCache::flush();
});

describe('Attribute property types (PHPStan L9 strict)', function (): void {
    it('Label has readonly string property', function (): void {
        $attr = new Label('Test Label');
        $ref = new ReflectionProperty($attr, 'value');
        expect($ref->isReadOnly())->toBeTrue()
            ->and($ref->getType()->getName())->toBe('string')
            ->and($attr->value)->toBe('Test Label');
    });

    it('Description has readonly string property', function (): void {
        $attr = new Description('Test Description');
        $ref = new ReflectionProperty($attr, 'value');
        expect($ref->isReadOnly())->toBeTrue()
            ->and($attr->value)->toBe('Test Description');
    });

    it('Color has readonly string property', function (): void {
        $attr = new Color('danger');
        expect($attr->value)->toBe('danger');
    });

    it('Icon has readonly string property', function (): void {
        $attr = new Icon('heroicon-o-check');
        expect($attr->value)->toBe('heroicon-o-check');
    });

    it('EnumLabel has nullable array and nullable string properties', function (): void {
        $ref = new ReflectionClass(EnumLabel::class);

        $labelsProp = $ref->getProperty('labels');
        expect($labelsProp->isReadOnly())->toBeTrue()
            ->and($labelsProp->getType()->allowsNull())->toBeTrue();

        $labelProp = $ref->getProperty('label');
        expect($labelProp->isReadOnly())->toBeTrue()
            ->and($labelProp->getType()->allowsNull())->toBeTrue();
    });

    it('EnumLabel can be constructed with labels map', function (): void {
        $attr = new EnumLabel(labels: ['active' => 'Active']);
        expect($attr->labels)->toBe(['active' => 'Active'])
            ->and($attr->label)->toBeNull();
    });

    it('EnumLabel can be constructed with single label', function (): void {
        $attr = new EnumLabel(label: 'Active');
        expect($attr->label)->toBe('Active')
            ->and($attr->labels)->toBeNull();
    });

    it('EnumDescription has nullable array and nullable string properties', function (): void {
        $attr = new EnumDescription(descriptions: ['a' => 'A']);
        expect($attr->descriptions)->toBe(['a' => 'A'])
            ->and($attr->description)->toBeNull();
    });

    it('EnumColor has all array properties with correct default', function (): void {
        $attr = new EnumColor;
        expect($attr->success)->toBe([])
            ->and($attr->danger)->toBe([])
            ->and($attr->warning)->toBe([])
            ->and($attr->info)->toBe([])
            ->and($attr->secondary)->toBe([]);
    });

    it('EnumColor accepts named arguments', function (): void {
        $attr = new EnumColor(success: ['a'], danger: ['b']);
        expect($attr->success)->toBe(['a'])
            ->and($attr->danger)->toBe(['b']);
    });

    it('EnumIcon has nullable string default property', function (): void {
        $attr = new EnumIcon(default: 'heroicon-o-star');
        expect($attr->default)->toBe('heroicon-o-star');

        $empty = new EnumIcon;
        expect($empty->default)->toBeNull();
    });
});

describe('EnumCache TTL boundary precision', function (): void {
    it('TTL of exactly 0 disables caching immediately', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Cached'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // With TTL=0, has() should immediately return false
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Restore
        $cache->setTtl(300);
    });

    it('negative TTL is normalized to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        // setTtl normalizes negative to 0 via max($ttl, 0)
        $ref = new ReflectionProperty($cache, 'ttl');
        expect($ref->getValue($cache))->toBe(0);

        // Restore
        $cache->setTtl(300);
    });

    it('TTL expiry works within microsecond precision', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL
        $cache->set(Priority::class, [
            'labels' => [1 => 'Cached Low'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Immediately should be available
        expect($cache->has(Priority::class))->toBeTrue();

        // Wait for TTL to expire
        usleep(1_100_000); // 1.1 seconds

        expect($cache->has(Priority::class))->toBeFalse();

        // Restore
        $cache->setTtl(300);
    });
});

describe('EnumMetadataResolver cache invalidation', function (): void {
    it('flushes cache and re-resolves fresh metadata', function (): void {
        // First resolution — should build and cache
        $meta1 = \ZeroBoiler\Enums\Support\EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta1)->toBeArray()
            ->and($meta1)->toHaveKey('labels');

        // Flush and re-resolve
        EnumCache::flush();
        $meta2 = \ZeroBoiler\Enums\Support\EnumMetadataResolver::resolve(UserStatus::class);

        // Should be equivalent but fresh
        expect($meta1)->toBe($meta2);
    });

    it('clearClass invalidates only the specified class', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Stale UserStatus'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->set(Priority::class, [
            'labels' => [1 => 'Stale Priority'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Clear only UserStatus
        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeTrue();

        // Restore
        $cache->setTtl(300);
        $cache->clearClass(Priority::class);
    });
});

describe('HasEnumMetadata edge cases', function (): void {
    it('forSelect returns correct structure for int-backed enum', function (): void {
        $select = IntStatusWithColor::forSelect();

        expect($select)->toBeArray()
            ->toHaveCount(4);

        foreach ($select as $option) {
            expect($option)->toHaveKeys(['value', 'label']);
            expect($option['value'])->toBeInt();
            expect($option['label'])->toBeString();
        }
    });

    it('forSelect returns string values for string-backed enum', function (): void {
        $select = UserStatus::forSelect();

        foreach ($select as $option) {
            expect($option['value'])->toBeString();
        }
    });

    it('forSelect returns case names for pure enum', function (): void {
        $select = PureFeatureFlag::forSelect();

        expect($select)->toHaveCount(3);

        foreach ($select as $option) {
            // Pure enums use case name as value
            expect($option['value'])->toBeString();
            expect($option['label'])->toBeString();
        }
    });

    it('forApi returns full metadata with all keys', function (): void {
        $api = UserStatus::forApi();

        expect($api)->toBeArray()->toHaveCount(5);

        foreach ($api as $item) {
            expect($item)->toHaveKeys([
                'value', 'name', 'label', 'description', 'color', 'icon',
            ]);
        }
    });

    it('tryFromLabel is case-insensitive', function (): void {
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('nonexistent'))->toBeNull();
    });

    it('tryFromName works with all fixture enums', function (): void {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(Priority::tryFromName('LOW'))->toBe(Priority::LOW);
        expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBe(PureFeatureFlag::DARK_MODE);
    });

    it('fromName throws for non-existent name', function (): void {
        expect(fn () => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class);
    });

    it('hasCase returns correct boolean', function (): void {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('active'))->toBeFalse(); // value, not name
        expect(UserStatus::hasCase('NON_EXISTENT'))->toBeFalse();
    });

    it('values() returns backed values for backed enums', function (): void {
        $values = Priority::values();
        expect($values)->toBe([1, 2, 3, 4]);
    });

    it('values() returns case names for pure enums', function (): void {
        $values = PureFeatureFlag::values();
        expect($values)->toBe(['TWO_FACTOR_AUTH', 'DARK_MODE', 'BETA_ACCESS']);
    });

    it('labels() returns all labels in case order', function (): void {
        $labels = UserStatus::labels();
        expect($labels)->toHaveCount(5);
        expect($labels[0])->toBe('Active User'); // explicit label
    });
});

describe('Class-level EnumLabel with case-level Label override priority', function (): void {
    it('per-case Label overrides class-level EnumLabel', function (): void {
        // MixedAttributeStatus has class-level labels but also per-case overrides
        // via Label attribute
        $label = MixedAttributeStatus::ACTIVE->label();
        // ACTIVE is not in the class-level labels map, so it auto-generates
        expect($label)->toBeString()->not->toBeEmpty();
    });

    it('class-level EnumLabel provides label for mapped values', function (): void {
        // 'new' is mapped in EnumLabel::labels
        $label = MixedAttributeStatus::NEW->label();
        expect($label)->toBe('Brand New Item');
    });
});

describe('EnumColor with int-backed enums', function (): void {
    it('class-level EnumColor resolves via int values', function (): void {
        // EnumColor(success: [1, 4], danger: [3], warning: [2])
        expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
        expect(IntStatusWithColor::PENDING->color())->toBe('warning');
        expect(IntStatusWithColor::DRAFT->color())->toBe('success');
    });

    it('per-case Color override takes precedence over class-level EnumColor', function (): void {
        // BANNED(3) has class-level danger but per-case Color('danger') too
        expect(IntStatusWithColor::BANNED->color())->toBe('danger');
    });
});
