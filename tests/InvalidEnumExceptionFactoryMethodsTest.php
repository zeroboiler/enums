<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('InvalidEnumException factory methods', function () {
    it('value() creates exception with string value', function () {
        $e = InvalidEnumException::value(UserStatus::class, 'nonexistent');

        expect($e)->toBeInstanceOf(InvalidEnumException::class);
        expect($e->getMessage())->toContain('nonexistent');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('value() creates exception with int value', function () {
        $e = InvalidEnumException::value(Priority::class, 99);

        expect($e->getMessage())->toContain('99');
        expect($e->getMessage())->toContain(Priority::class);
    });

    it('value() handles null value gracefully', function () {
        $e = InvalidEnumException::value(UserStatus::class, null);

        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('forName() creates exception with case name', function () {
        $e = InvalidEnumException::forName(UserStatus::class, 'NON_EXISTENT');

        expect($e)->toBeInstanceOf(InvalidEnumException::class);
        expect($e->getMessage())->toContain('NON_EXISTENT');
        expect($e->getMessage())->toContain('does not exist on enum');
    });

    it('exception is final and extends Exception', function () {
        $ref = new ReflectionClass(InvalidEnumException::class);

        expect($ref->isFinal())->toBeTrue();
        expect($ref->isSubclassOf(Exception::class))->toBeTrue();
    });
});
