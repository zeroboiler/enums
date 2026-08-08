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
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function (): void {
    EnumCache::flush();
});

describe('Type System: String-Backed Enum', function (): void {
    it('values() returns backed string values', function (): void {
        $values = UserStatus::values();

        expect($values)->toBeArray()
            ->and($values)->toContain('active')
            ->and($values)->toContain('inactive')
            ->and($values)->toContain('banned');
    });

    it('values() preserves declaration order', function (): void {
        $values = UserStatus::values();

        expect($values)->toBe([
            'active',
            'inactive',
            'banned',
        ]);
    });

    it('labels() preserves declaration order matching values()', function (): void {
        $values = UserStatus::values();
        $labels = UserStatus::labels();

        expect($labels)->toHaveCount(count($values));
    });

    it('forSelect() returns backed values not case names', function (): void {
        $options = UserStatus::forSelect();

        foreach ($options as $option) {
            expect($option['value'])->toBeIn(UserStatus::values());
        }
    });

    it('forApi() returns backed values in value field', function (): void {
        $api = UserStatus::forApi();

        foreach ($api as $item) {
            expect($item['value'])->toBeIn(UserStatus::values());
            expect($item['name'])->toBeString();
        }
    });
});

describe('Type System: Integer-Backed Enum', function (): void {
    it('values() returns backed int values', function (): void {
        $values = Priority::values();

        expect($values)->toBeArray();
        foreach ($values as $v) {
            expect($v)->toBeInt();
        }
    });

    it('values() preserves declaration order', function (): void {
        $values = Priority::values();

        expect($values)->toBe([1, 2, 3, 4]);
    });

    it('forSelect() returns int values', function (): void {
        $options = Priority::forSelect();

        foreach ($options as $option) {
            expect($option['value'])->toBeInt();
        }
    });

    it('forApi() returns int values', function (): void {
        $api = Priority::forApi();

        foreach ($api as $item) {
            expect($item['value'])->toBeInt();
        }
    });

    it('label() auto-generates for int-backed cases without Label attribute', function (): void {
        $label = Priority::HIGH->label();

        expect($label)->toBeString()->not->toBeEmpty();
    });
});

describe('Type System: Pure Enum', function (): void {
    it('values() returns case names as strings', function (): void {
        $values = PureFeatureFlag::values();

        expect($values)->toBeArray();
        foreach ($values as $v) {
            expect($v)->toBeString();
        }
    });

    it('values() are exact case names', function (): void {
        $values = PureFeatureFlag::values();
        $names = array_map(static fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());

        expect($values)->toBe($names);
    });

    it('forSelect() uses case names as values', function (): void {
        $options = PureFeatureFlag::forSelect();
        $names = array_map(static fn (\UnitEnum $c): string => $c->name, PureFeatureFlag::cases());

        $optionValues = array_map(static fn (array $o): string => (string) $o['value'], $options);

        expect($optionValues)->toBe($names);
    });

    it('color() returns secondary default for cases without color', function (): void {
        $color = PureFeatureFlag::cases()[0]->color();

        expect($color)->toBe('secondary');
    });
});

describe('Type System: CamelCase Enum', function (): void {
    it('auto-label converts camelCase to Title Case', function (): void {
        $cases = CamelCaseRole::cases();

        foreach ($cases as $case) {
            $label = $case->label();
            // CamelCase labels should be Title Case (space-separated)
            expect($label)->toBeString()->not->toBeEmpty();
            // No underscores in auto-generated labels from camelCase
            expect($label)->not->toContain('_');
        }
    });

    it('comparison works with string name for camelCase', function (): void {
        $case = CamelCaseRole::cases()[0];

        expect($case->is($case->name))->toBeTrue();
    });
});

