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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;

// ── Fixture: String-backed enum with all attribute types ──

#[EnumColor(success: ['active'], danger: ['banned'], warning: ['pending'])]
#[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
#[EnumDescription(descriptions: ['active' => 'Can access system', 'banned' => 'Permanently blocked'])]
#[EnumIcon(default: 'heroicon-o-flag', icons: ['active' => 'heroicon-o-check'])]
enum FullAttributeStringEnum: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    #[Label('Custom Active')]
    #[Icon('heroicon-o-star')]
    #[Description('Fully custom override')]
    case ACTIVE = 'active';

    case PENDING = 'pending';

    #[Color('danger')]
    #[Description('Override banned description')]
    case BANNED = 'banned';
}

// ── Fixture: Int-backed enum with EnumColor only ──

#[EnumColor(success: [1], danger: [0], warning: [2])]
enum IntBackedColorEnum: int
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case ONLINE = 1;
    case OFFLINE = 0;
    case AWAY = 2;
}

// ── Fixture: Pure enum (no backing) ──

enum PureStateEnum
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case IDLE;
    case RUNNING;
    case STOPPED;
}

// ── Fixture: Empty class-level attributes (all defaults) ──

enum DefaultFallbackEnum: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}

describe('Enum attribute contract full coverage', function () {
    // ── String-backed: FullAttributeStringEnum ──

    describe('FullAttributeStringEnum (string-backed, all attributes)', function () {
        it('per-case Label overrides class-level EnumLabel', function () {
            expect(FullAttributeStringEnum::ACTIVE->label())->toBe('Custom Active');
            expect(FullAttributeStringEnum::BANNED->label())->toBe('Banned User'); // class-level
            expect(FullAttributeStringEnum::PENDING->label())->toBe('Pending'); // auto-generated
        });

        it('per-case Description overrides class-level EnumDescription', function () {
            expect(FullAttributeStringEnum::ACTIVE->description())->toBe('Fully custom override');
            expect(FullAttributeStringEnum::BANNED->description())->toBe('Override banned description');
            expect(FullAttributeStringEnum::PENDING->description())->toBeNull(); // not defined
        });

        it('per-case Icon overrides class-level EnumIcon default and map', function () {
            expect(FullAttributeStringEnum::ACTIVE->icon())->toBe('heroicon-o-star'); // per-case
            expect(FullAttributeStringEnum::PENDING->icon())->toBe('heroicon-o-flag'); // class-level default
            expect(FullAttributeStringEnum::BANNED->icon())->toBe('heroicon-o-flag'); // class-level default
        });

        it('per-case Color overrides class-level EnumColor', function () {
            expect(FullAttributeStringEnum::ACTIVE->color())->toBe('success'); // class-level
            expect(FullAttributeStringEnum::BANNED->color())->toBe('danger'); // per-case override
            expect(FullAttributeStringEnum::PENDING->color())->toBe('warning'); // class-level
        });

        it('forSelect returns backed values with labels', function () {
            $select = FullAttributeStringEnum::forSelect();

            expect($select)->toHaveCount(3);
            expect($select[0])->toHaveKeys(['value', 'label']);
            expect($select[0]['value'])->toBe('active');
            expect($select[1]['value'])->toBe('pending');
            expect($select[2]['value'])->toBe('banned');
        });

        it('forApi returns full metadata with all fields populated', function () {
            $api = FullAttributeStringEnum::forApi();

            expect($api)->toHaveCount(3);
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('values returns backed string values', function () {
            $values = FullAttributeStringEnum::values();

            expect($values)->toBe(['active', 'pending', 'banned']);
        });

        it('labels returns in declaration order', function () {
            $labels = FullAttributeStringEnum::labels();

            expect($labels)->toBe(['Custom Active', 'Pending', 'Banned User']);
        });
    });

    // ── Int-backed: IntBackedColorEnum ──

    describe('IntBackedColorEnum (int-backed)', function () {
        it('resolves color from class-level EnumColor by int value', function () {
            expect(IntBackedColorEnum::ONLINE->color())->toBe('success');
            expect(IntBackedColorEnum::OFFLINE->color())->toBe('danger');
            expect(IntBackedColorEnum::AWAY->color())->toBe('warning');
        });

        it('auto-generates labels from case names', function () {
            expect(IntBackedColorEnum::ONLINE->label())->toBe('Online');
            expect(IntBackedColorEnum::OFFLINE->label())->toBe('Offline');
            expect(IntBackedColorEnum::AWAY->label())->toBe('Away');
        });

        it('description and icon default to null', function () {
            expect(IntBackedColorEnum::ONLINE->description())->toBeNull();
            expect(IntBackedColorEnum::ONLINE->icon())->toBeNull();
        });

        it('forSelect uses int backed values', function () {
            $select = IntBackedColorEnum::forSelect();

            expect($select[0]['value'])->toBe(1);
            expect($select[1]['value'])->toBe(0);
            expect($select[2]['value'])->toBe(2);
        });

        it('values returns int backed values', function () {
            expect(IntBackedColorEnum::values())->toBe([1, 0, 2]);
        });

        it('tryFromLabel works case-insensitively', function () {
            expect(IntBackedColorEnum::tryFromLabel('online'))->toBe(IntBackedColorEnum::ONLINE);
            expect(IntBackedColorEnum::tryFromLabel('ONLINE'))->toBe(IntBackedColorEnum::ONLINE);
            expect(IntBackedColorEnum::tryFromLabel('Online'))->toBe(IntBackedColorEnum::ONLINE);
        });

        it('fromName throws for invalid name', function () {
            expect(fn () => IntBackedColorEnum::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });
    });

    // ── Pure enum: PureStateEnum ──

    describe('PureStateEnum (no backing type)', function () {
        it('auto-generates labels for all cases', function () {
            expect(PureStateEnum::IDLE->label())->toBe('Idle');
            expect(PureStateEnum::RUNNING->label())->toBe('Running');
            expect(PureStateEnum::STOPPED->label())->toBe('Stopped');
        });

        it('color defaults to secondary', function () {
            expect(PureStateEnum::IDLE->color())->toBe('secondary');
            expect(PureStateEnum::RUNNING->color())->toBe('secondary');
        });

        it('description and icon default to null', function () {
            expect(PureStateEnum::IDLE->description())->toBeNull();
            expect(PureStateEnum::IDLE->icon())->toBeNull();
        });

        it('forSelect uses case names as values', function () {
            $select = PureStateEnum::forSelect();

            expect($select[0]['value'])->toBe('IDLE');
            expect($select[1]['value'])->toBe('RUNNING');
            expect($select[2]['value'])->toBe('STOPPED');
        });

        it('values returns case names', function () {
            expect(PureStateEnum::values())->toBe(['IDLE', 'RUNNING', 'STOPPED']);
        });

        it('forApi returns full metadata with name as value', function () {
            $api = PureStateEnum::forApi();

            expect($api[0]['value'])->toBe('IDLE');
            expect($api[0]['name'])->toBe('IDLE');
            expect($api[0]['label'])->toBe('Idle');
            expect($api[0]['color'])->toBe('secondary');
        });

        it('comparison methods work with pure enums', function () {
            $state = PureStateEnum::RUNNING;

            expect($state->is(PureStateEnum::RUNNING))->toBeTrue();
            expect($state->is('RUNNING'))->toBeTrue();
            expect($state->isNot(PureStateEnum::IDLE))->toBeTrue();
            expect($state->in([PureStateEnum::RUNNING, PureStateEnum::IDLE]))->toBeTrue();
            expect($state->notIn([PureStateEnum::IDLE, PureStateEnum::STOPPED]))->toBeTrue();
        });

        it('tryFromLabel works for pure enums', function () {
            expect(PureStateEnum::tryFromLabel('Running'))->toBe(PureStateEnum::RUNNING);
            expect(PureStateEnum::tryFromLabel('running'))->toBe(PureStateEnum::RUNNING);
            expect(PureStateEnum::tryFromLabel('NONEXISTENT'))->toBeNull();
        });
    });

    // ── Default fallback enum ──

    describe('DefaultFallbackEnum (no class-level attributes)', function () {
        it('auto-generates all labels', function () {
            expect(DefaultFallbackEnum::DRAFT->label())->toBe('Draft');
            expect(DefaultFallbackEnum::PUBLISHED->label())->toBe('Published');
        });

        it('color defaults to secondary', function () {
            expect(DefaultFallbackEnum::DRAFT->color())->toBe('secondary');
            expect(DefaultFallbackEnum::PUBLISHED->color())->toBe('secondary');
        });

        it('description and icon are null', function () {
            expect(DefaultFallbackEnum::DRAFT->description())->toBeNull();
            expect(DefaultFallbackEnum::DRAFT->icon())->toBeNull();
        });

        it('select option values are unique', function () {
            $values = array_column(DefaultFallbackEnum::forSelect(), 'value');
            expect($values)->each->toBeUnique();
        });
    });

    // ── Cross-type consistency ──

    describe('Cross-type consistency', function () {
        it('forSelect always returns value+label structure regardless of backing type', function () {
            $stringSelect = FullAttributeStringEnum::forSelect();
            $intSelect = IntBackedColorEnum::forSelect();
            $pureSelect = PureStateEnum::forSelect();

            foreach ([$stringSelect, $intSelect, $pureSelect] as $select) {
                foreach ($select as $option) {
                    expect($option)->toHaveKeys(['value', 'label']);
                    expect($option['label'])->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('forApi always returns full metadata structure', function () {
            $stringApi = FullAttributeStringEnum::forApi();
            $intApi = IntBackedColorEnum::forApi();
            $pureApi = PureStateEnum::forApi();

            $expectedKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

            foreach ([$stringApi, $intApi, $pureApi] as $api) {
                foreach ($api as $item) {
                    expect($item)->toHaveKeys($expectedKeys);
                    expect($item['color'])->toBeString()->not->toBeEmpty();
                }
            }
        });
    });
});
