<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * EnumRule validation type mismatch rejection tests.
 *
 * Verifies that EnumRule correctly rejects values whose PHP type
 * does not match the enum's backing type — a PHPStan Level 9
 * safety requirement to prevent TypeErrors from BackedEnum::tryFrom().
 *
 * @covers \ZeroBoiler\Enums\Rules\EnumRule
 */

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumRule type mismatch rejection', function (): void {
    it('rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(IntBackedPriority::class);

        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, ['priority' => 'high'], ['priority' => $rule]);

        expect($validator->fails())->toBeTrue();
    });

    it('rejects int value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, ['status' => 12345], ['status' => $rule]);

        expect($validator->fails())->toBeTrue();
    });

    it('accepts int value for int-backed enum', function (): void {
        $firstValue = IntBackedPriority::cases()[0]?->value;

        if ($firstValue === null) {
            return;
        }

        $rule = EnumRule::for(IntBackedPriority::class);

        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, ['priority' => $firstValue], ['priority' => $rule]);

        expect($validator->passes())->toBeTrue();
    });

    it('rejects null when not nullable', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, ['status' => null], ['status' => $rule]);

        expect($validator->fails())->toBeTrue();
    });

    it('accepts null when nullable', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, ['status' => null], ['status' => $rule]);

        expect($validator->passes())->toBeTrue();
    });

    it('rejects invalid string value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, ['status' => 'nonexistent_value'], ['status' => $rule]);

        expect($validator->fails())->toBeTrue();
    });

    it('accepts valid string value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, ['status' => 'active'], ['status' => $rule]);

        expect($validator->passes())->toBeTrue();
    });
});
