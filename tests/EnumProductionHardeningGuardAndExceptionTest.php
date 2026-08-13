<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum production hardening — metadata guard, exception factory, and cross-type contract', function () {
    // ── EnumMetadataResolver::resolve() guard ──────────────────────────────

    it('throws LogicException when resolving metadata for a non-enum class', function () {
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
            ->toThrow(\LogicException::class, 'is not a valid enum class');
    });

    it('throws LogicException when resolving metadata for a non-existent class', function () {
        // enum_exists() returns false for non-existent classes,
        // so our guard throws LogicException before ReflectionEnum is reached
        expect(fn () => EnumMetadataResolver::resolve('NonExistentClass'))
            ->toThrow(\LogicException::class, 'is not a valid enum class');
    });

    // ── InvalidEnumException factory methods ────────────────────────────────

    it('InvalidEnumException::value() formats null correctly', function () {
        $e = InvalidEnumException::value('App\Enums\Status', null);

        expect($e->getMessage())->toContain('null');
        expect($e->getMessage())->toContain('App\Enums\Status');
        expect($e->getMessage())->toContain('is not a valid case');
    });

    it('InvalidEnumException::value() formats int value correctly', function () {
        $e = InvalidEnumException::value('App\Enums\Priority', 99);

        expect($e->getMessage())->toContain('99');
    });

    it('InvalidEnumException::value() formats string value correctly', function () {
        $e = InvalidEnumException::value('App\Enums\Status', 'deleted');

        expect($e->getMessage())->toContain('deleted');
    });

    it('InvalidEnumException::forName() includes class and name in message', function () {
        $e = InvalidEnumException::forName('App\Enums\Status', 'UNKNOWN_CASE');

        expect($e->getMessage())->toContain('UNKNOWN_CASE');
        expect($e->getMessage())->toContain('App\Enums\Status');
        expect($e->getMessage())->toContain('does not exist');
    });

    it('InvalidEnumException::__toString() returns class name and message', function () {
        $e = InvalidEnumException::forName('App\Enums\Status', 'BAD');

        $str = (string) $e;
        expect($str)->toContain('InvalidEnumException');
        expect($str)->toContain('BAD');
    });

    // ── Cross-type contract: all fixture enums produce valid metadata ──────

    it('string-backed enum produces valid metadata shape', function () {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        expect($meta['labels'])->toBeArray()->not->toBeEmpty();
        expect($meta['colors'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
        expect($meta['icons'])->toBeArray();
    });

    it('int-backed enum produces valid metadata shape', function () {
        $meta = EnumMetadataResolver::resolve(Priority::class);

        expect($meta['labels'])->toBeArray()->not->toBeEmpty();
    });

    it('int-backed enum with zero value produces valid metadata', function () {
        $meta = EnumMetadataResolver::resolve(ZeroPriority::class);

        expect($meta['labels'])->toHaveKey(0);
        expect($meta['labels'][0])->toBeString()->not->toBeEmpty();
    });

    it('pure enum produces valid metadata shape (no backed values)', function () {
        $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        expect($meta['labels'])->toBeArray()->not->toBeEmpty();
        // Pure enums use case names as keys
        foreach (PureFeatureFlag::cases() as $case) {
            expect($meta['labels'])->toHaveKey($case->name);
        }
    });

    it('single case enum produces valid metadata', function () {
        $meta = EnumMetadataResolver::resolve(SingleCaseEnum::class);

        expect($meta['labels'])->toHaveCount(1);
        expect($meta['colors'])->toBeArray();
    });

    it('class-level label map enum resolves all labels correctly', function () {
        $meta = EnumMetadataResolver::resolve(LabelMapEnum::class);

        expect($meta['labels']['draft'])->toBe('Draft Article');
        expect($meta['labels']['published'])->toBe('Published Article');
        expect($meta['labels']['archived'])->toBe('Archived Article');
        // TRASHED has no class-level label → auto-generated
        expect($meta['labels']['trashed'])->toBe('Trashed');
    });

    it('class-level icon map resolves per-value and default icons', function () {
        $meta = EnumMetadataResolver::resolve(LabelMapEnum::class);

        // published has explicit icon
        expect($meta['icons']['published'])->toBe('heroicon-o-globe');
        // archived falls back to default
        expect($meta['icons']['archived'])->toBe('heroicon-o-document-text');
    });

    // ── Metadata cache invalidation ────────────────────────────────────────

    it('invalidate() removes a single class from cache', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumMetadataResolver::invalidate(UserStatus::class);
        // Re-resolve should work (no stale data)
        $meta = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta['labels'])->not->toBeEmpty();
    });

    it('invalidateAll() clears everything', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumMetadataResolver::invalidateAll();
        // Both should re-resolve cleanly
        expect(EnumMetadataResolver::resolve(UserStatus::class)['labels'])->not->toBeEmpty();
        expect(EnumMetadataResolver::resolve(Priority::class)['labels'])->not->toBeEmpty();
    });

    // ── camelCase label generation ──────────────────────────────────────────

    it('camelCase case names generate human-readable labels', function () {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
        expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
    });

    // ── Zero value edge cases ──────────────────────────────────────────────

    it('zero-backed int enum resolves zero value metadata', function () {
        $zero = ZeroBackedPriority::ZERO;
        expect($zero->label())->toBeString()->not->toBeEmpty();
        expect($zero->color())->toBeString();
    });

    // ── Comparison method cross-type consistency ───────────────────────────

    it('string-backed enum comparison works with all methods', function () {
        $active = UserStatus::ACTIVE;

        // is() with instance
        expect($active->is(UserStatus::ACTIVE))->toBeTrue();
        expect($active->is(UserStatus::BANNED))->toBeFalse();

        // is() with string
        expect($active->is('ACTIVE'))->toBeTrue();
        expect($active->is('BANNED'))->toBeFalse();

        // isNot()
        expect($active->isNot(UserStatus::ACTIVE))->toBeFalse();
        expect($active->isNot('ACTIVE'))->toBeFalse();

        // in()
        expect($active->in([UserStatus::ACTIVE, UserStatus::INACTIVE]))->toBeTrue();
        expect($active->in(['ACTIVE', 'INACTIVE']))->toBeTrue();
        expect($active->in([]))->toBeFalse();

        // notIn()
        expect($active->notIn([UserStatus::BANNED]))->toBeTrue();
        expect($active->notIn(['BANNED']))->toBeTrue();
        expect($active->notIn([UserStatus::ACTIVE]))->toBeFalse();
    });

    it('int-backed enum comparison works with all methods', function () {
        $high = Priority::HIGH;

        expect($high->is(Priority::HIGH))->toBeTrue();
        expect($high->is('HIGH'))->toBeTrue();
        expect($high->in([Priority::HIGH, Priority::LOW]))->toBeTrue();
        expect($high->in(['HIGH', 'LOW']))->toBeTrue();
        expect($high->notIn([Priority::CRITICAL]))->toBeTrue();
    });

    it('pure enum comparison works with all methods', function () {
        $flag = PureFeatureFlag::NEW_DASHBOARD;

        expect($flag->is(PureFeatureFlag::NEW_DASHBOARD))->toBeTrue();
        expect($flag->is('NEW_DASHBOARD'))->toBeTrue();
        expect($flag->in([PureFeatureFlag::NEW_DASHBOARD, PureFeatureFlag::DARK_MODE]))->toBeTrue();
        expect($flag->notIn([PureFeatureFlag::BETA_ACCESS]))->toBeTrue();
    });
});
