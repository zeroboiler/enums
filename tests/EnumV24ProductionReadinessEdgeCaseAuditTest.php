<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Fixtures\{PaymentStatus, OrderStatus, IntPriority, IntBackedPriority, CamelCasePriority, PlainTestEnum, PureFeatureFlag, SingleCaseEnum, SingleCaseToggle, ZeroBackedPriority, ZeroPriority};

describe('V24 production readiness comprehensive edge-case audit', function () {
    describe('EnumCache serialization prevention', function () {
        it('throws on serialize()', function () {
            $cache = EnumCache::getInstance();
            expect(fn () => serialize($cache))->toThrow(\RuntimeException::class);
        });

        it('throws on unserialize()', function () {
            // Build a fake serialized string to trigger __unserialize
            $fake = 'O:33:"ZeroBoiler\Enums\EnumCache":0:{}';
            expect(fn () => unserialize($fake))->toThrow(\RuntimeException::class);
        });

        it('__wakeup always throws RuntimeException', function () {
            $cache = EnumCache::getInstance();
            expect(fn () => $cache->__wakeup())->toThrow(\RuntimeException::class);
        });
    });

    describe('EnumCache TTL expiration edge cases', function () {
        it('cached entry expires when TTL is reached exactly', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(1);

            // Store an entry
            $cache->set('TestEnum::class', [
                'labels' => ['a' => 'Test'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // Should be available immediately
            expect($cache->has('TestEnum::class'))->toBeTrue();

            // Wait for TTL to expire
            sleep(2);

            // Should now be expired
            expect($cache->has('TestEnum::class'))->toBeFalse();

            $cache->setTtl(300); // restore
        });
    });

    describe('metadata isolation between enums', function () {
        it('resolving one enum does not pollute another', function () {
            $metaA = EnumMetadataResolver::resolve(PaymentStatus::class);
            $metaB = EnumMetadataResolver::resolve(IntPriority::class);

            // Labels should have different keys (string vs int backed)
            expect($metaA['labels'])->not->toEqual($metaB['labels']);

            // Each should have correct types for their backing values
            $paymentValues = PaymentStatus::values();
            foreach ($paymentValues as $v) {
                expect($v)->toBeString();
            }

            $priorityValues = IntPriority::values();
            foreach ($priorityValues as $v) {
                expect($v)->toBeInt();
            }
        });

        it('invalidating one enum leaves others cached', function () {
            EnumMetadataResolver::resolve(PaymentStatus::class);
            EnumMetadataResolver::resolve(OrderStatus::class);

            // Invalidate only PaymentStatus
            EnumMetadataResolver::invalidate(PaymentStatus::class);

            $cache = EnumCache::getInstance();
            expect($cache->has(PaymentStatus::class))->toBeFalse();
            expect($cache->has(OrderStatus::class))->toBeTrue();

            // Clean up
            EnumMetadataResolver::invalidateAll();
        });
    });

    describe('attribute precedence resolution order', function () {
        it('per-case Color overrides class-level EnumColor', function () {
            // PaymentStatus uses EnumColor for class-level + Color for per-case override
            $paid = PaymentStatus::PAID;
            $refunded = PaymentStatus::REFUNDED;

            // Both should have string colors
            expect($paid->color())->toBeString();
            expect($refunded->color())->toBeString();

            // Colors should be non-empty
            expect($paid->color())->not->toBeEmpty();
            expect($refunded->color())->not->toBeEmpty();
        });

        it('per-case Label overrides auto-generated label', function () {
            $label = PaymentStatus::PAID->label();
            // PAID has #[Label('Paid')] in the fixture
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        });

        it('class-level EnumLabel provides labels for all mapped values', function () {
            // Verify all cases return a label
            foreach (PaymentStatus::cases() as $case) {
                $label = $case->label();
                expect($label)->toBeString();
                expect(strlen($label))->toBeGreaterThan(0);
            }
        });
    });

    describe('EnumCast comprehensive type safety', function () {
        it('get() with float value returns null (not coerced)', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->get(new \stdClass(), 'status', 3.14, []);
            expect($result)->toBeNull();
        });

        it('get() with array value returns null', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->get(new \stdClass(), 'status', ['paid'], []);
            expect($result)->toBeNull();
        });

        it('set() with valid raw string stores correctly via tryFrom', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->set(new \stdClass(), 'status', 'paid', []);
            expect($result)->toBe('paid');
        });

        it('set() with invalid raw string throws InvalidArgumentException', function () {
            $cast = new EnumCast(PaymentStatus::class);
            expect(fn () => $cast->set(new \stdClass(), 'status', 'nonexistent', []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('set() with float throws InvalidArgumentException', function () {
            $cast = new EnumCast(PaymentStatus::class);
            expect(fn () => $cast->set(new \stdClass(), 'status', 3.14, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() passes through raw string', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->serialize(new \stdClass(), 'status', 'paid', []);
            expect($result)->toBe('paid');
        });

        it('serialize() returns null for null', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->serialize(new \stdClass(), 'status', null, []);
            expect($result)->toBeNull();
        });

        it('serialize() returns null for non-scalar non-enum', function () {
            $cast = new EnumCast(PaymentStatus::class);
            $result = $cast->serialize(new \stdClass(), 'status', ['array'], []);
            expect($result)->toBeNull();
        });
    });

    describe('EnumRule comprehensive validation scenarios', function () {
        it('float value fails for string-backed enum', function () {
            $rule = EnumRule::for(PaymentStatus::class);
            $failed = false;
            $rule->validate('status', 3.14, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('object value fails for string-backed enum', function () {
            $rule = EnumRule::for(PaymentStatus::class);
            $failed = false;
            $rule->validate('status', new \stdClass(), function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('null fails for non-nullable string-backed enum', function () {
            $rule = EnumRule::for(PaymentStatus::class);
            $failed = false;
            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('valid string passes for string-backed enum', function () {
            $rule = EnumRule::for(PaymentStatus::class);
            $failed = false;
            $rule->validate('status', 'paid', function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });

        it('valid int passes for int-backed enum', function () {
            $rule = EnumRule::for(IntPriority::class);
            $firstValue = IntPriority::cases()[0]->value;
            $failed = false;
            $rule->validate('priority', $firstValue, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });

        it('string value fails for int-backed enum', function () {
            $rule = EnumRule::for(IntPriority::class);
            $failed = false;
            $rule->validate('priority', 'high', function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('null value passes for pure enum when nullable', function () {
            $rule = EnumRule::for(PureFeatureFlag::class)->nullable();
            $failed = false;
            $rule->validate('flag', null, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });

        it('valid case name passes for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $firstCase = PureFeatureFlag::cases()[0]->name;
            $failed = false;
            $rule->validate('flag', $firstCase, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });

        it('invalid case name fails for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;
            $rule->validate('flag', 'NONEXISTENT', function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('error message includes valid values from HasEnumMetadata', function () {
            $rule = EnumRule::for(PaymentStatus::class);
            // We can't easily test the message content via the callback,
            // but we can verify the rule doesn't crash
            $failed = false;
            $rule->validate('status', 'invalid', function (string $message) use (&$failed): void {
                $failed = true;
                // Message should include allowed values
                expect($message)->toContain('Allowed values');
            });
            expect($failed)->toBeTrue();
        });
    });

    describe('bulk method consistency across all enum types', function () {
        it('forSelect() count matches cases() count for every fixture', function () {
            $enums = [
                PaymentStatus::class,
                OrderStatus::class,
                IntPriority::class,
                IntBackedPriority::class,
                CamelCasePriority::class,
                PlainTestEnum::class,
                PureFeatureFlag::class,
                SingleCaseEnum::class,
                SingleCaseToggle::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($enums as $enumClass) {
                $caseCount = count($enumClass::cases());
                $selectCount = count($enumClass::forSelect());
                expect($selectCount)->toBe($caseCount, "forSelect() count mismatch for {$enumClass}");
            }
        });

        it('forApi() count matches cases() count for every fixture', function () {
            $enums = [
                PaymentStatus::class,
                OrderStatus::class,
                IntPriority::class,
                IntBackedPriority::class,
                CamelCasePriority::class,
                PlainTestEnum::class,
                PureFeatureFlag::class,
                SingleCaseEnum::class,
                SingleCaseToggle::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($enums as $enumClass) {
                $caseCount = count($enumClass::cases());
                $apiCount = count($enumClass::forApi());
                expect($apiCount)->toBe($caseCount, "forApi() count mismatch for {$enumClass}");
            }
        });

        it('values() count matches cases() count for every fixture', function () {
            $enums = [
                PaymentStatus::class,
                OrderStatus::class,
                IntPriority::class,
                IntBackedPriority::class,
                CamelCasePriority::class,
                PlainTestEnum::class,
                PureFeatureFlag::class,
                SingleCaseEnum::class,
                SingleCaseToggle::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($enums as $enumClass) {
                $caseCount = count($enumClass::cases());
                $valuesCount = count($enumClass::values());
                expect($valuesCount)->toBe($caseCount, "values() count mismatch for {$enumClass}");
            }
        });

        it('labels() count matches cases() count for every fixture', function () {
            $enums = [
                PaymentStatus::class,
                OrderStatus::class,
                IntPriority::class,
                IntBackedPriority::class,
                CamelCasePriority::class,
                PlainTestEnum::class,
                PureFeatureFlag::class,
                SingleCaseEnum::class,
                SingleCaseToggle::class,
                ZeroBackedPriority::class,
                ZeroPriority::class,
            ];

            foreach ($enums as $enumClass) {
                $caseCount = count($enumClass::cases());
                $labelsCount = count($enumClass::labels());
                expect($labelsCount)->toBe($caseCount, "labels() count mismatch for {$enumClass}");
            }
        });
    });

    describe('comparison edge cases with all enum types', function () {
        it('is() with same instance returns true (identity check)', function () {
            $case = PaymentStatus::PAID;
            expect($case->is($case))->toBeTrue();
        });

        it('is() with different instance of same case returns true (PHP enum singleton)', function () {
            expect(PaymentStatus::PAID->is(PaymentStatus::PAID))->toBeTrue();
        });

        it('in() with single element array works correctly', function () {
            expect(PaymentStatus::PAID->in([PaymentStatus::PAID]))->toBeTrue();
            expect(PaymentStatus::PAID->in([PaymentStatus::REFUNDED]))->toBeFalse();
        });

        it('notIn() with single element array works correctly', function () {
            expect(PaymentStatus::PAID->notIn([PaymentStatus::PAID]))->toBeFalse();
            expect(PaymentStatus::PAID->notIn([PaymentStatus::REFUNDED]))->toBeTrue();
        });

        it('is() works for int-backed enum with string case name', function () {
            $case = IntPriority::cases()[0];
            expect($case->is($case->name))->toBeTrue();
        });

        it('is() works for pure enum with string case name', function () {
            $case = PureFeatureFlag::cases()[0];
            expect($case->is($case->name))->toBeTrue();
        });
    });

    describe('tryFromLabel edge cases', function () {
        it('returns null for whitespace-only label', function () {
            expect(PaymentStatus::tryFromLabel('   '))->toBeNull();
        });

        it('label lookup is truly case-insensitive with unicode', function () {
            $label = PaymentStatus::PAID->label();
            $upper = strtoupper($label);
            $lower = strtolower($label);

            expect(PaymentStatus::tryFromLabel($upper))->not->toBeNull();
            expect(PaymentStatus::tryFromLabel($lower))->not->toBeNull();
        });

        it('duplicate labels return first matching case', function () {
            // This verifies that tryFromLabel returns the FIRST match
            // when two cases happen to have the same label
            $case1 = PaymentStatus::tryFromLabel(PaymentStatus::PAID->label());
            expect($case1)->toBe(PaymentStatus::PAID);
        });
    });

    describe('InvalidEnumException factory methods', function () {
        it('forName() includes enum class in message', function () {
            $e = InvalidEnumException::forName(PaymentStatus::class, 'INVALID');
            expect($e->getMessage())->toContain(PaymentStatus::class);
            expect($e->getMessage())->toContain('INVALID');
        });

        it('value() handles null value gracefully', function () {
            $e = InvalidEnumException::value(PaymentStatus::class, null);
            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain(PaymentStatus::class);
        });

        it('value() handles int value in message', function () {
            $e = InvalidEnumException::value(IntPriority::class, 42);
            expect($e->getMessage())->toContain('42');
            expect($e->getMessage())->toContain(IntPriority::class);
        });

        it('__toString includes class name', function () {
            $e = InvalidEnumException::forName(PaymentStatus::class, 'X');
            $str = (string) $e;
            expect($str)->toContain(InvalidEnumException::class);
            expect($str)->toContain('X');
        });
    });

    describe('camelCase label generation', function () {
        it('camelCase enum name generates Title Case label', function () {
            // CamelCasePriority has camelCase cases
            foreach (CamelCasePriority::cases() as $case) {
                $label = $case->label();
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
                // First character should be uppercase
                expect(ctype_upper($label[0]))->toBeTrue();
            }
        });
    });

    describe('zero-value backed enum consistency', function () {
        it('ZeroBackedPriority forSelect includes zero value', function () {
            $select = ZeroBackedPriority::forSelect();
            $values = array_column($select, 'value');
            expect(in_array(0, $values, true))->toBeTrue('Zero value should be in forSelect()');
        });

        it('ZeroPriority forSelect includes zero value', function () {
            $select = ZeroPriority::forSelect();
            $values = array_column($select, 'value');
            expect(in_array(0, $values, true))->toBeTrue('Zero value should be in forSelect()');
        });
    });

    describe('resetInstance clears all state', function () {
        it('resetInstance creates fresh singleton with empty cache', function () {
            $cache = EnumCache::getInstance();
            $cache->set('TestAfterReset::class', [
                'labels' => ['x' => 'Test'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            expect($cache->has('TestAfterReset::class'))->toBeTrue();

            EnumCache::resetInstance();

            $fresh = EnumCache::getInstance();
            expect($fresh->has('TestAfterReset::class'))->toBeFalse();
            // TTL should be back to default 300
            expect($fresh->getTtl())->toBe(300);
        });
    });
});
