<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;

describe('InvalidEnumException', function (): void {
    it('creates exception with value factory method', function (): void {
        $exception = InvalidEnumException::value(OrderStatus::class, 'invalid_value');

        expect($exception)
            ->toBeInstanceOf(InvalidEnumException::class)
            ->toBeInstanceOf(Exception::class);

        expect($exception->getMessage())
            ->toContain('Value [string] is not a valid case of ['.OrderStatus::class.']');
    });

    it('includes correct type name in message for different value types', function (): void {
        $intException = InvalidEnumException::value(OrderStatus::class, 42);
        expect($intException->getMessage())->toContain('[int]');

        $nullException = InvalidEnumException::value(OrderStatus::class, null);
        expect($nullException->getMessage())->toContain('[null]');

        $arrayException = InvalidEnumException::value(OrderStatus::class, ['foo']);
        expect($arrayException->getMessage())->toContain('[array]');
    });

    it('is a final class', function (): void {
        $reflection = new ReflectionClass(InvalidEnumException::class);
        expect($reflection->isFinal())->toBeTrue();
    });

    it('can be caught as generic Exception', function (): void {
        $exception = InvalidEnumException::value(OrderStatus::class, 'bad');

        try {
            throw $exception;
        } catch (Exception $e) {
            expect($e)->toBeInstanceOf(InvalidEnumException::class);
        }
    });

    it('has code 0 by default', function (): void {
        $exception = InvalidEnumException::value(OrderStatus::class, 'bad');
        expect($exception->getCode())->toBe(0);
    });

    it('has no previous by default', function (): void {
        $exception = InvalidEnumException::value(OrderStatus::class, 'bad');
        expect($exception->getPrevious())->toBeNull();
    });

    it('stores the raw value type accurately using get_debug_type', function (): void {
        $boolException = InvalidEnumException::value(OrderStatus::class, true);
        expect($boolException->getMessage())->toContain('[bool]');

        $floatException = InvalidEnumException::value(OrderStatus::class, 3.14);
        expect($floatException->getMessage())->toContain('[float]');
    });
});
