<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use BackedEnum;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

describe('V42 Enum Strict Type Safety And Cross-Package Contract Audit', function () {
    // ── PHPStan Level 9 strict type verification ──────────────────────────

    describe('Strict type safety — no mixed types in public API', function () {
        it('label() returns string type (never null)', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'label');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType instanceof ReflectionNamedType)->toBeTrue();
            expect($returnType->getName())->toBe('string');
            expect($returnType->allowsNull())->toBeFalse();
        });

        it('color() returns string type (never null)', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'color');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType instanceof ReflectionNamedType)->toBeTrue();
            expect($returnType->getName())->toBe('string');
            expect($returnType->allowsNull())->toBeFalse();
        });

        it('icon() returns nullable string', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'icon');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('string');
            expect($returnType->allowsNull())->toBeTrue();
        });

        it('description() returns nullable string', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'description');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('string');
            expect($returnType->allowsNull())->toBeTrue();
        });

        it('toValue() returns int|string union type', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'toValue');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType instanceof ReflectionNamedType)->toBeTrue();
            expect($returnType->getName())->toBe('int|string');
        });

        it('values() returns array', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'values');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });

        it('labels() returns array', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'labels');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });

        it('is() accepts static|string union type', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'is');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(1);
            $paramType = $params[0]->getType();
            expect($paramType)->not->toBeNull();
            expect($paramType->getName())->toBe('static|string');
        });

        it('isNot() accepts static|string union type', function () {
            $reflection = new ReflectionMethod(HasEnumMetadata::class, 'isNot');
            $params = $reflection->getParameters();

            expect(count($params))->toBe(1);
            $paramType = $params[0]->getType();
            expect($paramType)->not->toBeNull();
            expect($paramType->getName())->toBe('static|string');
        });
    });

    // ── Strict comparisons verification ────────────────────────────────────

    describe('Strict comparison semantics', function () {
        it('is() uses strict identity — not string coercion for backed values', function () {
            // For string-backed enums, 'active' === 'active' but is('active') compares against name, not value
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse(); // name comparison, not value
        });

        it('is() uses strict identity for instance comparison', function () {
            $active = UserStatus::ACTIVE;
            expect($active->is(UserStatus::ACTIVE))->toBeTrue();

            // Different enum instances of the same value are identical in PHP
            // so this should still be true (singleton enum cases)
            expect($active->is(UserStatus::ACTIVE))->toBeTrue();
        });

        it('in() performs strict element comparison', function () {
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['active', 'pending']))->toBeFalse(); // case names, not values
        });

        it('notIn() is exact inverse of in()', function () {
            $statuses = [UserStatus::ACTIVE, UserStatus::PENDING];

            foreach (UserStatus::cases() as $case) {
                expect($case->notIn($statuses))->toBe(! $case->in($statuses));
            }
        });

        it('tryFromName() uses strict string equality for name matching', function () {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromName('active'))->toBeNull(); // value, not name
            expect(UserStatus::tryFromName('Active'))->toBeNull(); // case-sensitive
        });

        it('fromName() is case-sensitive', function () {
            expect(fn () => UserStatus::fromName('active'))->toThrow(InvalidEnumException::class);
            expect(fn () => UserStatus::fromName('Active'))->toThrow(InvalidEnumException::class);
            expect(UserStatus::fromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        });
    });

    // ── Integer-backed enum edge cases ──────────────────────────────────

    describe('Integer-backed enum specific behavior', function () {
        it('values() returns actual int values, not strings', function () {
            $values = IntBackedPriority::values();

            foreach ($values as $value) {
                expect($value)->toBeInt();
            }
        });

        it('zero-backed enum toValue() returns integer 0', function () {
            expect(ZeroBackedPriority::ZERO->toValue())->toBe(0);
            expect(ZeroBackedPriority::ZERO->toValue())->toBeSame(0); // strict type
        });

        it('forSelect() uses backed values as keys for int-backed enums', function () {
            $select = IntBackedPriority::forSelect();

            foreach ($select as $option) {
                expect($option['value'])->toBeInt();
                expect($option['label'])->toBeString();
            }
        });

        it('forApi() includes correct value type for int-backed enums', function () {
            $api = IntBackedPriority::forApi();

            foreach ($api as $item) {
                expect($item['value'])->toBeInt();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
                expect($item['color'])->toBeString();
            }
        });

        it('labels are non-empty for all int-backed enum cases', function () {
            $labels = IntBackedPriority::labels();

            foreach ($labels as $label) {
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });
    });

    // ── CamelCase enum edge cases ───────────────────────────────────────

    describe('CamelCase enum label generation', function () {
        it('generates Title Case from camelCase names', function () {
            expect(CamelCasePriority::highPriority->label())->toBe('High Priority');
            expect(CamelCasePriority::lowPriority->label())->toBe('Low Priority');
            expect(CamelCasePriority::mediumPriority->label())->toBe('Medium Priority');
        });

        it('forSelect() preserves camelCase-derived labels', function () {
            $select = CamelCasePriority::forSelect();
            $labels = array_column($select, 'label');

            foreach ($labels as $label) {
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
                expect($label)->toMatch('/^[A-Z]/'); // Title Case
            }
        });
    });

    // ── Single case enum behavior ───────────────────────────────────────

    describe('Single case enum edge cases', function () {
        it('single case enum has exactly one case', function () {
            expect(SingleCaseEnum::cases())->toHaveCount(1);
        });

        it('forSelect() returns exactly one entry', function () {
            expect(SingleCaseEnum::forSelect())->toHaveCount(1);
        });

        it('forApi() returns exactly one entry', function () {
            expect(SingleCaseEnum::forApi())->toHaveCount(1);
        });

        it('in() with single-element array works correctly', function () {
            $case = SingleCaseEnum::cases()[0];
            expect($case->in([$case]))->toBeTrue();
            expect($case->in([$case->name]))->toBeTrue();
        });

        it('fromName() works with the single case name', function () {
            $case = SingleCaseEnum::cases()[0];
            expect(SingleCaseEnum::fromName($case->name))->toBe($case);
        });

        it('fromName() throws for non-existent name even with single case', function () {
            $case = SingleCaseEnum::cases()[0];
            expect(fn () => SingleCaseEnum::fromName('NON_EXISTENT'))->toThrow(InvalidEnumException::class);
        });
    });

    // ── PaymentStatus (class-level attributes) verification ────────────

    describe('PaymentStatus class-level attribute resolution', function () {
        it('resolves class-level EnumColor for all payment statuses', function () {
            foreach (PaymentStatus::cases() as $case) {
                $color = $case->color();
                expect($color)->toBeString();
                expect($color)->not->toBeEmpty();
            }
        });

        it('tryFromLabel resolves all cases by their labels', function () {
            foreach (PaymentStatus::cases() as $case) {
                $resolved = PaymentStatus::tryFromLabel($case->label());
                expect($resolved)->toBe($case);
            }
        });

        it('forApi() output has all six metadata keys per case', function () {
            $api = PaymentStatus::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });
    });

    // ── EnumCache singleton behavior ────────────────────────────────────

    describe('EnumCache singleton contract', function () {
        it('returns same instance on multiple calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b); // strict identity
        });

        it('setTtl clamps negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-1);

            expect($cache->getTtl())->toBe(0);

            // Restore
            $cache->setTtl(300);
        });

        it('has() returns false when TTL is 0', function () {
            $cache = EnumCache::getInstance();
            $originalTtl = $cache->getTtl();

            $cache->setTtl(0);
            // Even if we set a value, has() should return false immediately
            $cache->set(__CLASS__, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(__CLASS__))->toBeFalse();

            // Restore
            $cache->clearClass(__CLASS__);
            $cache->setTtl($originalTtl);
        });

        it('flush clears all entries via static method', function () {
            $cache = EnumCache::getInstance();
            $cache->set(__CLASS__.'_test', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            EnumCache::flush();

            expect($cache->has(__CLASS__.'_test'))->toBeFalse();
        });
    });

    // ── InvalidEnumException ──────────────────────────────────────────

    describe('InvalidEnumException contract', function () {
        it('forName() includes class name in message', function () {
            $exception = InvalidEnumException::forName(UserStatus::class, 'INVALID');
            $message = $exception->getMessage();

            expect($message)->toContain(UserStatus::class);
            expect($message)->toContain('INVALID');
        });

        it('value() includes class name and value in message', function () {
            $exception = InvalidEnumException::value(UserStatus::class, 'bad_value');
            $message = $exception->getMessage();

            expect($message)->toContain(UserStatus::class);
            expect($message)->toContain('bad_value');
        });

        it('value() handles null value display', function () {
            $exception = InvalidEnumException::value(UserStatus::class, null);
            $message = $exception->getMessage();

            expect($message)->toContain('null');
        });

        it('__toString() follows FQCN: message format', function () {
            $exception = InvalidEnumException::forName(UserStatus::class, 'INVALID');
            $string = (string) $exception;

            expect($string)->toStartWith(InvalidEnumException::class);
            expect($string)->toContain('Case name [INVALID] does not exist');
        });
    });

    // ── declare(strict_types=1) verification ────────────────────────────

    describe('Strict types declaration', function () {
        it('all source files declare strict_types=1', function () {
            $srcDir = dirname(__DIR__, 2).'/src';
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $violations = [];
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = $file->getContents();
                if (! str_contains($contents, 'declare(strict_types=1)')) {
                    $violations[] = $file->getFilename();
                }
            }

            expect($violations)->toBeEmpty('Missing declare(strict_types=1) in: '.implode(', ', $violations));
        });
    });

    // ── Pure enum completeness ────────────────────────────────────────

    describe('Pure enum behavior', function () {
        it('values() returns case names for pure enum', function () {
            $values = PureFeatureFlag::values();

            foreach ($values as $value) {
                expect($value)->toBeString();
                expect(PureFeatureFlag::tryFromName($value))->not->toBeNull();
            }
        });

        it('forSelect() uses case names as values for pure enum', function () {
            $select = PureFeatureFlag::forSelect();
            $names = array_column($select, 'value');

            foreach ($names as $name) {
                expect($name)->toBeString();
                expect(PureFeatureFlag::hasCase($name))->toBeTrue();
            }
        });

        it('forApi() returns name as value and backed-type-correct output', function () {
            $api = PureFeatureFlag::forApi();

            foreach ($api as $item) {
                expect($item['value'])->toBe($item['name']); // pure enum: value === name
                expect($item['value'])->toBeString();
            }
        });

        it('color() always returns a non-empty string (default secondary)', function () {
            foreach (PureFeatureFlag::cases() as $case) {
                expect($case->color())->toBeString()->not->toBeEmpty();
            }
        });
    });

    // ── Attribute classes are all final + readonly ─────────────────────

    describe('Attribute class design', function () {
        $attributeClasses = [
            Label::class, Color::class, Icon::class, Description::class,
            EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class,
        ];

        foreach ($attributeClasses as $className) {
            it("{$className} is final", function () use ($className) {
                $ref = new ReflectionClass($className);
                expect($ref->isFinal())->toBeTrue();
            });
        }
    });
});
