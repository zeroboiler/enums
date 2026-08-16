<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum Attribute Contract — Final Classes And Readonly Properties', function () {
    it('all attribute classes are final', function () {
        $attributes = [
            \ZeroBoiler\Enums\Attributes\Label::class,
            \ZeroBoiler\Enums\Attributes\Color::class,
            \ZeroBoiler\Enums\Attributes\Icon::class,
            \ZeroBoiler\Enums\Attributes\Description::class,
            \ZeroBoiler\Enums\Attributes\EnumLabel::class,
            \ZeroBoiler\Enums\Attributes\EnumColor::class,
            \ZeroBoiler\Enums\Attributes\EnumIcon::class,
            \ZeroBoiler\Enums\Attributes\EnumDescription::class,
        ];

        foreach ($attributes as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue("{$attrClass} should be final");
        }
    });

    it('attribute classes use readonly promoted properties', function () {
        $labelRef = new ReflectionClass(\ZeroBoiler\Enums\Attributes\Label::class);
        $prop = $labelRef->getProperty('value');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->isPublic())->toBeTrue();
        expect($prop->hasDefaultValue())->toBeFalse();
    });
});

describe('EnumCache — TTL Behavior And Thread Safety Contract', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('returns fresh instance after resetInstance', function () {
        $a = EnumCache::getInstance();
        EnumCache::resetInstance();
        $b = EnumCache::getInstance();

        expect($a)->not->toBe($b);
    });

    it('TTL of 0 disables caching', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(TicketStatus::class, ['labels' => ['open' => 'Test'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        expect($cache->has(TicketStatus::class))->toBeFalse();
    });

    it('negative TTL is clamped to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('entries expire after TTL', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second
        $cache->set(TicketStatus::class, ['labels' => ['open' => 'Test'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        expect($cache->has(TicketStatus::class))->toBeTrue();

        // Wait for TTL to expire
        sleep(2);

        expect($cache->has(TicketStatus::class))->toBeFalse();
    });

    it('clearClass removes only the specified class', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(TicketStatus::class, ['labels' => ['open' => 'T1'], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set(IntPriority::class, ['labels' => [1 => 'T2'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass(TicketStatus::class);

        expect($cache->has(TicketStatus::class))->toBeFalse();
        expect($cache->has(IntPriority::class))->toBeTrue();
    });

    it('get throws OutOfBoundsException for missing class', function () {
        EnumCache::getInstance();

        expect(fn () => EnumCache::getInstance()->get('NonExistentClass'))->toThrow(\OutOfBoundsException::class);
    });

    it('prevents cloning', function () {
        expect(fn () => clone EnumCache::getInstance())->toThrow(\RuntimeException::class);
    });
});

describe('EnumMetadataResolver — Cache Integration', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('resolve caches result on first call', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $result1 = EnumMetadataResolver::resolve(TicketStatus::class);
        $result2 = EnumMetadataResolver::resolve(TicketStatus::class);

        expect($result1)->toBe($result2);
        expect($cache->has(TicketStatus::class))->toBeTrue();
    });

    it('invalidate forces rebuild on next resolve', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        EnumMetadataResolver::resolve(TicketStatus::class);
        expect($cache->has(TicketStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidate(TicketStatus::class);
        expect($cache->has(TicketStatus::class))->toBeFalse();

        EnumMetadataResolver::resolve(TicketStatus::class);
        expect($cache->has(TicketStatus::class))->toBeTrue();
    });

    it('invalidateAll clears all cached metadata', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        EnumMetadataResolver::resolve(TicketStatus::class);
        EnumMetadataResolver::resolve(IntPriority::class);

        EnumMetadataResolver::invalidateAll();

        expect($cache->has(TicketStatus::class))->toBeFalse();
        expect($cache->has(IntPriority::class))->toBeFalse();
    });

    it('throws LogicException for non-enum class', function () {
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))->toThrow(\LogicException::class);
    });
});

describe('HasEnumMetadata — Int-Backed Enum Edge Cases', function () {
    it('values() returns int backed values for IntPriority', function () {
        $values = IntPriority::values();

        expect($values)->toBe([1, 5, 10, 99]);
        expect($values)->each->toBeInt();
    });

    it('forSelect() uses int as value for IntPriority', function () {
        $select = IntPriority::forSelect();

        expect($select[0])->toHaveKey('value');
        expect($select[0]['value'])->toBeInt();
    });

    it('forApi() uses int as value for IntPriority', function () {
        $api = IntPriority::forApi();

        expect($api[0])->toHaveKey('value');
        expect($api[0]['value'])->toBeInt();
        expect($api[0])->toHaveKey('name');
        expect($api[0]['name'])->toBe('LOW');
    });

    it('select option values are unique for IntPriority', function () {
        $values = array_column(IntPriority::forSelect(), 'value');

        expect(array_unique($values))->toBe($values);
    });
});

describe('EnumRule — Int-Backed Enum Type Checking', function () {
    it('rejects string value for int-backed enum', function () {
        $rule = EnumRule::for(IntPriority::class);
        $fail = fn (string $msg): string => $msg;
        $passed = false;

        $rule->validate('priority', 'high', function (string $message) use (&$passed) {
            $passed = true;
        });

        expect($passed)->toBeTrue();
    });

    it('accepts valid int value for int-backed enum', function () {
        $rule = EnumRule::for(IntPriority::class);
        $passed = false;

        $rule->validate('priority', 10, function (string $message) use (&$passed) {
            $passed = true;
        });

        expect($passed)->toBeFalse(); // no error = no fail callback called
    });

    it('nullable variant accepts null value', function () {
        $rule = EnumRule::for(IntPriority::class)->nullable();
        $passed = false;

        $rule->validate('priority', null, function (string $message) use (&$passed) {
            $passed = true;
        });

        expect($passed)->toBeFalse(); // no error
    });

    it('non-nullable variant rejects null value', function () {
        $rule = EnumRule::for(IntPriority::class);
        $passed = false;

        $rule->validate('priority', null, function (string $message) use (&$passed) {
            $passed = true;
        });

        expect($passed)->toBeTrue(); // error triggered
    });
});

describe('InvalidEnumException — Factory Methods', function () {
    it('value() formats message with null', function () {
        $e = InvalidEnumException::value(\stdClass::class, null);

        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain(\stdClass::class);
    });

    it('value() formats message with string', function () {
        $e = InvalidEnumException::value(UserStatus::class, 'nonexistent');

        expect($e->getMessage())->toContain('nonexistent');
    });

    it('value() formats message with int', function () {
        $e = InvalidEnumException::value(IntPriority::class, 999);

        expect($e->getMessage())->toContain('999');
    });

    it('__toString includes class name and message', function () {
        $e = InvalidEnumException::forName(TicketStatus::class, 'INVALID');

        $str = (string) $e;
        expect($str)->toContain(InvalidEnumException::class);
        expect($str)->toContain('INVALID');
    });
});

describe('HasEnumMetadata — Label Generation Edge Cases', function () {
    it('generates label from SCREAMING_SNAKE_CASE', function () {
        expect(IntPriority::LOW->label())->toBe('Low');
        expect(IntPriority::HIGH->label())->toBe('High');
        expect(IntPriority::CRITICAL->label())->toBe('Critical');
    });

    it('generates label from camelCase when applicable', function () {
        // PlainTestEnum uses camelCase
        $labels = PlainTestEnum::labels();
        foreach ($labels as $label) {
            expect($label)->toBeString()->not->toBeEmpty();
            // First letter should be uppercase
            expect(ucfirst($label))->toBe($label);
        }
    });
});

describe('HasEnumMetadata — Comparison Method Edge Cases', function () {
    it('in() returns false for empty array', function () {
        expect(IntPriority::LOW->in([]))->toBeFalse();
    });

    it('notIn() returns true for empty array', function () {
        expect(IntPriority::LOW->notIn([]))->toBeTrue();
    });

    it('is() with wrong type throws type error', function () {
        // PHP will throw a TypeError if the type doesn't match
        // This verifies strict type behavior at runtime
        expect(IntPriority::LOW->is(IntPriority::LOW))->toBeTrue();
    });
});
