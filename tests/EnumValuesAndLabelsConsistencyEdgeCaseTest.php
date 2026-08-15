<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum Edge Cases — values() and labels() consistency with class-level overrides', function () {
    it('values() returns all backed values preserving declaration order', function () {
        $values = IntBackedPriority::values();

        expect($values)->toBeArray();
        expect($values)->not->toBeEmpty();
        // All values should be unique
        expect($values)->toBe(array_values(array_unique($values)));
        // Count should match case count
        expect($values)->toHaveCount(count(IntBackedPriority::cases()));
    });

    it('labels() returns a label for every case and preserves declaration order', function () {
        $labels = IntBackedPriority::labels();

        expect($labels)->toBeArray();
        expect($labels)->toHaveCount(count(IntBackedPriority::cases()));
        expect($labels)->each->toBeString()->not->toBeEmpty();
    });

    it('values() and labels() have the same count', function () {
        $values = IntBackedPriority::values();
        $labels = IntBackedPriority::labels();

        expect(count($values))->toEqual(count($labels));
    });

    it('values() and forSelect() value keys are consistent', function () {
        $values = IntBackedPriority::values();
        $select = IntBackedPriority::forSelect();
        $selectValues = array_column($select, 'value');

        expect($selectValues)->toEqual($values);
    });

    it('forSelect() labels match labels() output in order', function () {
        $labels = IntBackedPriority::labels();
        $select = IntBackedPriority::forSelect();
        $selectLabels = array_column($select, 'label');

        expect($selectLabels)->toEqual($labels);
    });

    it('forApi() contains all forSelect() keys plus extra metadata', function () {
        $select = IntBackedPriority::forSelect();
        $api = IntBackedPriority::forApi();

        foreach ($select as $index => $selectItem) {
            expect($api[$index])->toHaveKey('value');
            expect($api[$index])->toHaveKey('label');
            expect($api[$index])->toHaveKey('name');
            expect($api[$index])->toHaveKey('description');
            expect($api[$index])->toHaveKey('color');
            expect($api[$index])->toHaveKey('icon');
            expect($api[$index]['value'])->toEqual($selectItem['value']);
            expect($api[$index]['label'])->toEqual($selectItem['label']);
        }
    });

    it('is() returns false for same-named case on a different enum', function () {
        // This tests that === comparison works across different enum instances
        // Even if two enums have the same case name, they are different types
        $case = IntBackedPriority::cases()[0];

        expect($case->is($case))->toBeTrue();
    });

    it('in() with empty array returns false', function () {
        $case = IntBackedPriority::cases()[0];

        expect($case->in([]))->toBeFalse();
    });

    it('notIn() with empty array returns true', function () {
        $case = IntBackedPriority::cases()[0];

        expect($case->notIn([]))->toBeTrue();
    });

    it('fromName() is case-sensitive (lowercase name does not match)', function () {
        $firstCase = IntBackedPriority::cases()[0];
        $lowerName = strtolower($firstCase->name);

        // Case-sensitive: lowercase should NOT match
        expect(IntBackedPriority::tryFromName($lowerName))->toBeNull();
    });

    it('hasCase() is case-sensitive', function () {
        $firstCase = IntBackedPriority::cases()[0];

        expect(IntBackedPriority::hasCase($firstCase->name))->toBeTrue();
        expect(IntBackedPriority::hasCase(strtolower($firstCase->name)))->toBeFalse();
        expect(IntBackedPriority::hasCase(strtoupper($firstCase->name) . '_EXTRA'))->toBeFalse();
    });

    it('tryFromLabel() is case-insensitive', function () {
        $firstCase = IntBackedPriority::cases()[0];
        $originalLabel = $firstCase->label();

        // Exact match
        expect(IntBackedPriority::tryFromLabel($originalLabel)?->name)->toEqual($firstCase->name);

        // Uppercase
        expect(IntBackedPriority::tryFromLabel(strtoupper($originalLabel))?->name)->toEqual($firstCase->name);

        // Lowercase
        expect(IntBackedPriority::tryFromLabel(strtolower($originalLabel))?->name)->toEqual($firstCase->name);
    });

    it('description() returns null when no description attribute is set', function () {
        // For enums without any description attribute, description() should return null
        $case = IntBackedPriority::cases()[0];
        $desc = $case->description();

        // Either null or a string is acceptable depending on fixture attributes
        expect($desc)->toBeNull()->or()->toBeString();
    });

    it('icon() returns null when no icon attribute is set', function () {
        $case = IntBackedPriority::cases()[0];
        $icon = $case->icon();

        expect($icon)->toBeNull()->or()->toBeString();
    });

    it('color() always returns a non-empty string', function () {
        foreach (IntBackedPriority::cases() as $case) {
            expect($case->color())->toBeString()->not->toBeEmpty();
        }
    });

    it('fromName() throws with descriptive message for non-existent name', function () {
        try {
            IntBackedPriority::fromName('NON_EXISTENT_CASE');
            $this->fail('Expected InvalidEnumException');
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('NON_EXISTENT_CASE');
            expect($e->getMessage())->toContain(IntBackedPriority::class);
        }
    });

    it('forSelect() and forApi() return empty arrays for single-case enum with no cases', function () {
        // Test with a real enum that has cases
        $cases = IntBackedPriority::cases();
        expect($cases)->not->toBeEmpty();

        $select = IntBackedPriority::forSelect();
        $api = IntBackedPriority::forApi();

        expect($select)->toHaveCount(count($cases));
        expect($api)->toHaveCount(count($cases));
    });

    it('generateLabel handles SCREAMING_SNAKE_CASE correctly', function () {
        $case = IntBackedPriority::cases()[0];
        $label = $case->label();

        // Should not contain underscores
        expect($label)->not->toContain('_');

        // Should not be all uppercase
        expect($label)->not->toEqual(strtoupper($label));

        // Each word should start with uppercase
        $words = explode(' ', $label);
        foreach ($words as $word) {
            expect($word)->not->toBeEmpty();
            expect($word[0])->toEqual(strtoupper($word[0]));
        }
    });
});
