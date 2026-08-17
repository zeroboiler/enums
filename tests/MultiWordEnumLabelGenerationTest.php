<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\InventoryStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;

describe('Multi-Word Enum Label Generation', function () {
    describe('auto-generated labels for multi-word cases', function () {
        it('generates Title Case from multi-word SCREAMING_SNAKE_CASE', function () {
            // IN_STOCK → "In Stock" (auto-generated since no per-case Label)
            expect(InventoryStatus::IN_STOCK->label())->toBe('In Stock');
        });

        it('generates Title Case from multi-word SCREAMING_SNAKE_CASE for all cases', function () {
            expect(InventoryStatus::OUT_OF_STOCK->label())->toBe('Out Of Stock');
            expect(InventoryStatus::ON_BACK_ORDER->label())->toBe('On Back Order');
            expect(InventoryStatus::DISCONTINUED->label())->toBe('Discontinued');
        });
    });

    describe('class-level EnumColor resolution for multi-word cases', function () {
        it('resolves color from class-level EnumColor by backed value', function () {
            expect(InventoryStatus::IN_STOCK->color())->toBe('success');
            expect(InventoryStatus::OUT_OF_STOCK->color())->toBe('danger');
            expect(InventoryStatus::ON_BACK_ORDER->color())->toBe('warning');
        });

        it('defaults to secondary when no color is mapped', function () {
            expect(InventoryStatus::DISCONTINUED->color())->toBe('secondary');
        });
    });

    describe('class-level EnumDescription resolution', function () {
        it('resolves description for mapped cases', function () {
            expect(InventoryStatus::IN_STOCK->description())->toBe('Item is available');
            expect(InventoryStatus::OUT_OF_STOCK->description())->toBe('Item is not available');
        });

        it('returns null for unmapped cases', function () {
            expect(InventoryStatus::ON_BACK_ORDER->description())->toBeNull();
            expect(InventoryStatus::DISCONTINUED->description())->toBeNull();
        });
    });

    describe('bulk methods with multi-word cases', function () {
        it('forSelect returns correct value/label pairs for all cases', function () {
            $select = InventoryStatus::forSelect();

            expect($select)->toBeArray();
            expect($select)->toHaveCount(4);

            // Check structure
            expect($select[0])->toHaveKey('value');
            expect($select[0])->toHaveKey('label');

            // Check values are backed values
            expect($select[0]['value'])->toBe('in_stock');
            expect($select[0]['label'])->toBe('In Stock');
            expect($select[1]['value'])->toBe('out_of_stock');
            expect($select[1]['label'])->toBe('Out Of Stock');
        });

        it('forApi returns full metadata with correct keys', function () {
            $api = InventoryStatus::forApi();

            expect($api)->toBeArray();
            expect($api)->toHaveCount(4);

            $first = $api[0];
            expect($first)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($first['name'])->toBe('IN_STOCK');
            expect($first['value'])->toBe('in_stock');
            expect($first['label'])->toBe('In Stock');
            expect($first['color'])->toBe('success');
            expect($first['icon'])->toBeNull();
        });

        it('values returns all backed values', function () {
            expect(InventoryStatus::values())->toBe([
                'in_stock',
                'out_of_stock',
                'on_back_order',
                'discontinued',
            ]);
        });

        it('labels returns all auto-generated labels', function () {
            expect(InventoryStatus::labels())->toBe([
                'In Stock',
                'Out Of Stock',
                'On Back Order',
                'Discontinued',
            ]);
        });
    });

    describe('comparison methods with multi-word cases', function () {
        it('is() works with enum instance', function () {
            expect(InventoryStatus::IN_STOCK->is(InventoryStatus::IN_STOCK))->toBeTrue();
            expect(InventoryStatus::IN_STOCK->is(InventoryStatus::OUT_OF_STOCK))->toBeFalse();
        });

        it('is() works with case name string', function () {
            expect(InventoryStatus::IN_STOCK->is('IN_STOCK'))->toBeTrue();
            expect(InventoryStatus::IN_STOCK->is('OUT_OF_STOCK'))->toBeFalse();
        });

        it('in() works with multiple cases', function () {
            expect(InventoryStatus::IN_STOCK->in([
                InventoryStatus::IN_STOCK,
                InventoryStatus::ON_BACK_ORDER,
            ]))->toBeTrue();

            expect(InventoryStatus::IN_STOCK->in([
                InventoryStatus::OUT_OF_STOCK,
                InventoryStatus::DISCONTINUED,
            ]))->toBeFalse();
        });

        it('notIn() excludes correctly', function () {
            expect(InventoryStatus::IN_STOCK->notIn([
                InventoryStatus::OUT_OF_STOCK,
                InventoryStatus::DISCONTINUED,
            ]))->toBeTrue();

            expect(InventoryStatus::IN_STOCK->notIn([
                InventoryStatus::IN_STOCK,
                InventoryStatus::ON_BACK_ORDER,
            ]))->toBeFalse();
        });
    });

    describe('lookup methods with multi-word cases', function () {
        it('tryFromLabel resolves multi-word labels', function () {
            expect(InventoryStatus::tryFromLabel('In Stock'))->toBe(InventoryStatus::IN_STOCK);
            expect(InventoryStatus::tryFromLabel('Out Of Stock'))->toBe(InventoryStatus::OUT_OF_STOCK);
            expect(InventoryStatus::tryFromLabel('On Back Order'))->toBe(InventoryStatus::ON_BACK_ORDER);
            expect(InventoryStatus::tryFromLabel('Discontinued'))->toBe(InventoryStatus::DISCONTINUED);
        });

        it('tryFromLabel is case-insensitive', function () {
            expect(InventoryStatus::tryFromLabel('in stock'))->toBe(InventoryStatus::IN_STOCK);
            expect(InventoryStatus::tryFromLabel('OUT OF STOCK'))->toBe(InventoryStatus::OUT_OF_STOCK);
        });

        it('tryFromLabel returns null for non-existent labels', function () {
            expect(InventoryStatus::tryFromLabel('Unknown'))->toBeNull();
            expect(InventoryStatus::tryFromLabel(''))->toBeNull();
        });

        it('tryFromName resolves multi-word case names', function () {
            expect(InventoryStatus::tryFromName('IN_STOCK'))->toBe(InventoryStatus::IN_STOCK);
            expect(InventoryStatus::tryFromName('OUT_OF_STOCK'))->toBe(InventoryStatus::OUT_OF_STOCK);
            expect(InventoryStatus::tryFromName('ON_BACK_ORDER'))->toBe(InventoryStatus::ON_BACK_ORDER);
            expect(InventoryStatus::tryFromName('DISCONTINUED'))->toBe(InventoryStatus::DISCONTINUED);
        });

        it('tryFromName returns null for non-existent names', function () {
            expect(InventoryStatus::tryFromName('UNKNOWN'))->toBeNull();
            expect(InventoryStatus::tryFromName('InStock'))->toBeNull(); // case-sensitive
        });

        it('fromName throws for non-existent names', function () {
            expect(fn () => InventoryStatus::fromName('UNKNOWN'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns correct boolean', function () {
            expect(InventoryStatus::hasCase('IN_STOCK'))->toBeTrue();
            expect(InventoryStatus::hasCase('DISCONTINUED'))->toBeTrue();
            expect(InventoryStatus::hasCase('UNKNOWN'))->toBeFalse();
        });
    });

    describe('toValue consistency', function () {
        it('returns backed value for all cases', function () {
            expect(InventoryStatus::IN_STOCK->toValue())->toBe('in_stock');
            expect(InventoryStatus::OUT_OF_STOCK->toValue())->toBe('out_of_stock');
            expect(InventoryStatus::ON_BACK_ORDER->toValue())->toBe('on_back_order');
            expect(InventoryStatus::DISCONTINUED->toValue())->toBe('discontinued');
        });
    });

    describe('cross-fixture consistency', function () {
        it('class-level attributes on TicketStatus resolve correctly alongside InventoryStatus', function () {
            // TicketStatus has EnumLabel, EnumDescription, EnumIcon
            expect(TicketStatus::OPEN->label())->toBe('Open');
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
            expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');

            // InventoryStatus has EnumColor, EnumDescription (partial)
            expect(InventoryStatus::IN_STOCK->label())->toBe('In Stock');
            expect(InventoryStatus::IN_STOCK->color())->toBe('success');
            expect(InventoryStatus::IN_STOCK->description())->toBe('Item is available');
        });
    });
});
