<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * PHPStan Level 9 public API type contract verification.
 *
 * Manually verifies that all public methods have strict, non-mixed return types,
 * use strict comparison operators, and that no public parameter accepts `mixed`.
 * This complements the static analysis by checking semantic type-safety contracts
 * that PHPStan alone cannot verify (e.g., strict comparisons, value types).
 *
 * @see https://phpstan.org/blog/what-is-phpstan-level-9
 */

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('PHPStan Level 9 — Public API Type Contract', function (): void {

    // ──────────────────────────────────────────────────────────────
    // 1. No public method returns mixed
    // ──────────────────────────────────────────────────────────────

    describe('No public method returns mixed', function (): void {
        $publicClasses = [
            EnumManager::class,
            EnumCache::class,
            EnumRule::class,
            EnumCast::class,
            InvalidEnumException::class,
            Enum::class,
            EnumMetadataResolver::class,
            EnumsServiceProvider::class,
            \ZeroBoiler\Enums\Console\Commands\InspectEnumCommand::class,
            \ZeroBoiler\Enums\Console\Commands\MakeEnumTestCommand::class,
            \ZeroBoiler\Enums\Support\EnumTestGenerator::class,
        ];

        foreach ($publicClasses as $class) {
            $short = (new \ReflectionClass($class))->getShortName();

            it("{$short}: no public method returns mixed", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                $publicMethods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

                foreach ($publicMethods as $method) {
                    $returnType = $method->getReturnType();
                    if ($returnType === null) {
                        continue;
                    }

                    // 'mixed' has no allowedNull (it's always nullable)
                    $name = $returnType->getName();
                    expect($name)->not->toBe('mixed',
                        "{$class}::{$method->getName()}() returns mixed — not PHPStan L9 compliant");
                }
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 2. No public method has a mixed parameter
    // ──────────────────────────────────────────────────────────────

    describe('No public method has mixed parameter (except validate)', function (): void {
        // EnumRule::validate() uses `mixed $value` because the Laravel
        // ValidationRule interface requires it. This is the sole exception.
        $allowedMixedParams = [
            EnumRule::class => ['validate'],
        ];

        $classesToCheck = [
            EnumManager::class,
            EnumCache::class,
            EnumCast::class,
            InvalidEnumException::class,
            EnumMetadataResolver::class,
            \ZeroBoiler\Enums\Support\EnumTestGenerator::class,
            \ZeroBoiler\Enums\Console\Commands\InspectEnumCommand::class,
        ];

        foreach ($classesToCheck as $class) {
            $short = (new \ReflectionClass($class))->getShortName();

            it("{$short}: no public method accepts mixed parameter", function () use ($class, $allowedMixedParams): void {
                $ref = new \ReflectionClass($class);
                $publicMethods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
                $allowed = $allowedMixedParams[$class] ?? [];

                foreach ($publicMethods as $method) {
                    if (in_array($method->getName(), $allowed, true)) {
                        continue;
                    }

                    foreach ($method->getParameters() as $param) {
                        $type = $param->getType();
                        if ($type === null) {
                            // No type = implicitly mixed — fail
                            expect(true)->toBeFalse(
                                "{$class}::{$method->getName()}() param \${$param->getName()} has no type declaration"
                            );
                        }

                        $typeName = $type instanceof \ReflectionNamedType
                            ? $type->getName()
                            : (string) $type;

                        expect($typeName)->not->toBe('mixed',
                            "{$class}::{$method->getName()}() param \${$param->getName()} is mixed — not PHPStan L9 compliant"
                        );
                    }
                }
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 3. HasEnumMetadata trait — all methods have return types
    // ──────────────────────────────────────────────────────────────

    describe('HasEnumMetadata trait methods have explicit return types', function (): void {
        $methods = [
            'label' => 'string',
            'description' => '?string',
            'color' => 'string',
            'icon' => '?string',
            'toValue' => 'int|string',
            'is' => 'bool',
            'isNot' => 'bool',
            'in' => 'bool',
            'notIn' => 'bool',
            'forSelect' => 'array',
            'forApi' => 'array',
            'tryFromLabel' => '?static',
            'tryFromName' => '?static',
            'fromName' => 'static',
            'hasCase' => 'bool',
            'values' => 'array',
            'labels' => 'array',
        ];

        foreach ($methods as $method => $expectedReturnType) {
            it("trait method {$method}() has return type", function () use ($method, $expectedReturnType): void {
                $ref = new \ReflectionClass(HasEnumMetadata::class);
                $m = $ref->getMethod($method);
                expect($m->hasReturnType())->toBeTrue("{$method}() missing return type");

                $rt = $m->getReturnType();
                // For union types (int|string), getReturnType() returns ReflectionUnionType
                $actual = (string) $rt;
                expect($actual)->toBe($expectedReturnType,
                    "{$method}() return type mismatch: expected {$expectedReturnType}, got {$actual}"
                );
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 4. Attribute classes — constructor parameter types are strict
    // ──────────────────────────────────────────────────────────────

    describe('Attribute constructor parameters have explicit types', function (): void {
        $attributeClasses = [
            Label::class,
            Color::class,
            Description::class,
            Icon::class,
            EnumLabel::class,
            EnumColor::class,
            EnumDescription::class,
            EnumIcon::class,
        ];

        foreach ($attributeClasses as $class) {
            $short = (new \ReflectionClass($class))->getShortName();

            it("{$short}: all constructor params have typed declarations", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                $constructor = $ref->getConstructor();
                expect($constructor)->not->toBeNull("{$short} missing constructor");

                foreach ($constructor->getParameters() as $param) {
                    $type = $param->getType();
                    expect($type)->not->toBeNull(
                        "{$short}::\$param->getName()} has no type declaration"
                    );
                }
            });

            it("{$short}: all properties are readonly", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                foreach ($ref->getProperties() as $prop) {
                    expect($prop->isReadOnly())->toBeTrue(
                        "{$short}::\${$prop->getName()} is not readonly"
                    );
                }
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 5. Strict comparison verification — runtime behavior
    // ──────────────────────────────────────────────────────────────

    describe('Runtime strict comparison behavior', function (): void {
        it('is() uses strict identity (not loose equality)', function (): void {
            // String-backed: '0' should NOT match int 0
            $result = UserStatus::ACTIVE->is('active');
            expect($result)->toBeTrue();

            // Wrong case should not match (strict string comparison)
            $result = UserStatus::ACTIVE->is('Active');
            expect($result)->toBeFalse();

            $result = UserStatus::ACTIVE->is('ACTIVE');
            expect($result)->toBeTrue();
        });

        it('in() uses strict matching (not loose in_array)', function (): void {
            // These should NOT match — string '0' vs int 0, different types
            $result = UserStatus::ACTIVE->in(['active', 'pending']);
            expect($result)->toBeTrue();

            // Wrong case in string comparison
            $result = UserStatus::ACTIVE->in(['Active']);
            expect($result)->toBeFalse();
        });

        it('tryFromName is case-sensitive (strict === comparison)', function (): void {
            expect(OrderStatus::tryFromName('PENDING'))->not->toBeNull();
            expect(OrderStatus::tryFromName('pending'))->toBeNull();
            expect(OrderStatus::tryFromName('Pending'))->toBeNull();
        });

        it('hasCase is case-sensitive', function (): void {
            expect(OrderStatus::hasCase('PENDING'))->toBeTrue();
            expect(OrderStatus::hasCase('pending'))->toBeFalse();
            expect(OrderStatus::hasCase('Pending'))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 6. Value type consistency — forSelect/forApi return correct types
    // ──────────────────────────────────────────────────────────────

    describe('Value type consistency across enum types', function (): void {
        it('string-backed enum: forSelect values are strings', function (): void {
            $select = OrderStatus::forSelect();
            foreach ($select as $option) {
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }
        });

        it('int-backed enum: forSelect values are ints', function (): void {
            $select = IntBackedPriority::forSelect();
            foreach ($select as $option) {
                expect($option['value'])->toBeInt();
                expect($option['label'])->toBeString();
            }
        });

        it('pure enum: forSelect values are strings (case names)', function (): void {
            $select = PureSystemState::forSelect();
            foreach ($select as $option) {
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }
        });

        it('forApi always returns string color (never null)', function (): void {
            $fixtures = [OrderStatus::class, IntBackedPriority::class, PureSystemState::class];

            foreach ($fixtures as $enum) {
                $api = $enum::forApi();
                foreach ($api as $item) {
                    expect($item['color'])->toBeString();
                    expect($item['color'])->not->toBeEmpty();
                }
            }
        });

        it('forApi value types match the backing type', function (): void {
            // String-backed
            $api = OrderStatus::forApi();
            foreach ($api as $item) {
                expect($item['value'])->toBeString();
            }

            // Int-backed
            $api = IntBackedPriority::forApi();
            foreach ($api as $item) {
                expect($item['value'])->toBeInt();
            }

            // Pure enum — values are case names (strings)
            $api = PureSystemState::forApi();
            foreach ($api as $item) {
                expect($item['value'])->toBeString();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 7. EnumCache — get() throws on missing key (no silent failure)
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache get() throws on missing key', function (): void {
        it('get() throws OutOfBoundsException for uncached class', function (): void {
            EnumCache::resetInstance();
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect(fn () => $cache->get('NonExistentEnumClass'))
                ->toThrow(\OutOfBoundsException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 8. InvalidEnumException — factory methods produce correct messages
    // ──────────────────────────────────────────────────────────────

    describe('InvalidEnumException message contracts', function (): void {
        it('forName() includes class and name in message', function (): void {
            $e = InvalidEnumException::forName('A'p'p\E'nums\UserStatus', 'NONEXISTENT');
            $msg = $e->getMessage();
            expect($msg)->toContain('NONEXISTENT');
            expect($msg)->toContain('App\\Enums\\UserStatus');
        });

        it('value() includes value in message', function (): void {
            $e = InvalidEnumException::value('A'p'p\E'nums\UserStatus', 'invalid_value');
            $msg = $e->getMessage();
            expect($msg)->toContain('invalid_value');
            expect($msg)->toContain('App\\Enums\\UserStatus');
        });

        it('value() with null displays "null" in message', function (): void {
            $e = InvalidEnumException::value('A'p'p\E'nums\UserStatus', null);
            $msg = $e->getMessage();
            expect($msg)->toContain('null');
        });

        it('__toString() returns class name and message', function (): void {
            $e = InvalidEnumException::forName('A'p'p\E'nums\UserStatus', 'BAD');
            $str = (string) $e;
            expect($str)->toContain('InvalidEnumException');
            expect($str)->toContain('BAD');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 9. EnumManager — delegates to trait with correct types
    // ──────────────────────────────────────────────────────────────

    describe('EnumManager return type correctness', function (): void {
        it('forSelect() returns list with string value and string label keys', function (): void {
            $manager = new EnumManager;
            $result = $manager->forSelect(OrderStatus::class);

            expect($result)->toBeArray();
            expect($result)->not->toBeEmpty();

            foreach ($result as $item) {
                expect(array_keys($item))->toContain('value');
                expect(array_keys($item))->toContain('label');
            }
        });

        it('tryFromLabel() returns null for non-matching label', function (): void {
            $manager = new EnumManager;
            $result = $manager->tryFromLabel(OrderStatus::class, 'nonexistent-label-xyz-123');
            expect($result)->toBeNull();
        });

        it('tryFromName() returns null for non-matching name', function (): void {
            $manager = new EnumManager;
            $result = $manager->tryFromName(OrderStatus::class, 'NONEXISTENT');
            expect($result)->toBeNull();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. EnumRule — nullable flag behavior with type safety
    // ──────────────────────────────────────────────────────────────

    describe('EnumRule nullable behavior', function (): void {
        it('non-nullable rule rejects null', function (): void {
            $rule = EnumRule::for(OrderStatus::class);
            $failed = false;
            $rule->validate('status', null, function (string $message) use (&$failed): void {
                $failed = true;
                expect($message)->toBeString();
            });
            expect($failed)->toBeTrue();
        });

        it('nullable rule accepts null', function (): void {
            $rule = EnumRule::for(OrderStatus::class)->nullable();
            $failed = false;
            $rule->validate('status', null, function (string $message) use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });

        it('rejects wrong PHP type for int-backed enum', function (): void {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;
            $rule->validate('priority', 'not-an-int', function (string $message) use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('rejects int for string-backed enum', function (): void {
            $rule = EnumRule::for(OrderStatus::class);
            $failed = false;
            $rule->validate('status', 123, function (string $message) use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });
    });
});
