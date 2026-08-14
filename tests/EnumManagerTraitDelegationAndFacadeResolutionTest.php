<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\MixedTicketType;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;

describe('EnumManager trait delegation and facade resolution', function () {
    it('forSelect delegates to trait method and returns correct structure', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(MixedTicketType::class);

        expect($result)->toBeArray()->not->toBeEmpty();
        expect($result[0])->toHaveKeys(['value', 'label']);
        expect($result[0]['value'])->toBeString();
        expect($result[0]['label'])->toBeString()->not->toBeEmpty();
    });

    it('forApi delegates to trait method and returns full metadata structure', function () {
        $manager = new EnumManager;
        $result = $manager->forApi(MixedTicketType::class);

        expect($result)->toBeArray()->not->toBeEmpty();
        expect($result[0])->toHaveKeys([
            'value', 'name', 'label', 'description', 'color', 'icon',
        ]);
    });

    it('values returns all backed values', function () {
        $manager = new EnumManager;
        $result = $manager->values(MixedTicketType::class);

        expect($result)->toBeArray()->not->toBeEmpty();
        foreach ($result as $value) {
            expect($value)->toBeString();
        }
    });

    it('labels returns all labels with correct count matching values', function () {
        $manager = new EnumManager;
        $values = $manager->values(MixedTicketType::class);
        $labels = $manager->labels(MixedTicketType::class);

        expect($labels)->toBeArray()->toHaveCount(count($values));
        foreach ($labels as $label) {
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });

    it('tryFromName returns case for valid name and null for invalid', function () {
        $manager = new EnumManager;

        $result = $manager->tryFromName(MixedTicketType::class, 'BUG');
        expect($result)->not->toBeNull();

        $missing = $manager->tryFromName(MixedTicketType::class, 'NON_EXISTENT_CASE');
        expect($missing)->toBeNull();
    });

    it('fromName returns case for valid name and throws for invalid', function () {
        $manager = new EnumManager;

        $result = $manager->fromName(MixedTicketType::class, 'BUG');
        expect($result)->not->toBeNull();

        expect(fn () => $manager->fromName(
            MixedTicketType::class,
            'DEFINITELY_NOT_A_CASE'
        ))->toThrow(InvalidEnumException::class);
    });

    it('hasCase returns true for existing and false for non-existing', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(MixedTicketType::class, 'BUG'))->toBeTrue();
        expect($manager->hasCase(MixedTicketType::class, 'GHOST_CASE'))->toBeFalse();
    });

    it('tryFromLabel resolves case by label (case-insensitive)', function () {
        $manager = new EnumManager;
        $labels = $manager->labels(MixedTicketType::class);

        expect($labels)->not->toBeEmpty();

        $firstLabel = $labels[0];
        $result = $manager->tryFromLabel(MixedTicketType::class, strtolower($firstLabel));
        expect($result)->not->toBeNull();
    });

    it('forSelect works for class with HasEnumMetadata trait', function () {
        $manager = new EnumManager;

        // PlainTestEnum uses HasEnumMetadata — should not throw
        expect(fn () => $manager->forSelect(PlainTestEnum::class))
            ->not->toThrow(\BadMethodCallException::class);

        // EmptyDefaultsStatus also uses HasEnumMetadata — should not throw
        expect(fn () => $manager->values(EmptyDefaultsStatus::class))
            ->not->toThrow(\BadMethodCallException::class);
    });

    it('forSelect returns values in case declaration order', function () {
        $manager = new EnumManager;
        $cases = MixedTicketType::cases();
        $select = $manager->forSelect(MixedTicketType::class);

        expect($select)->toHaveCount(count($cases));

        // Values should match case order
        $expectedValues = array_map(
            fn (\UnitEnum $case): string|int =>
                $case instanceof \BackedEnum ? $case->value : $case->name,
            $cases
        );
        $actualValues = array_column($select, 'value');
        expect($actualValues)->toBe($expectedValues);
    });

    it('forApi returns consistent data with individual accessors', function () {
        $manager = new EnumManager;
        $api = $manager->forApi(MixedTicketType::class);
        $cases = MixedTicketType::cases();

        foreach ($cases as $i => $case) {
            expect($api[$i]['name'])->toBe($case->name);
            expect($api[$i]['label'])->toBe($case->label());
            expect($api[$i]['description'])->toBe($case->description());
            expect($api[$i]['color'])->toBeString();
        }
    });
});
