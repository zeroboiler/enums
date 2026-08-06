<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;

describe('SingleCaseEnum edge case', function () {
    it('has exactly one case', function () {
        expect(SingleCaseEnum::cases())->toHaveCount(1);
    });

    it('resolves label via auto-generation', function () {
        expect(SingleCaseEnum::ONLY->label())->toBe('Only');
    });

    it('defaults color to secondary', function () {
        expect(SingleCaseEnum::ONLY->color())->toBe('secondary');
    });

    it('defaults icon to null', function () {
        expect(SingleCaseEnum::ONLY->icon())->toBeNull();
    });

    it('defaults description to null', function () {
        expect(SingleCaseEnum::ONLY->description())->toBeNull();
    });

    it('forSelect returns single entry', function () {
        $select = SingleCaseEnum::forSelect();
        expect($select)->toHaveCount(1);
        expect($select[0])->toHaveKeys(['value', 'label']);
        expect($select[0]['value'])->toBe('only');
        expect($select[0]['label'])->toBe('Only');
    });

    it('forApi returns single entry with all keys', function () {
        $api = SingleCaseEnum::forApi();
        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($api[0]['value'])->toBe('only');
        expect($api[0]['name'])->toBe('ONLY');
        expect($api[0]['color'])->toBe('secondary');
        expect($api[0]['icon'])->toBeNull();
        expect($api[0]['description'])->toBeNull();
    });

    it('values returns single value', function () {
        expect(SingleCaseEnum::values())->toBe(['only']);
    });

    it('labels returns single label', function () {
        expect(SingleCaseEnum::labels())->toBe(['Only']);
    });

    it('is() works with self', function () {
        expect(SingleCaseEnum::ONLY->is(SingleCaseEnum::ONLY))->toBeTrue();
    });

    it('is() works with string name', function () {
        expect(SingleCaseEnum::ONLY->is('ONLY'))->toBeTrue();
    });

    it('isNot() works with self', function () {
        expect(SingleCaseEnum::ONLY->isNot(SingleCaseEnum::ONLY))->toBeFalse();
    });

    it('in() works with single-element list containing self', function () {
        expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
    });

    it('in() works with string list', function () {
        expect(SingleCaseEnum::ONLY->in(['ONLY']))->toBeTrue();
    });

    it('tryFromLabel resolves by label', function () {
        expect(SingleCaseEnum::tryFromLabel('Only'))->toBe(SingleCaseEnum::ONLY);
    });

    it('tryFromName resolves by name', function () {
        expect(SingleCaseEnum::tryFromName('ONLY'))->toBe(SingleCaseEnum::ONLY);
    });

    it('tryFromName returns null for unknown name', function () {
        expect(SingleCaseEnum::tryFromName('UNKNOWN'))->toBeNull();
    });

    it('hasCase returns true for existing case', function () {
        expect(SingleCaseEnum::hasCase('ONLY'))->toBeTrue();
    });

    it('hasCase returns false for unknown case', function () {
        expect(SingleCaseEnum::hasCase('UNKNOWN'))->toBeFalse();
    });

    it('fromName returns the case', function () {
        expect(SingleCaseEnum::fromName('ONLY'))->toBe(SingleCaseEnum::ONLY);
    });

    it('fromName throws for unknown name', function () {
        expect(fn () => SingleCaseEnum::fromName('UNKNOWN'))
            ->throws(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class);
    });
});

describe('AllClassLevelEnum — all class-level attributes', function () {
    it('resolves labels from class-level EnumLabel', function () {
        expect(AllClassLevelEnum::OPEN->label())->toBe('Open Status');
        expect(AllClassLevelEnum::IN_PROGRESS->label())->toBe('In Progress');
        expect(AllClassLevelEnum::DONE->label())->toBe('Done');
    });

    it('resolves descriptions from class-level EnumDescription', function () {
        expect(AllClassLevelEnum::OPEN->description())->toBe('Task is open');
        expect(AllClassLevelEnum::IN_PROGRESS->description())->toBe('Task is being worked on');
        expect(AllClassLevelEnum::DONE->description())->toBe('Task is complete');
    });

    it('resolves icon from class-level EnumIcon default', function () {
        expect(AllClassLevelEnum::OPEN->icon())->toBe('heroicon-o-circle');
        expect(AllClassLevelEnum::IN_PROGRESS->icon())->toBe('heroicon-o-circle');
        expect(AllClassLevelEnum::DONE->icon())->toBe('heroicon-o-circle');
    });

    it('defaults color to secondary when no EnumColor is set', function () {
        expect(AllClassLevelEnum::OPEN->color())->toBe('secondary');
    });

    it('forSelect has correct count', function () {
        expect(AllClassLevelEnum::forSelect())->toHaveCount(3);
    });

    it('forApi has correct count and keys', function () {
        $api = AllClassLevelEnum::forApi();
        expect($api)->toHaveCount(3);
        foreach ($api as $entry) {
            expect($entry)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($entry['icon'])->toBe('heroicon-o-circle');
        }
    });

    it('tryFromLabel works with class-level labels', function () {
        expect(AllClassLevelEnum::tryFromLabel('Open Status'))->toBe(AllClassLevelEnum::OPEN);
        expect(AllClassLevelEnum::tryFromLabel('In Progress'))->toBe(AllClassLevelEnum::IN_PROGRESS);
        expect(AllClassLevelEnum::tryFromLabel('Done'))->toBe(AllClassLevelEnum::DONE);
    });

    it('tryFromLabel is case-insensitive', function () {
        expect(AllClassLevelEnum::tryFromLabel('open status'))->toBe(AllClassLevelEnum::OPEN);
        expect(AllClassLevelEnum::tryFromLabel('IN PROGRESS'))->toBe(AllClassLevelEnum::IN_PROGRESS);
    });

    it('values returns backed values', function () {
        expect(AllClassLevelEnum::values())->toBe(['open', 'in_progress', 'done']);
    });

    it('labels returns class-level labels', function () {
        expect(AllClassLevelEnum::labels())->toBe(['Open Status', 'In Progress', 'Done']);
    });
});
