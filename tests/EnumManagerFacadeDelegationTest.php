<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager delegation and error handling', function (): void {
    it('forSelect returns value/label pairs for string-backed enum', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $options = $manager->forSelect(UserStatus::class);

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('forSelect returns value/label pairs for int-backed enum', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $options = $manager->forSelect(IntBackedPriority::class);

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();
    });

    it('forApi returns full metadata with all expected keys', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $api = $manager->forApi(UserStatus::class);

        expect($api)->toBeArray();
        expect($api)->not->toBeEmpty();
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('forApi returns full metadata for pure enum', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $api = $manager->forApi(PureFeatureFlag::class);

        expect($api)->toBeArray();
        expect($api)->not->toBeEmpty();
    });

    it('tryFromLabel resolves case by label (case-insensitive)', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $firstCase = UserStatus::cases()[0];
        $case = $manager->tryFromLabel(UserStatus::class, $firstCase->label());

        expect($case)->not->toBeNull();
        expect($case)->toBe($firstCase);
    });

    it('tryFromLabel returns null for non-existent label', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $case = $manager->tryFromLabel(UserStatus::class, 'non-existent-label-xyz-123');

        expect($case)->toBeNull();
    });

    it('tryFromLabel is case-insensitive', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $firstCase = UserStatus::cases()[0];
        $label = $firstCase->label();
        $case = $manager->tryFromLabel(UserStatus::class, strtolower($label));

        expect($case)->toBeInstanceOf(UserStatus::class);
    });

    it('tryFromName resolves case by name', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $case = $manager->tryFromName(UserStatus::class, 'ACTIVE');

        expect($case)->not->toBeNull();
        expect($case->name)->toBe('ACTIVE');
    });

    it('tryFromName returns null for non-existent name', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $case = $manager->tryFromName(UserStatus::class, 'NON_EXISTENT');

        expect($case)->toBeNull();
    });

    it('fromName resolves case and throws on failure', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $case = $manager->fromName(UserStatus::class, 'ACTIVE');

        expect($case)->toBeInstanceOf(UserStatus::class);
        expect($case->name)->toBe('ACTIVE');
    });

    it('fromName throws InvalidEnumException for non-existent name', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $manager->fromName(UserStatus::class, 'NON_EXISTENT');
    })->throws(InvalidEnumException::class);

    it('hasCase returns true for existing case', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
    });

    it('hasCase returns false for non-existent case', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;

        expect($manager->hasCase(UserStatus::class, 'NON_EXISTENT'))->toBeFalse();
    });

    it('values returns all backed values for string enum', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $values = $manager->values(UserStatus::class);

        expect($values)->toBeArray();
        expect($values)->not->toBeEmpty();
        expect($values)->toHaveCount(count(UserStatus::cases()));
    });

    it('values returns int values for int-backed enum', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $values = $manager->values(IntBackedPriority::class);

        expect($values)->toBeArray();
        expect($values)->not->toBeEmpty();
    });

    it('labels returns all labels in case order', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $labels = $manager->labels(UserStatus::class);

        expect($labels)->toBeArray();
        expect($labels)->not->toBeEmpty();
        expect($labels)->toHaveCount(count(UserStatus::cases()));
        // All labels should be non-empty strings
        foreach ($labels as $label) {
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });

    it('forSelect throws BadMethodCallException for non-metadata enum', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $manager->forSelect(\SomePlainEnum::class);
    })->throws(\BadMethodCallException::class);

    it('forApi throws BadMethodCallException for non-metadata enum', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $manager->forApi(\SomePlainEnum::class);
    })->throws(\BadMethodCallException::class);

    it('values throws BadMethodCallException for non-metadata enum', function (): void {
        $manager = new \ZeroBoiler\Enums\EnumManager;
        $manager->values(\SomePlainEnum::class);
    })->throws(\BadMethodCallException::class);
});

/**
 * Plain enum without HasEnumMetadata — for testing error paths.
 */
enum SomePlainEnum: string
{
    case FOO = 'foo';
    case BAR = 'bar';
}

describe('EnumFacade static proxy test', function (): void {
    it('Facade accessor resolves to EnumManager', function (): void {
        expect(Enum::getFacadeAccessor())->toBe('zeroboiler.enum');
    });
});
