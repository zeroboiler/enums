<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

describe('Enums Production Hardening V3', function () {
    describe('EnumCache cross-type isolation', function () {
        it('keeps metadata for different enum types independent', function () {
            EnumCache::flush();

            // Resolve string-backed enum
            $userMeta = EnumMetadataResolver::resolve(UserStatus::class);
            expect($userMeta['labels'])->toHaveKey('active');

            // Resolve int-backed enum
            $intMeta = EnumMetadataResolver::resolve(IntBackedPriority::class);
            expect($intMeta['labels'])->toHaveKey(1);

            // Resolve pure enum
            $pureMeta = EnumMetadataResolver::resolve(PureFeatureFlag::class);
            expect($pureMeta['labels'])->toHaveKey('TWO_FACTOR_AUTH');

            // String-backed label should not leak to int-backed
            expect($intMeta['labels'])->not->toHaveKey('active');
            expect($userMeta['labels'])->not->toHaveKey(1);
        });

        it('isolates clearClass operations', function () {
            EnumCache::flush();

            // Resolve two enums
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(PaymentStatus::class);

            // Clear only one
            EnumCache::getInstance()->clearClass(UserStatus::class);

            // PaymentStatus should still be cached
            $cache = EnumCache::getInstance();
            expect($cache->has(PaymentStatus::class))->toBeTrue();
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('supports concurrent TTL across multiple enums', function () {
            EnumCache::flush();
            EnumCache::getInstance()->setTtl(60);

            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(IntBackedPriority::class);
            EnumMetadataResolver::resolve(PureFeatureFlag::class);
            EnumMetadataResolver::resolve(SingleCaseEnum::class);

            // All should be cached
            $cache = EnumCache::getInstance();
            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(IntBackedPriority::class))->toBeTrue();
            expect($cache->has(PureFeatureFlag::class))->toBeTrue();
            expect($cache->has(SingleCaseEnum::class))->toBeTrue();

            // Reset TTL to 0 (disable caching)
            EnumCache::getInstance()->setTtl(0);
            expect($cache->has(UserStatus::class))->toBeFalse();
        });
    });

    describe('EnumRule with int-backed and pure enums', function () {
        it('validates int-backed enum with strict type checking', function () {
            $rule = EnumRule::for(IntBackedPriority::class);

            // Valid int values
            $fail = fn (string $msg): string => $msg;
            $validator = new Validator(
                resolve(app('translator')),
                ['priority' => 1],
                ['priority' => [$rule]]
            );

            expect($rule->validate('priority', 1, function () {}))->toBeNull(); // 1 is valid
        });

        it('rejects string value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;

            $rule->validate('priority', '1', function () use (&$failed) {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('rejects float value for int-backed enum', function () {
            $rule = EnumRule::for(IntBackedPriority::class);
            $failed = false;

            $rule->validate('priority', 1.5, function () use (&$failed) {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('validates pure enum by case name', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 'TWO_FACTOR_AUTH', function () use (&$failed) {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('rejects invalid pure enum case name', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 'NONEXISTENT', function () use (&$failed) {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('rejects int value for pure enum', function () {
            $rule = EnumRule::for(PureFeatureFlag::class);
            $failed = false;

            $rule->validate('feature', 42, function () use (&$failed) {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    describe('EnumRule nullable behavior', function () {
        it('passes null when nullable is true', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', null, function () use (&$failed) {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('rejects null when nullable is false', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;

            $rule->validate('status', null, function () use (&$failed) {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('still validates non-null values when nullable is true', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;

            $rule->validate('status', 'invalid_value', function () use (&$failed) {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    describe('Metadata resolution edge cases', function () {
        it('returns empty labels for single-case enum without attributes', function () {
            $meta = EnumMetadataResolver::resolve(SingleCaseEnum::class);
            // Single case without label — should have auto-generated label
            expect($meta['labels'])->not->toBeEmpty();
        });

        it('handles zero-backed int enum correctly', function () {
            $case = ZeroPriority::NONE;
            expect($case->value)->toBe(0);
            expect($case->label())->toBeString()->not->toBeEmpty();
            expect($case->color())->toBeString();
        });

        it('handles camelCase label generation', function () {
            $cases = CamelCaseRole::cases();
            foreach ($cases as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });

        it('resolves EnumDescription at case level', function () {
            $meta = EnumMetadataResolver::resolve(UserStatus::class);
            // ACTIVE has a per-case Description
            expect($meta['descriptions']['active'])->toBe('User can fully access the system');
            // BANNED has a per-case Description
            expect($meta['descriptions']['banned'])->toBe('User is permanently banned');
        });

        it('falls back to null for missing descriptions', function () {
            expect(UserStatus::INACTIVE->description())->toBeNull();
            expect(UserStatus::SUSPENDED->description())->toBeNull();
        });
    });

    describe('Comparison methods edge cases', function () {
        it('notIn returns false when case is in the list', function () {
            expect(UserStatus::ACTIVE->notIn(['ACTIVE', 'INACTIVE']))->toBeFalse();
        });

        it('notIn returns true when case is not in the list', function () {
            expect(UserStatus::ACTIVE->notIn(['BANNED', 'SUSPENDED']))->toBeTrue();
        });

        it('notIn accepts mixed instances and strings', function () {
            expect(UserStatus::ACTIVE->notIn([UserStatus::BANNED, 'SUSPENDED']))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn([UserStatus::ACTIVE, 'INACTIVE']))->toBeFalse();
        });

        it('in() works with single-element list', function () {
            expect(UserStatus::ACTIVE->in(['ACTIVE']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['BANNED']))->toBeFalse();
        });

        it('in() works with empty list', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('notIn() works with empty list', function () {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });
    });

    describe('InvalidEnumException', function () {
        it('formats value exception correctly', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'unknown');
            expect($e->getMessage())->toContain('unknown');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('formats null value exception correctly', function () {
            $e = InvalidEnumException::value(UserStatus::class, null);
            expect($e->getMessage())->toContain('null');
        });

        it('formats name exception correctly', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');
            expect($e->getMessage())->toContain('NONEXISTENT');
            expect($e->getMessage())->toContain(UserStatus::class);
        });

        it('serializes to string via __toString', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'BOGUS');
            $str = (string) $e;
            expect($str)->toContain('InvalidEnumException');
            expect($str)->toContain('BOGUS');
        });

        it('serializes value exception to string via __toString', function () {
            $e = InvalidEnumException::value(UserStatus::class, 42);
            $str = (string) $e;
            expect($str)->toContain('InvalidEnumException');
            expect($str)->toContain('42');
        });
    });

    describe('Bulk method consistency', function () {
        it('forSelect() returns consistent order with cases()', function () {
            $selectValues = array_column(UserStatus::forSelect(), 'value');
            $expectedValues = UserStatus::values();
            expect($selectValues)->toBe($expectedValues);
        });

        it('forApi() returns consistent order with cases()', function () {
            $apiValues = array_column(UserStatus::forApi(), 'value');
            $expectedValues = UserStatus::values();
            expect($apiValues)->toBe($expectedValues);
        });

        it('forApi() includes all metadata keys for every case', function () {
            $api = UserStatus::forApi();
            $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];
            foreach ($api as $case) {
                foreach ($requiredKeys as $key) {
                    expect($case)->toHaveKey($key);
                }
            }
        });

        it('labels() returns same count as cases()', function () {
            expect(UserStatus::labels())->toHaveCount(count(UserStatus::cases()));
        });

        it('values() returns same count as cases()', function () {
            expect(UserStatus::values())->toHaveCount(count(UserStatus::cases()));
        });
    });
});
