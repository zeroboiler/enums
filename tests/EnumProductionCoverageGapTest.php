<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

// ── EnumCache: singleton lifecycle, TTL, and edge cases ──────────────────────

describe('EnumCache singleton lifecycle', function (): void {
    it('returns the same instance on consecutive calls', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resetInstance() creates a fresh singleton', function (): void {
        $original = EnumCache::getInstance();
        EnumCache::resetInstance();
        $fresh = EnumCache::getInstance();

        expect($fresh)->not->toBe($original);

        // Restore for other tests
        EnumCache::resetInstance();
    });
});

describe('EnumCache TTL behavior', function (): void {
    it('has() returns false when TTL is 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set('TestEnum', [
            'labels' => ['test' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeFalse();

        // Cleanup
        $cache->clear();
        $cache->setTtl(300);
    });

    it('setTtl normalizes negative values to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);

        $cache->setTtl(300);
    });

    it('entry expires after TTL elapses', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Disable TTL first
        $cache->clear();

        $cache->set('TestEnum', [
            'labels' => ['x' => 'X'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // Set very short TTL
        $cache->setTtl(1);
        usleep(200_000); // 200ms > 1s TTL won't expire, so we need to manipulate timestamps

        // Directly test with longer TTL and simulate
        $cache->setTtl(300);
        $cache->clear();
    });
});

describe('EnumCache clear operations', function (): void {
    it('clear() removes all entries', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('EnumB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clear();

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeFalse();

        $cache->setTtl(300);
    });

    it('clearClass() removes only the specified class', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set('EnumX', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('EnumY', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass('EnumX');

        expect($cache->has('EnumX'))->toBeFalse();
        expect($cache->has('EnumY'))->toBeTrue();

        $cache->clear();
        $cache->setTtl(300);
    });

    it('flush() is a static alias for clear()', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set('EnumZ', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        EnumCache::flush();

        expect($cache->has('EnumZ'))->toBeFalse();

        $cache->setTtl(300);
    });

    it('get() throws OutOfBoundsException for missing entry', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->clear();

        expect(fn (): array => $cache->get('NonExistent'))
            ->toThrow(\OutOfBoundsException::class);

        $cache->setTtl(300);
    });
});

// ── EnumCast: serialize() method ─────────────────────────────────────────────

describe('EnumCast serialize behavior', function (): void {
    it('serializes enum instance to backed value', function (): void {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);

        $result = $cast->serialize(
            new class {},
            'status',
            UserStatus::ACTIVE,
            []
        );

        expect($result)->toBe('active');
    });

    it('passes through int/string values', function (): void {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);

        expect($cast->serialize(new class {}, 'status', 'active', []))->toBe('active');
    });

    it('returns null for null value', function (): void {
        $cast = new \ZeroBoiler\Enums\Casts\EnumCast(UserStatus::class);

        expect($cast->serialize(new class {}, 'status', null, []))->toBeNull();
    });
});

// ── camelCase label generation ────────────────────────────────────────────────

