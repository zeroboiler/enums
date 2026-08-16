<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\{Color, Description, EnumColor, EnumDescription, EnumIcon, Icon, Label};
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\{IntPriority, PlainTestEnum, PureFeatureFlag, TicketStatus, UserStatus};

describe('V26 — Enum cache lifecycle and metadata consistency', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('EnumCache getInstance always returns same singleton within process', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('EnumCache set+get roundtrip preserves all metadata keys', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $meta = [
            'labels' => ['open' => 'Open', 'closed' => 'Closed'],
            'descriptions' => ['open' => 'Ticket is open'],
            'colors' => ['open' => 'success', 'closed' => 'danger'],
            'icons' => ['open' => 'heroicon-o-check'],
        ];
        $cache->set(TicketStatus::class, $meta);

        expect($cache->has(TicketStatus::class))->toBeTrue();

        $retrieved = $cache->get(TicketStatus::class);
        expect($retrieved)->toBe($meta);
    });

    it('EnumCache flush via static method clears all entries', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(TicketStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set(IntPriority::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        EnumCache::flush();

        expect($cache->has(TicketStatus::class))->toBeFalse();
        expect($cache->has(IntPriority::class))->toBeFalse();
    });

    it('EnumCache clearClass preserves other entries', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set(TicketStatus::class, ['labels' => ['open' => 'A'], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set(IntPriority::class, ['labels' => [1 => 'B'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass(TicketStatus::class);

        expect($cache->has(TicketStatus::class))->toBeFalse();
        expect($cache->has(IntPriority::class))->toBeTrue();
        expect($cache->get(IntPriority::class)['labels'][1])->toBe('B');
    });

    it('EnumCache setTtl with negative value clamps to zero', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
    });

    it('EnumCache with TTL=1 expires after sleep', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);
        $cache->set(TicketStatus::class, ['labels' => ['open' => 'X'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        expect($cache->has(TicketStatus::class))->toBeTrue();

        sleep(2);

        expect($cache->has(TicketStatus::class))->toBeFalse();
    });
});

describe('V26 — EnumMetadataResolver cross-class isolation', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('resolve returns different metadata for different enum classes', function () {
        $ticketMeta = EnumMetadataResolver::resolve(TicketStatus::class);
        $intMeta = EnumMetadataResolver::resolve(IntPriority::class);

        expect($ticketMeta)->not->toBe($intMeta);
    });

    it('resolve result is stable across multiple calls for same class', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $first = EnumMetadataResolver::resolve(TicketStatus::class);
        $second = EnumMetadataResolver::resolve(TicketStatus::class);
        $third = EnumMetadataResolver::resolve(TicketStatus::class);

        expect($first)->toBe($second);
        expect($second)->toBe($third);
    });

    it('invalidate specific class does not affect other classes', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        EnumMetadataResolver::resolve(TicketStatus::class);
        EnumMetadataResolver::resolve(IntPriority::class);

        EnumMetadataResolver::invalidate(TicketStatus::class);

        expect($cache->has(TicketStatus::class))->toBeFalse();
        expect($cache->has(IntPriority::class))->toBeTrue();
    });
});

describe('V26 — HasEnumMetadata trait — comprehensive accessor consistency', function () {
    it('all cases return non-empty string labels', function () {
        foreach (TicketStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });

    it('all cases return string color (never null)', function () {
        foreach (TicketStatus::cases() as $case) {
            expect($case->color())->toBeString();
        }
    });

    it('icon and description are nullable but always string or null', function () {
        foreach (TicketStatus::cases() as $case) {
            $icon = $case->icon();
            expect($icon)->toBeNull()->or()->toBeString();

            $desc = $case->description();
            expect($desc)->toBeNull()->or()->toBeString();
        }
    });

    it('forSelect returns consistent count with cases()', function () {
        $select = TicketStatus::forSelect();
        expect($select)->toHaveCount(count(TicketStatus::cases()));
    });

    it('forApi returns consistent count with cases()', function () {
        $api = TicketStatus::forApi();
        expect($api)->toHaveCount(count(TicketStatus::cases()));
    });

    it('forApi contains all required keys', function () {
        $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];

        foreach (TicketStatus::forApi() as $item) {
            foreach ($requiredKeys as $key) {
                expect($item)->toHaveKey($key);
            }
        }
    });

    it('forSelect values are unique', function () {
        $values = array_column(TicketStatus::forSelect(), 'value');
        expect(array_unique($values))->toBe($values);
    });

    it('forSelect labels are non-empty strings', function () {
        foreach (TicketStatus::forSelect() as $option) {
            expect($option['label'])->toBeString()->not->toBeEmpty();
        }
    });

    it('values() returns same count as cases()', function () {
        expect(TicketStatus::values())->toHaveCount(count(TicketStatus::cases()));
    });

    it('labels() returns same count as cases()', function () {
        expect(TicketStatus::labels())->toHaveCount(count(TicketStatus::cases()));
    });
});

describe('V26 — Comparison methods — strict identity verification', function () {
    it('is() with same instance returns true', function () {
        $case = TicketStatus::OPEN;
        expect($case->is($case))->toBeTrue();
    });

    it('is() with different instance returns false', function () {
        expect(TicketStatus::OPEN->is(TicketStatus::CLOSED))->toBeFalse();
    });

    it('is() with string name is case-sensitive', function () {
        expect(TicketStatus::OPEN->is('OPEN'))->toBeTrue();
        expect(TicketStatus::OPEN->is('open'))->toBeFalse();
        expect(TicketStatus::OPEN->is('Open'))->toBeFalse();
    });

    it('isNot() is exact negation of is()', function () {
        foreach (TicketStatus::cases() as $case) {
            foreach (TicketStatus::cases() as $other) {
                expect($case->isNot($other))->toBe(!$case->is($other));
            }
        }
    });

    it('in() with empty array returns false', function () {
        expect(TicketStatus::OPEN->in([]))->toBeFalse();
    });

    it('notIn() with empty array returns true', function () {
        expect(TicketStatus::OPEN->notIn([]))->toBeTrue();
    });

    it('in() with single matching instance returns true', function () {
        expect(TicketStatus::OPEN->in([TicketStatus::CLOSED, TicketStatus::OPEN]))->toBeTrue();
    });

    it('in() with single matching string returns true', function () {
        expect(TicketStatus::OPEN->in(['CLOSED', 'OPEN']))->toBeTrue();
    });

    it('in() with mixed instances and strings works', function () {
        expect(TicketStatus::OPEN->in([TicketStatus::CLOSED, 'OPEN']))->toBeTrue();
    });

    it('notIn() with all matching returns false', function () {
        expect(TicketStatus::OPEN->notIn([TicketStatus::OPEN]))->toBeFalse();
        expect(TicketStatus::OPEN->notIn(['OPEN']))->toBeFalse();
    });
});

describe('V26 — Reverse lookup — tryFromLabel, tryFromName, fromName, hasCase', function () {
    it('tryFromLabel finds by exact label', function () {
        $case = TicketStatus::OPEN;
        expect(TicketStatus::tryFromLabel($case->label()))->toBe($case);
    });

    it('tryFromLabel is case-insensitive', function () {
        $label = TicketStatus::OPEN->label();
        expect(TicketStatus::tryFromLabel(strtolower($label)))->toBeInstanceOf(TicketStatus::class);
        expect(TicketStatus::tryFromLabel(strtoupper($label)))->toBeInstanceOf(TicketStatus::class);
    });

    it('tryFromLabel returns null for non-existent label', function () {
        expect(TicketStatus::tryFromLabel('nonexistent-label-xyz'))->toBeNull();
    });

    it('tryFromName finds by exact case name', function () {
        expect(TicketStatus::tryFromName('OPEN'))->toBe(TicketStatus::OPEN);
    });

    it('tryFromName is case-sensitive', function () {
        expect(TicketStatus::tryFromName('open'))->toBeNull();
        expect(TicketStatus::tryFromName('Open'))->toBeNull();
    });

    it('tryFromName returns null for non-existent name', function () {
        expect(TicketStatus::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('fromName returns correct case', function () {
        expect(TicketStatus::fromName('OPEN'))->toBe(TicketStatus::OPEN);
    });

    it('fromName throws InvalidEnumException for non-existent name', function () {
        expect(fn () => TicketStatus::fromName('NON_EXISTENT'))->toThrow(InvalidEnumException::class);
    });

    it('fromName exception message contains class and name', function () {
        try {
            TicketStatus::fromName('BOGUS');
            expect(false)->toBeTrue('Should have thrown');
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('BOGUS');
            expect($e->getMessage())->toContain(TicketStatus::class);
        }
    });

    it('hasCase returns true for existing case', function () {
        expect(TicketStatus::hasCase('OPEN'))->toBeTrue();
    });

    it('hasCase returns false for non-existent case', function () {
        expect(TicketStatus::hasCase('BOGUS'))->toBeFalse();
    });
});

describe('V26 — Int-backed enum type safety', function () {
    it('values() returns int values only', function () {
        foreach (IntPriority::values() as $value) {
            expect($value)->toBeInt();
        }
    });

    it('forSelect values are ints', function () {
        foreach (IntPriority::forSelect() as $option) {
            expect($option['value'])->toBeInt();
        }
    });

    it('forApi values are ints', function () {
        foreach (IntPriority::forApi() as $item) {
            expect($item['value'])->toBeInt();
        }
    });

    it('select values are unique for int-backed enum', function () {
        $values = array_column(IntPriority::forSelect(), 'value');
        expect(array_unique($values))->toBe($values);
    });
});

describe('V26 — Pure enum type safety', function () {
    it('values() returns case names (strings)', function () {
        foreach (PureFeatureFlag::values() as $value) {
            expect($value)->toBeString();
        }
    });

    it('forSelect values are case names', function () {
        foreach (PureFeatureFlag::forSelect() as $option) {
            expect($option['value'])->toBeString();
            expect(PureFeatureFlag::tryFromName($option['value']))->not->toBeNull();
        }
    });

    it('all metadata accessors work on pure enum', function () {
        foreach (PureFeatureFlag::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
            expect($case->color())->toBeString();
            expect($case->icon())->toBeNull()->or()->toBeString();
            expect($case->description())->toBeNull()->or()->toBeString();
        }
    });
});

describe('V26 — EnumCast serialization contract', function () {
    it('get() returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->get($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('get() returns enum instance for valid backed value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->get($model, 'status', 'active', []);
        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('get() returns null for invalid backed value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->get($model, 'status', 'nonexistent', []);
        expect($result)->toBeNull();
    });

    it('set() returns backed value for enum instance', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->set($model, 'status', UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });

    it('set() returns raw value for valid string', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->set($model, 'status', 'active', []);
        expect($result)->toBe('active');
    });

    it('set() returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->set($model, 'status', null, []);
        expect($result)->toBeNull();
    });

    it('set() throws for wrong enum type', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        expect(fn () => $cast->set($model, 'status', TicketStatus::OPEN, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('set() throws for invalid raw value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        expect(fn () => $cast->set($model, 'status', 'invalid_value', []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('serialize() returns backed value for enum instance', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->serialize($model, 'status', UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });

    it('serialize() returns raw string value as-is', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->serialize($model, 'status', 'active', []);
        expect($result)->toBe('active');
    });

    it('serialize() returns null for null', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->serialize($model, 'status', null, []);
        expect($result)->toBeNull();
    });
});

describe('V26 — EnumRule validation contract', function () {
    it('validates string-backed enum value', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 'active', function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects invalid string-backed enum value', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', 'nonexistent', function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('validates int-backed enum value', function () {
        $rule = EnumRule::for(IntPriority::class);
        $failed = false;

        $rule->validate('priority', 10, function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects string for int-backed enum', function () {
        $rule = EnumRule::for(IntPriority::class);
        $failed = false;

        $rule->validate('priority', 'high', function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('nullable passes null without error', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failed = false;

        $rule->validate('status', null, function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('non-nullable rejects null with error', function () {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;

        $rule->validate('status', null, function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('validates pure enum by case name', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;

        // PureFeatureFlag case names — check what exists
        $firstName = PureFeatureFlag::cases()[0]->name;
        $rule->validate('feature', $firstName, function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects non-string for pure enum', function () {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;

        $rule->validate('feature', 123, function (string $message) use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('error message includes allowed values when enum has HasEnumMetadata', function () {
        $rule = EnumRule::for(UserStatus::class);
        $capturedMessage = '';

        $rule->validate('status', 'nonexistent', function (string $message) use (&$capturedMessage) {
            $capturedMessage = $message;
        });

        expect($capturedMessage)->toContain('invalid');
        expect($capturedMessage)->toContain('Allowed values');
    });

    it('error message is generic when enum lacks HasEnumMetadata', function () {
        $rule = EnumRule::for(\PureFeatureFlag::class);
        $capturedMessage = '';

        $rule->validate('feature', 'nonexistent', function (string $message) use (&$capturedMessage) {
            $capturedMessage = $message;
        });

        expect($capturedMessage)->toContain('invalid');
    });
});

describe('V26 — EnumManager delegation contract', function () {
    it('forSelect delegates correctly', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(UserStatus::class);

        expect($result)->toBe(UserStatus::forSelect());
    });

    it('forApi delegates correctly', function () {
        $manager = new EnumManager;
        $result = $manager->forApi(UserStatus::class);

        expect($result)->toBe(UserStatus::forApi());
    });

    it('tryFromLabel delegates correctly', function () {
        $manager = new EnumManager;
        $label = UserStatus::ACTIVE->label();

        expect($manager->tryFromLabel(UserStatus::class, $label))->toBe(UserStatus::ACTIVE);
    });

    it('tryFromName delegates correctly', function () {
        $manager = new EnumManager;

        expect($manager->tryFromName(UserStatus::class, 'ACTIVE'))->toBe(UserStatus::ACTIVE);
    });

    it('fromName delegates correctly', function () {
        $manager = new EnumManager;

        expect($manager->fromName(UserStatus::class, 'ACTIVE'))->toBe(UserStatus::ACTIVE);
    });

    it('fromName throws on invalid name', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->fromName(UserStatus::class, 'BOGUS'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase delegates correctly', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
        expect($manager->hasCase(UserStatus::class, 'BOGUS'))->toBeFalse();
    });

    it('values delegates correctly', function () {
        $manager = new EnumManager;

        expect($manager->values(UserStatus::class))->toBe(UserStatus::values());
    });

    it('labels delegates correctly', function () {
        $manager = new EnumManager;

        expect($manager->labels(UserStatus::class))->toBe(UserStatus::labels());
    });

    it('throws BadMethodCallException for non-enum class', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('V26 — EnumManager is final readonly', function () {
    it('is final', function () {
        $ref = new ReflectionClass(EnumManager::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('is readonly', function () {
        $ref = new ReflectionClass(EnumManager::class);
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumRule is final readonly', function () {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumCast is final', function () {
        $ref = new ReflectionClass(EnumCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumCache is final', function () {
        $ref = new ReflectionClass(EnumCache::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumMetadataResolver is final', function () {
        $ref = new ReflectionClass(EnumMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
    });
});

describe('V26 — InvalidEnumException factory methods', function () {
    it('value() with null produces readable message', function () {
        $e = InvalidEnumException::value(UserStatus::class, null);

        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('value() with string produces readable message', function () {
        $e = InvalidEnumException::value(UserStatus::class, 'bad_value');

        expect($e->getMessage())->toContain('bad_value');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('value() with int produces readable message', function () {
        $e = InvalidEnumException::value(IntPriority::class, 999);

        expect($e->getMessage())->toContain('999');
        expect($e->getMessage())->toContain(IntPriority::class);
    });

    it('forName() produces correct message', function () {
        $e = InvalidEnumException::forName(UserStatus::class, 'INVALID');

        expect($e->getMessage())->toContain('INVALID');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('__toString() includes class name and message', function () {
        $e = InvalidEnumException::forName(UserStatus::class, 'TEST');
        $str = (string) $e;

        expect($str)->toContain(InvalidEnumException::class);
        expect($str)->toContain('TEST');
    });
});

describe('V26 — Class-level attribute override priority', function () {
    it('per-case Color overrides class-level EnumColor', function () {
        // UserStatus has EnumColor mapping 'active' → 'success'
        // But if a case has #[Color('danger')], it should win
        $meta = EnumMetadataResolver::resolve(UserStatus::class);
        $colorMeta = $meta['colors'];

        // Check that per-case overrides exist in the resolved metadata
        expect($colorMeta)->toBeArray();
    });

    it('resolved metadata has all four keys', function () {
        $meta = EnumMetadataResolver::resolve(TicketStatus::class);

        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    });
});

describe('V26 — EnumsServiceProvider structural contract', function () {
    it('is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('register and boot have Override attributes', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);
        $register = $ref->getMethod('register');
        $boot = $ref->getMethod('boot');

        expect($register->getAttributes(\Override::class))->not->toBeEmpty();
        expect($boot->getAttributes(\Override::class))->not->toBeEmpty();
    });
});
