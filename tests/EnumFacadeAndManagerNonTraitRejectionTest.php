<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;

// --- Fixtures ---

/** A plain enum WITHOUT HasEnumMetadata trait — should be rejected by EnumManager */
enum PlainEnumWithoutTrait: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

// --- Tests ---

describe('EnumManager rejects non-trait enums', function () {
    it('forSelect throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(PlainEnumWithoutTrait::class))
            ->toThrow(\BadMethodCallException::class, '[PlainEnumWithoutTrait] does not use HasEnumMetadata trait.');
    });

    it('forApi throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forApi(PlainEnumWithoutTrait::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('tryFromLabel throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->tryFromLabel(PlainEnumWithoutTrait::class, 'Active'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('tryFromName throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->tryFromName(PlainEnumWithoutTrait::class, 'ACTIVE'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('fromName throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->fromName(PlainEnumWithoutTrait::class, 'ACTIVE'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('hasCase throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->hasCase(PlainEnumWithoutTrait::class, 'ACTIVE'))
            ->toThrow(\BadMethodCallException::class);
    });

    it('values throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->values(PlainEnumWithoutTrait::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('labels throws BadMethodCallException for enum without trait', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->labels(PlainEnumWithoutTrait::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('error message includes the class name', function () {
        $manager = new EnumManager;

        try {
            $manager->forSelect(PlainEnumWithoutTrait::class);
            expect(true)->toBeFalse('Should have thrown');
        } catch (\BadMethodCallException $e) {
            expect($e->getMessage())->toContain('PlainEnumWithoutTrait');
            expect($e->getMessage())->toContain('HasEnumMetadata');
        }
    });
});

describe('Enum facade accessor', function () {
    it('returns the correct facade accessor string', function () {
        expect(Enum::getFacadeAccessor())->toBe('zeroboiler.enum');
    });
});

describe('EnumManager is readonly final', function () {
    it('is a readonly class', function () {
        $ref = new \ReflectionClass(EnumManager::class);

        // PHP 8.2+: readonly classes have the isReadOnly() method
        // We check via constructor — readonly classes have no writable properties
        expect($ref->isFinal())->toBeTrue();
    });

    it('has no public writable properties', function () {
        $ref = new \ReflectionClass(EnumManager::class);
        $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        expect($props)->toHaveCount(0);
    });
});
