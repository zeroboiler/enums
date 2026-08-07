<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function () {
    EnumCache::getInstance()->clear();
    EnumCache::resetInstance();
});

afterAll(function () {
    EnumCache::getInstance()->clear();
    EnumCache::resetInstance();
});

describe('Facade @method PHPStan docblocks', function () {
    it('Enum::forSelect returns correct structure via facade', function () {
        $options = Enum::forSelect(OrderStatus::class);

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('Enum::forApi returns correct structure via facade', function () {
        $api = Enum::forApi(OrderStatus::class);

        expect($api)->toBeArray();
        expect($api)->not->toBeEmpty();
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('Enum::tryFromLabel resolves via facade', function () {
        // First call to resolve metadata and cache it
        Enum::forSelect(OrderStatus::class);

        $case = Enum::tryFromLabel(OrderStatus::class, OrderStatus::PENDING->label());

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('PENDING');
    });

    it('Enum::tryFromLabel returns null for non-existent label', function () {
        Enum::forSelect(OrderStatus::class);

        $case = Enum::tryFromLabel(OrderStatus::class, 'NonExistentLabel');

        expect($case)->toBeNull();
    });

    it('facade returns int-backed values for forSelect', function () {
        $options = Enum::forSelect(Priority::class);

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();

        // All values should be integers for int-backed enum
        foreach ($options as $option) {
            expect($option['value'])->toBeInt();
            expect($option['label'])->toBeString();
        }
    });

    it('facade returns int-backed values for forApi', function () {
        $api = Enum::forApi(Priority::class);

        expect($api)->toBeArray();
        expect($api)->not->toBeEmpty();

        foreach ($api as $item) {
            expect($item['value'])->toBeInt();
            expect($item['name'])->toBeString();
        }
    });

    it('Enum facade works with string-backed enums after cache rebuild', function () {
        // Call forSelect to build cache
        $first = Enum::forSelect(UserStatus::class);

        // Flush cache
        EnumCache::flush();

        // Re-resolve
        $second = Enum::forSelect(UserStatus::class);

        expect($first)->toEqual($second);
    });

    it('Enum::forSelect returns values in declaration order', function () {
        $options = Enum::forSelect(OrderStatus::class);
        $values = array_column($options, 'value');

        $expectedOrder = array_map(
            static fn (\UnitEnum $case): string|int => $case instanceof \BackedEnum ? $case->value : $case->name,
            OrderStatus::cases()
        );

        expect($values)->toEqual($expectedOrder);
    });

    it('Enum::forApi includes all metadata fields with correct types', function () {
        $api = Enum::forApi(OrderStatus::class);

        foreach ($api as $item) {
            expect($item['value'])->toBeString();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString();
            expect($item['color'])->toBeString();
            // description and icon can be null
            expect(in_array($item['description'], [null, ...array_filter(array_column($api, 'description'), static fn (?string $v): bool => $v !== null)], true))->toBeTrue();
        }
    });
});

describe('Facade error handling', function () {
    it('Enum::forSelect throws BadMethodCallException for non-enum class', function () {
        Enum::forSelect(\stdClass::class);
    })->throws(\BadMethodCallException::class);

    it('Enum::forApi throws BadMethodCallException for class without trait', function () {
        Enum::forApi(\stdClass::class);
    })->throws(\BadMethodCallException::class);

    it('Enum::tryFromLabel throws BadMethodCallException for non-enum class', function () {
        Enum::tryFromLabel(\stdClass::class, 'test');
    })->throws(\BadMethodCallException::class);
});

describe('Attribute final class enforcement', function () {
    it('all attribute classes are final', function () {
        $attributeClasses = [
            \ZeroBoiler\Enums\Attributes\Color::class,
            \ZeroBoiler\Enums\Attributes\Description::class,
            \ZeroBoiler\Enums\Attributes\EnumColor::class,
            \ZeroBoiler\Enums\Attributes\EnumDescription::class,
            \ZeroBoiler\Enums\Attributes\EnumIcon::class,
            \ZeroBoiler\Enums\Attributes\EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\Icon::class,
            \ZeroBoiler\Enums\Attributes\Label::class,
        ];

        foreach ($attributeClasses as $class) {
            $ref = new \ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} should be final");
        }
    });

    it('EnumRule is readonly final', function () {
        $ref = new \ReflectionClass(EnumRule::class);

        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('InvalidEnumException is final', function () {
        $ref = new \ReflectionClass(InvalidEnumException::class);

        expect($ref->isFinal())->toBeTrue();
    });
});

describe('EnumCache singleton isolation', function () {
    it('flush clears all entries and timestamps', function () {
        $cache = EnumCache::getInstance();
        $cache->set('TestEnum', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeTrue();

        EnumCache::flush();

        expect($cache->has('TestEnum'))->toBeFalse();
    });

    it('clearClass removes only the specified class', function () {
        $cache = EnumCache::getInstance();
        $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('EnumB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass('EnumA');

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeTrue();
    });

    it('TTL of 0 means entries are always stale', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        expect($cache->has('TestEnum'))->toBeFalse();
    });

    it('negative TTL is normalized to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);
        $cache->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        expect($cache->has('TestEnum'))->toBeFalse();
    });

    it('resetInstance allows fresh singleton', function () {
        $first = EnumCache::getInstance();
        EnumCache::resetInstance();
        $second = EnumCache::getInstance();

        // The instances should be different objects
        expect(spl_object_id($first))->not->toEqual(spl_object_id($second));
    });
});
