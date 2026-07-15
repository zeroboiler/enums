<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;

describe('InvalidEnumException', function (): void {
    it('creates exception for invalid value', function (): void {
        $exception = InvalidEnumException::value('App\\Enums\\Status', 'invalid_value');

        // Message format: "Value [type] is not a valid case of [class]"
        expect($exception)->toBeInstanceOf(InvalidEnumException::class)
            ->and($exception->getMessage())->toContain('App\\Enums\\Status')
            ->and($exception->getMessage())->toContain('string');
    });

    it('creates exception for non-string value type', function (): void {
        $exception = InvalidEnumException::value('App\\Enums\\Priority', 999);

        // get_debug_type(999) returns 'int'
        expect($exception->getMessage())->toContain('int')
            ->and($exception->getMessage())->toContain('App\\Enums\\Priority');
    });

    it('creates exception for array value type', function (): void {
        $exception = InvalidEnumException::value('App\\Enums\\Status', ['foo']);

        expect($exception->getMessage())->toContain('array');
    });

    it('is throwable', function (): void {
        expect(fn (): never => throw InvalidEnumException::value('Test', 'x'))
            ->toThrow(InvalidEnumException::class);
    });
});
