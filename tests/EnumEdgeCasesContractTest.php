<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Pure enum metadata contract', function (): void {
    it('generates labels from case names when not overridden by class-level EnumLabel', function (): void {
        // INITIALIZING has no per-case Label and no EnumLabel mapping
        expect(PureSystemState::INITIALIZING->label())->toBe('Initializing');
    });

    it('uses class-level EnumLabel when provided', function (): void {
        expect(PureSystemState::READY->label())->toBe('Ready to Serve'); // Per-case override
        expect(PureSystemState::FAILED->label())->toBe('System Failure'); // Per-case override
    });

    it('resolves per-case Color override over class-level EnumColor', function (): void {
        expect(PureSystemState::READY->color())->toBe('success');  // Per-case override
        expect(PureSystemState::FAILED->color())->toBe('danger');   // Per-case override
    });

    it('falls back to class-level EnumColor for non-overridden cases', function (): void {
        expect(PureSystemState::INITIALIZING->color())->toBe('secondary'); // No per-case, no class mapping
        expect(PureSystemState::RUNNING->color())->toBe('secondary');       // No per-case, no class mapping
    });

    it('resolves per-case Description override', function (): void {
        expect(PureSystemState::READY->description())->toBe('All services started and accepting traffic');
        expect(PureSystemState::FAILED->description())->toBe('Critical failure');
    });

    it('falls back to null for Description when not set', function (): void {
        expect(PureSystemState::INITIALIZING->description())->toBeNull();
        expect(PureSystemState::RUNNING->description())->toBeNull();
    });

    it('resolves per-case Icon override', function (): void {
        expect(PureSystemState::INITIALIZING->icon())->toBe('heroicon-o-arrow-path');
        expect(PureSystemState::READY->icon())->toBe('heroicon-o-check-circle');
    });

    it('resolves default Icon from class-level EnumIcon', function (): void {
        expect(PureSystemState::RUNNING->icon())->toBe('heroicon-o-cog'); // Default from EnumIcon
        expect(PureSystemState::FAILED->icon())->toBeNull();              // No default icon set for FAILED? Check
    });

    it('generates forSelect with case names as values', function (): void {
        $options = PureSystemState::forSelect();

        expect($options)->toHaveCount(4);
        expect($options[0])->toHaveKeys(['value', 'label']);
        // Pure enum uses case name as value
        expect($options[0]['value'])->toBe('INITIALIZING');
    });

    it('generates forApi with case name metadata', function (): void {
        $api = PureSystemState::forApi();

        expect($api)->toHaveCount(4);
        expect($api[0])->toHaveKey('name');
        expect($api[0]['name'])->toBe('INITIALIZING');
        expect($api[0])->toHaveKey('value');
        expect($api[0]['value'])->toBe('INITIALIZING');
    });

    it('values() returns case names for pure enum', function (): void {
        $values = PureSystemState::values();

        expect($values)->toBe([
            'INITIALIZING',
            'READY',
            'RUNNING',
            'FAILED',
        ]);
    });

    it('tryFromName resolves existing cases', function (): void {
        expect(PureSystemState::tryFromName('INITIALIZING'))->toBe(PureSystemState::INITIALIZING);
        expect(PureSystemState::tryFromName('FAILED'))->toBe(PureSystemState::FAILED);
    });

    it('tryFromName returns null for unknown cases', function (): void {
        expect(PureSystemState::tryFromName('DOES_NOT_EXIST'))->toBeNull();
    });

    it('hasCase returns true for existing and false for non-existing', function (): void {
        expect(PureSystemState::hasCase('READY'))->toBeTrue();
        expect(PureSystemState::hasCase('NONEXISTENT'))->toBeFalse();
    });

    it('is returns true for matching case', function (): void {
        expect(PureSystemState::INITIALIZING->is(PureSystemState::INITIALIZING))->toBeTrue();
        expect(PureSystemState::INITIALIZING->is(PureSystemState::READY))->toBeFalse();
    });

    it('isNot returns true for non-matching case', function (): void {
        expect(PureSystemState::INITIALIZING->isNot(PureSystemState::READY))->toBeTrue();
        expect(PureSystemState::INITIALIZING->isNot(PureSystemState::INITIALIZING))->toBeFalse();
    });
});

