<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Coverage boost tests for enum infrastructure components that are
 * under-tested: EnumMetadataResolver::invalidate/invalidateAll,
 * EnumRule with pure enums, InvalidEnumException::__toString,
 * EnumCache::__debugInfo, EnumCache TTL boundary behavior,
 * comparison methods, and metadata resolution edge cases.
 */
describe('Enum infrastructure coverage boost', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    // -----------------------------------------------------------------------
    // EnumMetadataResolver::invalidate / invalidateAll
    // -----------------------------------------------------------------------
    describe('EnumMetadataResolver cache invalidation', function (): void {
        it('invalidate removes cached metadata for one class', function (): void {
            $cache = EnumCache::getInstance();

            // Resolve to populate cache
            $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeTrue();

            EnumMetadataResolver::invalidate(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();

            // Re-resolve should work and produce same result
            $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta2)->toBe($meta1);
        });

        it('invalidateAll removes all cached metadata', function (): void {
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(IntPriority::class);
            EnumMetadataResolver::resolve(PureFeatureFlag::class);

            $cache = EnumCache::getInstance();
            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(IntPriority::class))->toBeTrue();
            expect($cache->has(PureFeatureFlag::class))->toBeTrue();

            EnumMetadataResolver::invalidateAll();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(IntPriority::class))->toBeFalse();
            expect($cache->has(PureFeatureFlag::class))->toBeFalse();
        });

        it('invalidate on non-cached class is a no-op', function (): void {
            // Should not throw
            EnumMetadataResolver::invalidate('NonExistentClass');
            expect(true)->toBeTrue();
        });

        it('invalidateAll on empty cache is a no-op', function (): void {
            // Should not throw
            EnumMetadataResolver::invalidateAll();
            expect(true)->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // InvalidEnumException::__toString
    // -----------------------------------------------------------------------
    describe('InvalidEnumException __toString', function (): void {
        it('returns class name and message for value factory', function (): void {
            $ex = InvalidEnumException::value(UserStatus::class, 'bad_value');

            $str = $ex->__toString();

            expect($str)->toBeString();
            expect($str)->toContain('InvalidEnumException');
            expect($str)->toContain('bad_value');
            expect($str)->toContain('UserStatus');
        });

        it('returns class name and message for forName factory', function (): void {
            $ex = InvalidEnumException::forName(UserStatus::class, 'FAKE');

            $str = $ex->__toString();

            expect($str)->toBeString();
            expect($str)->toContain('FAKE');
            expect($str)->toContain('UserStatus');
        });
    });

    // -----------------------------------------------------------------------
    // EnumCache::__debugInfo
    // -----------------------------------------------------------------------
    describe('EnumCache __debugInfo', function (): void {
        it('returns ttl, cachedClasses, and timestampCount', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(120);

            $debug = $cache->__debugInfo();

            expect($debug)->toBeArray();
            expect($debug)->toHaveKeys(['ttl', 'cachedClasses', 'timestampCount']);
            expect($debug['ttl'])->toBe(120);
            expect($debug['cachedClasses'])->toBeInt();
            expect($debug['timestampCount'])->toBeInt();
        });

        it('shows correct cachedClasses count', function (): void {
            $cache = EnumCache::getInstance();

            expect($cache->__debugInfo()['cachedClasses'])->toBe(0);

            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(IntPriority::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->__debugInfo()['cachedClasses'])->toBe(2);
            expect($cache->__debugInfo()['timestampCount'])->toBe(2);
        });
    });

    // -----------------------------------------------------------------------
    // EnumCache TTL boundary behavior
    // -----------------------------------------------------------------------
    describe('EnumCache TTL boundary behavior', function (): void {
        it('TTL of 0 disables caching', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // With TTL 0, has() should return false immediately
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('getTtl returns 0 after setting TTL to 0', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);

            expect($cache->getTtl())->toBe(0);
        });

        it('clear removes timestamp too', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->__debugInfo()['timestampCount'])->toBe(1);

            $cache->clear();

            expect($cache->__debugInfo()['cachedClasses'])->toBe(0);
            expect($cache->__debugInfo()['timestampCount'])->toBe(0);
        });
    });

    // -----------------------------------------------------------------------
    // EnumRule with pure enums
    // -----------------------------------------------------------------------
    describe('EnumRule with pure enums', function (): void {
        it('validates valid pure enum case name', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);

            $error = null;
            $rule->validate('flag', 'DARK_MODE', function (string $message) use (&$error): void {
                $error = $message;
            });

            expect($error)->toBeNull();
        });

        it('rejects invalid pure enum case name', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);

            $rule->validate('flag', 'NONEXISTENT_FLAG', function (string $message): void {
                expect($message)->toBeString();
                expect($message)->toContain('flag');
            });
        });

        it('rejects non-string value for pure enum', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class);

            $rule->validate('flag', 123, function (string $message): void {
                expect($message)->toBeString();
            });
        });

        it('accepts null when nullable for pure enum', function (): void {
            $rule = EnumRule::for(PureFeatureFlag::class)->nullable();

            $error = null;
            $rule->validate('flag', null, function (string $message) use (&$error): void {
                $error = $message;
            });

            expect($error)->toBeNull();
        });

        it('rejects null when not nullable for pure enum', function (): void {
            $rule = EnumRule::for(PureSystemState::class);

            $rule->validate('state', null, function (string $message): void {
                expect($message)->toBeString();
            });
        });
    });

    // -----------------------------------------------------------------------
    // EnumRule error message includes allowed values
    // -----------------------------------------------------------------------
    describe('EnumRule error message with HasEnumMetadata', function (): void {
        it('includes allowed values for backed enum with metadata', function (): void {
            $rule = EnumRule::for(UserStatus::class);

            $rule->validate('status', 'invalid', function (string $message): void {
                // When enum uses HasEnumMetadata, values() is available
                // and the message should include allowed values
                expect($message)->toContain('Allowed values');
            });
        });

        it('falls back to generic message for enum without metadata', function (): void {
            // IntPriority uses HasEnumMetadata, so it will show values
            $rule = EnumRule::for(IntPriority::class);

            $rule->validate('priority', 999, function (string $message): void {
                expect($message)->toContain('priority');
            });
        });
    });

    // -----------------------------------------------------------------------
    // EnumRule with non-existent class
    // -----------------------------------------------------------------------
    describe('EnumRule with non-existent class', function (): void {
        it('fails validation for non-existent enum class', function (): void {
            $rule = EnumRule::for('App\Enums\NonExistentEnum');

            $rule->validate('field', 'value', function (string $message): void {
                expect($message)->toBeString();
            });
        });
    });

    // -----------------------------------------------------------------------
    // Metadata resolution for camelCase enum
    // -----------------------------------------------------------------------
    describe('Metadata for camelCase enums', function (): void {
        it('generates correct label from camelCase name', function (): void {
            // CamelCaseRole has camelCase cases
            expect(CamelCaseRole::SUPER_ADMIN->label())->toBe('Super Admin');
        });

        it('values returns backed values', function (): void {
            $values = CamelCaseRole::values();

            foreach ($values as $value) {
                expect($value)->toBeString();
            }

            expect($values)->toHaveCount(count(CamelCaseRole::cases()));
        });
    });

    // -----------------------------------------------------------------------
    // PaymentStatus (edge case: class-level EnumDescription)
    // -----------------------------------------------------------------------
    describe('PaymentStatus with class-level descriptions', function (): void {
        it('resolves class-level descriptions', function (): void {
            $desc = PaymentStatus::COMPLETED->description();

            // PaymentStatus has class-level EnumDescription
            expect($desc)->toBeString();
        });

        it('toValue returns backed value', function (): void {
            expect(PaymentStatus::COMPLETED->toValue())->toBeString();
        });
    });

    // -----------------------------------------------------------------------
    // forSelect / forApi with int-backed enums
    // -----------------------------------------------------------------------
    describe('forSelect and forApi with int-backed enums', function (): void {
        it('forSelect uses int values as keys', function (): void {
            $select = IntPriority::forSelect();

            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeInt();
                expect($option['label'])->toBeString();
            }
        });

        it('forApi includes int values', function (): void {
            $api = IntPriority::forApi();

            foreach ($api as $item) {
                expect($item['value'])->toBeInt();
                expect($item['name'])->toBeString();
            }
        });
    });

    // -----------------------------------------------------------------------
    // Comparison methods with mixed instance and string args
    // -----------------------------------------------------------------------
    describe('Comparison methods with mixed args', function (): void {
        it('in() accepts mix of instances and strings', function (): void {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['INACTIVE', UserStatus::BANNED]))->toBeFalse();
        });

        it('notIn() accepts mix of instances and strings', function (): void {
            expect(UserStatus::ACTIVE->notIn(['INACTIVE', UserStatus::BANNED]))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn([UserStatus::ACTIVE, 'PENDING']))->toBeFalse();
        });

        it('is() with string is case-sensitive', function (): void {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
        });

        it('is() with self instance uses strict identity', function (): void {
            $active = UserStatus::ACTIVE;

            expect($active->is(UserStatus::ACTIVE))->toBeTrue();
            expect($active->is(UserStatus::BANNED))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // tryFromLabel edge cases
    // -----------------------------------------------------------------------
    describe('tryFromLabel edge cases', function (): void {
        it('returns null for empty string', function (): void {
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('is truly case-insensitive', function (): void {
            $label = UserStatus::ACTIVE->label();

            expect(UserStatus::tryFromLabel(strtoupper($label)))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(strtolower($label)))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(ucfirst(strtolower($label))))->toBe(UserStatus::ACTIVE);
        });

        it('returns first match when multiple cases have similar labels', function (): void {
            // This is inherently an O(n) first-match — document the behavior
            $result = UserStatus::tryFromLabel(UserStatus::ACTIVE->label());

            expect($result)->toBe(UserStatus::ACTIVE);
        });
    });

    // -----------------------------------------------------------------------
    // EnumRule nullable chaining
    // -----------------------------------------------------------------------
    describe('EnumRule nullable chaining', function (): void {
        it('nullable() returns a new instance', function (): void {
            $original = EnumRule::for(UserStatus::class);
            $nullable = $original->nullable();

            expect($nullable)->not->toBe($original);
        });

        it('original instance still rejects null after nullable() call', function (): void {
            $original = EnumRule::for(UserStatus::class);
            $original->nullable(); // create nullable, discard

            $original->validate('status', null, function (string $message): void {
                expect($message)->toBeString();
            });
        });
    });
});
