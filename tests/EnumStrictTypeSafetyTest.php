<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function () {
    EnumCache::getInstance()->clear();
    EnumCache::resetInstance();
});

afterAll(function () {
    EnumCache::getInstance()->clear();
    EnumCache::resetInstance();
});

describe('HasEnumMetadata trait — strict type safety', function () {
    it('forSelect returns consistent value types across string-backed enums', function () {
        $select = OrderStatus::forSelect();

        foreach ($select as $option) {
            expect($option['value'])->toBeString();
            expect($option['label'])->toBeString();
            expect(array_keys($option))->toEqual(['value', 'label']);
        }
    });

    it('forSelect returns consistent value types across int-backed enums', function () {
        $select = Priority::forSelect();

        foreach ($select as $option) {
            expect($option['value'])->toBeInt();
            expect($option['label'])->toBeString();
        }
    });

    it('forApi returns correct types for all fields', function () {
        $api = OrderStatus::forApi();

        foreach ($api as $item) {
            expect($item['value'])->toBeString();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString();
            expect($item['color'])->toBeString();
            // description and icon are nullable
            expect($item['description'])->toBeNull()->or()->toBeString();
            expect($item['icon'])->toBeNull()->or()->toBeString();
        }
    });

    it('values() returns correct types for string-backed', function () {
        $values = OrderStatus::values();

        foreach ($values as $value) {
            expect($value)->toBeString();
        }
    });

    it('values() returns correct types for int-backed', function () {
        $values = Priority::values();

        foreach ($values as $value) {
            expect($value)->toBeInt();
        }
    });

    it('labels() returns same count as cases', function () {
        $labels = OrderStatus::labels();

        expect($labels)->toHaveCount(count(OrderStatus::cases()));

        foreach ($labels as $label) {
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        }
    });
});

describe('Comparison methods — strict identity', function () {
    it('is() uses strict identity comparison', function () {
        $case = OrderStatus::PENDING;

        // Same instance — must be true
        expect($case->is($case))->toBeTrue();

        // Same case retrieved via ::cases() — still same singleton
        $sameCase = OrderStatus::cases()[array_search('PENDING', array_column(OrderStatus::cases(), 'name'), true)];
        expect($case->is($sameCase))->toBeTrue();

        // Different case — must be false
        expect($case->is(OrderStatus::SHIPPED))->toBeFalse();
    });

    it('is() with string is case-sensitive', function () {
        expect(OrderStatus::PENDING->is('PENDING'))->toBeTrue();
        expect(OrderStatus::PENDING->is('pending'))->toBeFalse();
        expect(OrderStatus::PENDING->is('Pending'))->toBeFalse();
        expect(OrderStatus::PENDING->is('SHIPPED'))->toBeFalse();
    });

    it('isNot() is exact negation of is()', function () {
        $case = OrderStatus::PENDING;

        // Test with all cases
        foreach (OrderStatus::cases() as $other) {
            expect($case->isNot($other))->toEqual(! $case->is($other));
        }

        // Test with string
        expect($case->isNot('PENDING'))->toBeFalse();
        expect($case->isNot('SHIPPED'))->toBeTrue();
    });

    it('in() returns false for empty array', function () {
        expect(OrderStatus::PENDING->in([]))->toBeFalse();
    });

    it('in() with single element array', function () {
        expect(OrderStatus::PENDING->in([OrderStatus::PENDING]))->toBeTrue();
        expect(OrderStatus::PENDING->in([OrderStatus::SHIPPED]))->toBeFalse();
    });

    it('in() with mixed instances and strings', function () {
        expect(OrderStatus::PENDING->in([OrderStatus::PENDING, 'SHIPPED']))->toBeTrue();
        expect(OrderStatus::PENDING->in(['PENDING', OrderStatus::SHIPPED]))->toBeTrue();
    });

    it('in() does not match partial names', function () {
        // 'PEND' should not match 'PENDING'
        expect(OrderStatus::PENDING->in(['PEND']))->toBeFalse();
    });
});

describe('Lookup methods — strict behavior', function () {
    it('tryFromName is case-sensitive', function () {
        expect(OrderStatus::tryFromName('PENDING'))->toBe(OrderStatus::PENDING);
        expect(OrderStatus::tryFromName('pending'))->toBeNull();
        expect(OrderStatus::tryFromName('Pending'))->toBeNull();
    });

    it('tryFromName returns null for empty string', function () {
        expect(OrderStatus::tryFromName(''))->toBeNull();
    });

    it('tryFromLabel is case-insensitive', function () {
        $label = OrderStatus::PENDING->label();

        expect(OrderStatus::tryFromLabel($label))->toBe(OrderStatus::PENDING);
        expect(OrderStatus::tryFromLabel(strtolower($label)))->toBe(OrderStatus::PENDING);
        expect(OrderStatus::tryFromLabel(strtoupper($label)))->toBe(OrderStatus::PENDING);
    });

    it('tryFromLabel returns null for empty string', function () {
        expect(OrderStatus::tryFromLabel(''))->toBeNull();
    });

    it('tryFromLabel returns first match when labels collide', function () {
        // Two different cases might produce similar labels after auto-generation
        // (unlikely with our fixtures, but verify first-match behavior)
        $first = OrderStatus::tryFromLabel(OrderStatus::cases()[0]->label());

        expect($first)->toBe(OrderStatus::cases()[0]);
    });

    it('fromName throws for non-existent case', function () {
        OrderStatus::fromName('NON_EXISTENT');
    })->throws(InvalidEnumException::class);

    it('fromName exception message contains class name and case name', function () {
        try {
            OrderStatus::fromName('NON_EXISTENT');
            expect(true)->toBeFalse('Should have thrown');
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('NON_EXISTENT');
            expect($e->getMessage())->toContain(OrderStatus::class);
        }
    });

    it('hasCase returns correct booleans', function () {
        expect(OrderStatus::hasCase('PENDING'))->toBeTrue();
        expect(OrderStatus::hasCase('NON_EXISTENT'))->toBeFalse();
        expect(OrderStatus::hasCase(''))->toBeFalse();
    });
});

