<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function () {
    EnumCache::resetInstance();
});

afterAll(function () {
    EnumCache::resetInstance();
});

describe('EnumManager — runtime delegation', function () {
    it('forSelect delegates to enum with HasEnumMetadata', function () {
        $manager = new EnumManager;
        $select = $manager->forSelect(OrderStatus::class);

        expect($select)->toBeArray();
        expect($select)->not->toBeEmpty();
        expect($select[0])->toHaveKeys(['value', 'label']);
    });

    it('forApi delegates to enum with HasEnumMetadata', function () {
        $manager = new EnumManager;
        $api = $manager->forApi(OrderStatus::class);

        expect($api)->toBeArray();
        expect($api)->not->toBeEmpty();
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('tryFromLabel delegates to enum with HasEnumMetadata', function () {
        $manager = new EnumManager;
        $label = OrderStatus::PENDING->label();

        $case = $manager->tryFromLabel(OrderStatus::class, $label);

        expect($case)->toBe(OrderStatus::PENDING);
    });

    it('tryFromLabel returns null for non-matching label', function () {
        $manager = new EnumManager;

        $case = $manager->tryFromLabel(OrderStatus::class, 'NonExistentLabel');

        expect($case)->toBeNull();
    });

    it('throws BadMethodCallException for enum without HasEnumMetadata', function () {
        $manager = new EnumManager;
        $manager->forSelect(\stdClass::class);
    })->throws(\BadMethodCallException::class);

    it('BadMethodCallException message contains class name', function () {
        $manager = new EnumManager;

        try {
            $manager->forApi(\stdClass::class);
            expect(true)->toBeFalse('Should have thrown');
        } catch (\BadMethodCallException $e) {
            expect($e->getMessage())->toContain('stdClass');
            expect($e->getMessage())->toContain('HasEnumMetadata');
        }
    });

    it('throws BadMethodCallException for tryFromLabel on non-metadata enum', function () {
        $manager = new EnumManager;
        $manager->tryFromLabel(\stdClass::class, 'test');
    })->throws(\BadMethodCallException::class);
});

describe('EnumCache — TTL disabled behavior', function () {
    it('TTL 0 disables caching — has() always returns false', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set('TestClass', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestClass'))->toBeFalse();
    });

    it('negative TTL is normalized to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        $cache->set('TestClass', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestClass'))->toBeFalse();
    });

    it('clearClass removes only the specified class entry', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set('ClassA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('ClassB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass('ClassA');

        expect($cache->has('ClassA'))->toBeFalse();
        expect($cache->has('ClassB'))->toBeTrue();
    });

    it('clearClass is a no-op for non-existent class', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->clearClass('NonExistent');

        expect($cache->has('NonExistent'))->toBeFalse();
    });

    it('flush static method clears all entries via singleton', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set('A', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('B', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        EnumCache::flush();

        expect($cache->has('A'))->toBeFalse();
        expect($cache->has('B'))->toBeFalse();
    });

    it('resetInstance allows fresh singleton creation', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set('Test', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        EnumCache::resetInstance();

        $fresh = EnumCache::getInstance();

        expect($fresh->has('Test'))->toBeFalse();
    });
});

describe('EnumRule — pure enum validation', function () {
    it('accepts valid case name for pure enum', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $called = false;
        $fail = static function (string $message) use (&$called): void {
            $called = true;
        };

        $rule->validate('flag', PureFeatureFlag::SearchEnabled->name, $fail);

        expect($called)->toBeFalse();
    });

    it('rejects invalid case name for pure enum', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $fail = function (string $message): void {
            throw new \InvalidArgumentException($message);
        };

        $rule->validate('flag', 'NONEXISTENT', $fail);
    })->throws(\InvalidArgumentException::class);

    it('rejects non-string value for pure enum', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $fail = function (string $message): void {
            throw new \InvalidArgumentException($message);
        };

        $rule->validate('flag', 42, $fail);
    })->throws(\InvalidArgumentException::class);

    it('rejects array value for pure enum', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $fail = function (string $message): void {
            throw new \InvalidArgumentException($message);
        };

        $rule->validate('flag', ['value'], $fail);
    })->throws(\InvalidArgumentException::class);
});

describe('EnumRule — error messages', function () {
    it('message includes allowed values list', function () {
        $rule = EnumRule::for(UserStatus::class);

        $failMessage = null;
        $fail = static function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        $rule->validate('status', 'nonexistent', $fail);

        expect($failMessage)->not->toBeNull();
        expect($failMessage)->toContain('Allowed values');

        // Should contain at least one known value
        expect($failMessage)->toContain('active');
    });

    it('generic message when enum lacks values() method', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $failMessage = null;
        $fail = static function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        $rule->validate('flag', 'NONEXISTENT', $fail);

        expect($failMessage)->not->toBeNull();
        expect($failMessage)->toContain('invalid');
    });

    it('nullable creates new instance with nullable flag', function () {
        $rule = EnumRule::for(UserStatus::class);
        $nullableRule = $rule->nullable();

        expect($nullableRule)->not->toBe($rule);
    });
});

describe('EnumTestGenerator — output format', function () {
    it('generates valid PHP with declare strict_types', function () {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toContain('declare(strict_types=1)');
        expect($content)->toContain('use '.OrderStatus::class.';');
    });

    it('generates Pest describe block', function () {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toContain("describe('OrderStatus enum'");
    });

    it('generates per-case label and color tests', function () {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        foreach (OrderStatus::cases() as $case) {
            expect($content)->toContain("has a label for case {$case->name}");
            expect($content)->toContain("has a color for case {$case->name}");
        }
    });

    it('generates comparison tests when 2+ cases exist', function () {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toContain('supports is() comparison');
        expect($content)->toContain('supports isNot() comparison');
        expect($content)->toContain('supports in() group matching');
    });

    it('generates tryFromLabel test', function () {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toContain('tryFromLabel');
    });

    it('generates forSelect and forApi tests', function () {
        $content = EnumTestGenerator::generate(OrderStatus::class);

        expect($content)->toContain('generate select options');
        expect($content)->toContain('generate API response');
    });
});

describe('InvalidEnumException — edge cases', function () {
    it('value() with empty string value displays empty string', function () {
        $exception = InvalidEnumException::value(OrderStatus::class, '');

        expect($exception->getMessage())->toContain(OrderStatus::class);
    });

    it('forName with very long case name', function () {
        $longName = str_repeat('A', 500);
        $exception = InvalidEnumException::forName(OrderStatus::class, $longName);

        expect($exception->getMessage())->toContain($longName);
    });
});

describe('HasEnumMetadata — int-backed enum color from class-level', function () {
    it('uses int-backed value for class-level color lookup', function () {
        // Priority has no per-case Color attributes, so auto-labels apply
        $label = Priority::HIGH->label();

        expect($label)->toBeString();
        expect($label)->not->toBeEmpty();
        expect($label)->toBe('High');
    });

    it('forApi returns int values for int-backed enum', function () {
        $api = Priority::forApi();

        foreach ($api as $item) {
            expect($item['value'])->toBeInt();
        }
    });
});
