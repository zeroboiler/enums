<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache NoReturn attributes', function () {
    it('__clone has #[NoReturn] attribute', function () {
        $ref = new ReflectionMethod(EnumCache::class, '__clone');
        $attrs = $ref->getAttributes();

        $hasNoReturn = false;
        foreach ($attrs as $attr) {
            if ($attr->getName() === 'NoReturn') {
                $hasNoReturn = true;
                break;
            }
        }

        expect($hasNoReturn)->toBeTrue('EnumCache::__clone() must have #[NoReturn] attribute');
    });

    it('__wakeup has #[NoReturn] attribute', function () {
        $ref = new ReflectionMethod(EnumCache::class, '__wakeup');
        $attrs = $ref->getAttributes();

        $hasNoReturn = false;
        foreach ($attrs as $attr) {
            if ($attr->getName() === 'NoReturn') {
                $hasNoReturn = true;
                break;
            }
        }

        expect($hasNoReturn)->toBeTrue('EnumCache::__wakeup() must have #[NoReturn] attribute');
    });

    it('__clone always throws RuntimeException', function () {
        $cache = EnumCache::getInstance();
        // Reflection to access private constructor — clone via serialization workaround
        $ref = new ReflectionMethod(EnumCache::class, '__clone');
        $ref->setAccessible(true);

        expect(fn () => $ref->invoke($cache))
            ->toThrow(\RuntimeException::class, 'singleton and cannot be cloned');
    });

    it('__wakeup always throws RuntimeException', function () {
        $cache = EnumCache::getInstance();
        $ref = new ReflectionMethod(EnumCache::class, '__wakeup');
        $ref->setAccessible(true);

        expect(fn () => $ref->invoke($cache))
            ->toThrow(\RuntimeException::class, 'singleton and cannot be unserialized');
    });
});