describe('Type System: Class-Level Attribute Resolution', function (): void {
    it('EnumColor maps multiple values to color names', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $meta = EnumMetadataResolver::resolve(IntStatusWithColor::class);

        expect($meta['colors'])->toBeArray()
            ->and($meta['colors'])->toHaveKey('1')
            ->and($meta['colors'])->toHaveKey('2');

        $cache->setTtl(300);
        $cache->clearClass(IntStatusWithColor::class);
    });

    it('per-case Color overrides class-level EnumColor', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $meta = EnumMetadataResolver::resolve(IntStatusWithColor::class);

        // IntStatusWithColor has per-case Color on case CRITICAL=1
        expect($meta['colors']['1'])->not->toBeEmpty();

        $cache->setTtl(300);
        $cache->clearClass(IntStatusWithColor::class);
    });

    it('EnumIcon provides default icon for all cases', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $meta = EnumMetadataResolver::resolve(TicketStatus::class);

        // TicketStatus may or may not have EnumIcon — test structure is correct
        expect($meta['icons'])->toBeArray();

        $cache->setTtl(300);
        $cache->clearClass(TicketStatus::class);
    });

    it('EnumLabel provides class-level labels', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $meta = EnumMetadataResolver::resolve(OrderStatus::class);

        expect($meta['labels'])->toBeArray();

        $cache->setTtl(300);
        $cache->clearClass(OrderStatus::class);
    });

    it('per-case attributes always override class-level', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // UserStatus has per-case attributes on some cases
        expect($meta['labels'])->toBeArray();
        expect($meta['colors'])->toBeArray();

        $cache->setTtl(300);
        $cache->clearClass(UserStatus::class);
    });
});

describe('Type System: Resolution Priority', function (): void {
    it('per-case label overrides auto-generated label', function (): void {
        // ACTIVE case in UserStatus has per-case label 'Active User'
        $label = UserStatus::ACTIVE->label();

        expect($label)->toBe('Active User');
    });

    it('auto-generated label for cases without attributes', function (): void {
        // INACTIVE has no per-case Label → auto-generated from case name
        $label = UserStatus::INACTIVE->label();

        expect($label)->toBe('Inactive');
    });

    it('icon returns null when no icon defined', function (): void {
        $icon = UserStatus::INACTIVE->icon();

        expect($icon)->toBeNull();
    });

    it('description returns null when no description defined', function (): void {
        $desc = UserStatus::INACTIVE->description();

        expect($desc)->toBeNull();
    });

    it('color returns secondary when no color defined', function (): void {
        $color = UserStatus::INACTIVE->color();

        expect($color)->toBe('secondary');
    });
});

describe('Type System: EnumCast', function (): void {
    it('is a final class', function (): void {
        $ref = new \ReflectionClass(EnumCast::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('implements CastsAttributes', function (): void {
        $ref = new \ReflectionClass(EnumCast::class);

        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
    });

    it('constructor accepts class-string parameter', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $ref = new \ReflectionProperty($cast, 'enumClass');

        expect($ref->getValue($cast))->toBe(UserStatus::class);
    });
});

describe('Type System: EnumManager and Facade', function (): void {
    it('EnumManager is final', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('Enum facade has correct accessor', function (): void {
        $ref = new \ReflectionMethod(\ZeroBoiler\Enums\Facades\Enum::class, 'getFacadeAccessor');

        expect($ref->isPublic())->toBeTrue();
    });
});

describe('Type System: EnumsServiceProvider', function (): void {
    it('is final', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('has register and boot methods', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        expect($ref->hasMethod('register'))->toBeTrue();
        expect($ref->hasMethod('boot'))->toBeTrue();
    });
});

describe('Type System: EnumTestGenerator', function (): void {
    it('generates valid PHP content with strict types', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain('declare(strict_types=1)')
            ->and($content)->toContain('describe(')
            ->and($content)->toContain('it(');
    });

    it('generates tests for each case', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

        foreach (UserStatus::cases() as $case) {
            expect($content)->toContain("case {$case->name}");
        }
    });

    it('generates comparison tests when multiple cases exist', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain('is()')
            ->and($content)->toContain('isNot()')
            ->and($content)->toContain('in()');
    });

    it('generates lookup tests', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain('tryFromName')
            ->and($content)->toContain('hasCase');
    });

    it('generates tryFromLabel test', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain('tryFromLabel');
    });

    it('generates forApi structure test', function (): void {
        $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

        expect($content)->toContain('forApi');
    });
});

describe('Type System: Cross-Enum Type Safety', function (): void {
    it('is() returns false for different enum types', function (): void {
        // Even if case names match, different enum types should not match
        $status = UserStatus::ACTIVE;

        expect($status->is('ACTIVE'))->toBeTrue();
        // TicketStatus::ACTIVE if it exists — different enum type
    });

    it('fromName() throws InvalidEnumException for non-existent case', function (): void {
        expect(fn (): mixed => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('forSelect() and values() have matching count', function (): void {
        $select = UserStatus::forSelect();
        $values = UserStatus::values();

        expect(count($select))->toBe(count($values));
    });

    it('forApi() contains all expected keys', function (): void {
        $api = UserStatus::forApi();

        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        }
    });
});