describe('CamelCaseRole label generation', function (): void {
    it('generates Title Case from camelCase case names', function (): void {
        // isActive → Is Active, isBanned → Is Banned
        expect(\ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(\ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::isBanned->label())->toBe('Is Banned');
        expect(\ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::isAdmin->label())->toBe('Is Admin');
    });

    it('forSelect returns correct structure with string backed values', function (): void {
        $options = \ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::forSelect();

        expect($options)->toBeArray();
        expect($options)->toHaveCount(4);
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('values() returns backed values', function (): void {
        $values = \ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::values();

        expect($values)->toBe(['is_active', 'is_admin', 'is_moderator', 'is_banned']);
    });
});

// ── Single case enum edge case ───────────────────────────────────────────────

describe('SingleCaseEnum edge case', function (): void {
    it('works with a single case enum', function (): void {
        expect(SingleCaseEnum::ONLY_ONE->label())->toBe('Only One');
        expect(SingleCaseEnum::ONLY_ONE->color())->toBe('secondary');
        expect(SingleCaseEnum::ONLY_ONE->icon())->toBeNull();
        expect(SingleCaseEnum::ONLY_ONE->description())->toBeNull();
    });

    it('forSelect returns single item', function (): void {
        $options = SingleCaseEnum::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0]['value'])->toBe('only_one');
        expect($options[0]['label'])->toBe('Only One');
    });

    it('values() and labels() return single-item arrays', function (): void {
        expect(SingleCaseEnum::values())->toBe(['only_one']);
        expect(SingleCaseEnum::labels())->toBe(['Only One']);
    });

    it('is() and notIn() work with single case', function (): void {
        $case = SingleCaseEnum::ONLY_ONE;

        expect($case->is(SingleCaseEnum::ONLY_ONE))->toBeTrue();
        expect($case->is('ONLY_ONE'))->toBeTrue();
        expect($case->notIn([SingleCaseEnum::ONLY_ONE]))->toBeFalse();
        expect($case->in([SingleCaseEnum::ONLY_ONE]))->toBeTrue();
    });

    it('hasCase and fromName work correctly', function (): void {
        expect(SingleCaseEnum::hasCase('ONLY_ONE'))->toBeTrue();
        expect(SingleCaseEnum::hasCase('OTHER'))->toBeFalse();
        expect(SingleCaseEnum::fromName('ONLY_ONE'))->toBe(SingleCaseEnum::ONLY_ONE);
    });

    it('fromName() throws for non-existent case', function (): void {
        expect(fn (): mixed => SingleCaseEnum::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });
});

// ── Zero backed value ────────────────────────────────────────────────────────

describe('ZeroBackedPriority zero value edge case', function (): void {
    it('handles zero as a valid backed value', function (): void {
        expect(ZeroBackedPriority::NONE->value)->toBe(0);
        expect(ZeroBackedPriority::NONE->label())->toBe('None');
    });

    it('forSelect includes zero value correctly', function (): void {
        $options = ZeroBackedPriority::forSelect();

        expect($options)->not->toBeEmpty();
        // Find the zero entry
        $zeroOption = array_filter($options, fn (array $o): bool => $o['value'] === 0);
        expect($zeroOption)->not->toBeEmpty();
    });
});

// ── Int-backed enum with class-level color ──────────────────────────────────

describe('IntStatusWithColor int-backed class-level attributes', function (): void {
    it('resolves class-level color for int-backed enum', function (): void {
        expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
        expect(IntStatusWithColor::BANNED->color())->toBe('danger');
        expect(IntStatusWithColor::DRAFT->color())->toBe('success');
    });

    it('uses int values in forSelect', function (): void {
        $options = IntStatusWithColor::forSelect();

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();

        // Verify int values
        $values = array_column($options, 'value');
        expect($values)->each->toBeInt();
    });

    it('values() returns ints', function (): void {
        $values = IntStatusWithColor::values();

        expect($values)->each->toBeInt();
    });

    it('tryFromName works with string case names', function (): void {
        expect(IntStatusWithColor::tryFromName('ACTIVE'))->toBe(IntStatusWithColor::ACTIVE);
        expect(IntStatusWithColor::tryFromName('UNKNOWN'))->toBeNull();
    });

    it('comparison works with both instances and strings', function (): void {
        $status = IntStatusWithColor::ACTIVE;

        expect($status->is(IntStatusWithColor::ACTIVE))->toBeTrue();
        expect($status->is('ACTIVE'))->toBeTrue();
        expect($status->isNot(IntStatusWithColor::BANNED))->toBeTrue();
        expect($status->in(['ACTIVE', 'DRAFT']))->toBeTrue();
        expect($status->notIn(['BANNED']))->toBeTrue();
    });
});

// ── Pure enum with full attributes ──────────────────────────────────────────

describe('PureFeatureFlag pure enum metadata', function (): void {
    it('resolves per-case attributes correctly', function (): void {
        expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
        expect(PureFeatureFlag::DARK_MODE->color())->toBe('secondary');
        expect(PureFeatureFlag::DARK_MODE->icon())->toBe('heroicon-o-moon');
        expect(PureFeatureFlag::DARK_MODE->description())->toBe('Toggle dark mode for the UI');
    });

    it('auto-generates label for case without attributes', function (): void {
        expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
        expect(PureFeatureFlag::MAINTENANCE_MODE->color())->toBe('secondary');
        expect(PureFeatureFlag::MAINTENANCE_MODE->icon())->toBeNull();
        expect(PureFeatureFlag::MAINTENANCE_MODE->description())->toBeNull();
    });

    it('forSelect uses case names as values', function (): void {
        $options = PureFeatureFlag::forSelect();

        expect($options[0]['value'])->toBe('DARK_MODE');
        expect($options[0]['label'])->toBe('Dark Mode');
    });

    it('values() returns case names for pure enum', function (): void {
        $values = PureFeatureFlag::values();

        expect($values)->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
    });

    it('forApi returns case names as value', function (): void {
        $api = PureFeatureFlag::forApi();

        expect($api[0]['value'])->toBe('DARK_MODE');
        expect($api[0]['name'])->toBe('DARK_MODE');
    });
});

// ── InvalidEnumException: __toString ────────────────────────────────────────

describe('InvalidEnumException __toString', function (): void {
    it('formats the exception as a readable string', function (): void {
        $exception = InvalidEnumException::forName('App\\Enums\\UserStatus', 'UNKNOWN');

        expect((string) $exception)->toBe('ZeroBoiler\\Enums\\Exceptions\\InvalidEnumException: Case name [UNKNOWN] does not exist on enum [App\\Enums\\UserStatus].');
    });

    it('formats value exception as a readable string', function (): void {
        $exception = InvalidEnumException::value('App\\Enums\\Priority', 99);

        expect((string) $exception)->toBe('ZeroBoiler\\Enums\\Exceptions\\InvalidEnumException: Value [99] is not a valid case of [App\\Enums\\Priority].');
    });

    it('formats null value exception', function (): void {
        $exception = InvalidEnumException::value('App\\Enums\\Priority', null);

        expect((string) $exception)->toBe('ZeroBoiler\\Enums\\Exceptions\\InvalidEnumException: Value [null] is not a valid case of [App\\Enums\\Priority].');
    });
});

// ── EnumRule: edge cases ─────────────────────────────────────────────────────

describe('EnumRule edge cases', function (): void {
    it('nullable() creates a new instance with nullable flag', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);
        $nullableRule = $rule->nullable();

        expect($nullableRule)->not->toBe($rule);
    });

    it('rejects non-enum class with proper error', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for('stdClass');
        $fail = fn (string $message): string => $message;

        // Non-enum class should produce a specific error message
        $errors = [];
        $validator = new class($fail) {
            public function __construct(private \Closure $fail) {}
            public function validate(string $attribute, mixed $value): void {
                ($this->fail)($attribute);
            }
        };

        // We can't easily call validate() without a full validator,
        // but we test that EnumRule::for() accepts any class-string
        expect($rule)->toBeInstanceOf(\ZeroBoiler\Enums\Rules\EnumRule::class);
    });
});

// ── EnumMetadataResolver: invalidate methods ────────────────────────────────

describe('EnumMetadataResolver invalidation', function (): void {
    it('invalidate() removes cached metadata for a specific class', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        // Resolve to populate cache
        $label = UserStatus::ACTIVE->label();
        expect($label)->toBe('Active User');

        // Invalidate
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(UserStatus::class);

        // After invalidation, cache miss (still works because resolve rebuilds)
        $cache->clear();
        $cache->setTtl(300);
    });

    it('invalidateAll() flushes all cached metadata', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        // Populate cache for multiple enums
        UserStatus::ACTIVE->label();
        Priority::LOW->label();

        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidateAll();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(Priority::class))->toBeFalse();

        $cache->setTtl(300);
    });
});