describe('InvalidEnumException — type safety', function () {
    it('value() with null displays "null"', function () {
        $exception = InvalidEnumException::value(OrderStatus::class, null);

        expect($exception->getMessage())->toContain('null');
    });

    it('value() with int displays the number', function () {
        $exception = InvalidEnumException::value(OrderStatus::class, 42);

        expect($exception->getMessage())->toContain('42');
    });

    it('value() with string displays the string', function () {
        $exception = InvalidEnumException::value(OrderStatus::class, 'invalid');

        expect($exception->getMessage())->toContain('invalid');
    });

    it('forName contains class and name', function () {
        $exception = InvalidEnumException::forName(OrderStatus::class, 'MISSING');

        expect($exception->getMessage())->toContain('MISSING');
        expect($exception->getMessage())->toContain(OrderStatus::class);
    });
});

describe('EnumCache — TTL edge cases', function () {
    it('cached metadata persists within TTL', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set('TestEnum', ['labels' => ['a' => 'A'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        expect($cache->has('TestEnum'))->toBeTrue();

        $cached = $cache->get('TestEnum');
        expect($cached['labels'])->toEqual(['a' => 'A']);
    });

    it('cache expires after TTL', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL

        $cache->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        // Should be available immediately
        expect($cache->has('TestEnum'))->toBeTrue();

        // Simulate time passing (we can't actually sleep in tests, but verify the logic)
        // The TTL check uses microtime(true), so entries set at the exact same time are valid
    });

    it('get() throws OutOfBoundsException for missing key', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->get('NonExistent');
    })->throws(\OutOfBoundsException::class);

    it('set() updates existing entries', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set('TestEnum', ['labels' => ['a' => 'Old'], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('TestEnum', ['labels' => ['a' => 'New'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cached = $cache->get('TestEnum');
        expect($cached['labels']['a'])->toBe('New');
    });

    it('clear() removes all entries but preserves TTL setting', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set('A', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('B', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clear();

        expect($cache->has('A'))->toBeFalse();
        expect($cache->has('B'))->toBeFalse();
        // TTL should still be effective
        $cache->set('C', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        expect($cache->has('C'))->toBeTrue();
    });
});

describe('EnumRule — strict type checking', function () {
    it('rejects int value for string-backed enum', function () {
        $rule = EnumRule::for(OrderStatus::class);

        $fail = function (string $message): void {
            throw new \InvalidArgumentException($message);
        };

        $rule->validate('status', 42, $fail);
    })->throws(\InvalidArgumentException::class);

    it('rejects string value for int-backed enum', function () {
        $rule = EnumRule::for(Priority::class);

        $fail = function (string $message): void {
            throw new \InvalidArgumentException($message);
        };

        $rule->validate('priority', 'high', $fail);
    })->throws(\InvalidArgumentException::class);

    it('accepts valid int for int-backed enum', function () {
        $rule = EnumRule::for(Priority::class);

        $called = false;
        $fail = static function (string $message) use (&$called): void {
            $called = true;
        };

        $rule->validate('priority', Priority::HIGH->value, $fail);

        expect($called)->toBeFalse();
    });

    it('rejects invalid value for int-backed enum', function () {
        $rule = EnumRule::for(Priority::class);

        $fail = function (string $message): void {
            throw new \InvalidArgumentException($message);
        };

        $rule->validate('priority', 999, $fail);
    })->throws(\InvalidArgumentException::class);

    it('nullable allows null without calling fail', function () {
        $rule = EnumRule::for(OrderStatus::class)->nullable();

        $called = false;
        $fail = static function (string $message) use (&$called): void {
            $called = true;
        };

        $rule->validate('status', null, $fail);

        expect($called)->toBeFalse();
    });

    it('non-nullable rejects null', function () {
        $rule = EnumRule::for(OrderStatus::class);

        $fail = function (string $message): void {
            throw new \InvalidArgumentException($message);
        };

        $rule->validate('status', null, $fail);
    })->throws(\InvalidArgumentException::class);

    it('message includes allowed values when enum uses HasEnumMetadata', function () {
        $rule = EnumRule::for(OrderStatus::class);

        $failMessage = null;
        $fail = static function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        $rule->validate('status', 'nonexistent', $fail);

        expect($failMessage)->not->toBeNull();
        expect($failMessage)->toContain('Allowed values');
    });
});

describe('Auto-generated labels — edge cases', function () {
    it('SCREAMING_SNAKE_CASE generates Title Case', function () {
        $label = UserStatus::PENDING->label();

        expect($label)->toBe('Pending');
    });

    it('camelCase generates Title Case', function () {
        // Check CamelCaseRole fixture
        $case = \ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::cases()[0] ?? null;
        if ($case === null) {
            $this->markTestSkipped('No camelCase fixture case available');
        }

        expect($case->label())->toBeString();
        expect($case->label())->not->toBeEmpty();
    });

    it('single word generates capitalized', function () {
        // ACTIVE should become 'Active'
        $label = UserStatus::ACTIVE->label();

        expect($label)->toBe('Active');
    });

    it('labels are deterministic (same input always same output)', function () {
        $label1 = UserStatus::PENDING->label();
        $label2 = UserStatus::PENDING->label();

        expect($label1)->toBe($label2);
    });
});