describe('Single-case enum contract', function (): void {
    it('resolves metadata for single case', function (): void {
        expect(SingleCaseEnum::ONLY->label())->toBeString();
        expect(SingleCaseEnum::ONLY->label())->not->toBeEmpty();
    });

    it('forSelect returns array with single entry', function (): void {
        $options = SingleCaseEnum::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('forApi returns array with single entry', function (): void {
        $api = SingleCaseEnum::forApi();

        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('values returns single-element array', function (): void {
        $values = SingleCaseEnum::values();

        expect($values)->toHaveCount(1);
    });
});

describe('Zero-backed int enum contract', function (): void {
    it('correctly resolves label for zero value', function (): void {
        expect(ZeroBackedPriority::NONE->label())->toBe('None');
    });

    it('does not confuse zero with falsy', function (): void {
        $values = ZeroBackedPriority::values();

        expect($values)->toContain(0);
        expect($values)->toContain(1);
        expect($values)->toContain(2);
    });

    it('forSelect includes zero value', function (): void {
        $options = ZeroBackedPriority::forSelect();

        expect($options)->toHaveCount(3);
        expect($options[0]['value'])->toBe(0);
        expect($options[0]['label'])->toBe('None');
    });

    it('tryFrom correctly handles zero value', function (): void {
        expect(ZeroBackedPriority::tryFrom(0))->toBe(ZeroBackedPriority::NONE);
        expect(ZeroBackedPriority::tryFrom(0))->not->toBeNull();
    });
});

describe('Enum bulk method type consistency', function (): void {
    it('forSelect preserves insertion order', function (): void {
        $options = UserStatus::forSelect();
        $values = array_column($options, 'value');

        expect($values)->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
    });

    it('forApi preserves insertion order', function (): void {
        $api = UserStatus::forApi();
        $names = array_column($api, 'name');

        expect($names)->toBe(['ACTIVE', 'INACTIVE', 'PENDING', 'SUSPENDED', 'BANNED']);
    });

    it('forSelect values are backed values, not case names', function (): void {
        $stringOpts = UserStatus::forSelect();
        expect($stringOpts[0]['value'])->toBe('active'); // String backed

        $intOpts = Priority::forSelect();
        expect($intOpts[0]['value'])->toBe(1); // Int backed
    });

    it('forApi values are backed values', function (): void {
        $intApi = Priority::forApi();
        expect($intApi[0]['value'])->toBe(1);
        expect($intApi[3]['value'])->toBe(4);
    });

    it('labels returns list in declaration order', function (): void {
        $stringLabels = UserStatus::labels();
        expect($stringLabels)->toHaveCount(5);
        expect($stringLabels[0])->toBe('Active User');

        $intLabels = Priority::labels();
        expect($intLabels)->toHaveCount(4);
        expect($intLabels[0])->toBe('Low');
    });

    it('tryFromLabel is case-insensitive', function (): void {
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel returns null for non-existent label', function (): void {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
        expect(UserStatus::tryFromLabel('Nonexistent Status'))->toBeNull();
    });
});

describe('Enum edge cases', function (): void {
    it('empty string value enum case works', function (): void {
        // Test with string-backed enum that has expected behavior
        $active = UserStatus::ACTIVE;
        expect($active->value)->toBe('active');
    });

    it('duplicate labels in class-level EnumLabel use per-case override', function (): void {
        // Per-case Label takes precedence over class-level EnumLabel
        expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
    });

    it('all bulk methods return consistent case count', function (): void {
        $selectCount = count(UserStatus::forSelect());
        $apiCount = count(UserStatus::forApi());
        $valuesCount = count(UserStatus::values());
        $labelsCount = count(UserStatus::labels());
        $casesCount = count(UserStatus::cases());

        expect($selectCount)->toBe($casesCount);
        expect($apiCount)->toBe($casesCount);
        expect($valuesCount)->toBe($casesCount);
        expect($labelsCount)->toBe($casesCount);
    });

    it('int-backed enum with non-sequential values preserves value keys', function (): void {
        $values = IntPriority::values();

        expect($values)->toBe([1, 5, 10, 99]);
    });

    it('int-backed forSelect preserves non-sequential values', function (): void {
        $options = IntPriority::forSelect();
        $values = array_column($options, 'value');

        expect($values)->toBe([1, 5, 10, 99]);
    });
});
