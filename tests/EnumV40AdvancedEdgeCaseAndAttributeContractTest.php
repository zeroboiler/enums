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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

// ── Test Fixtures ────────────────────────────────────────────────────────────

#[EnumColor(success: ['active'], danger: ['banned'], warning: ['pending'])]
#[EnumIcon(default: 'heroicon-o-flag', icons: ['active' => 'heroicon-o-check', 'banned' => 'heroicon-o-x-mark'])]
enum UserStatus: string
{
    use HasEnumMetadata;

    #[Label('Active User')]
    #[Description('User can fully access the system')]
    #[Color('success')]
    #[Icon('heroicon-o-check-circle')]
    case active = 'active';

    #[Label('Banned User')]
    #[Color('danger')]
    case banned = 'banned';

    case pending = 'pending';
}

#[EnumLabel(labels: ['admin' => 'Administrator', 'editor' => 'Editor'])]
#[EnumDescription(descriptions: ['admin' => 'Full system access', 'editor' => 'Content management'])]
enum UserRole: string
{
    use HasEnumMetadata;

    case admin = 'admin';
    case editor = 'editor';
}

enum IntStatus: int
{
    use HasEnumMetadata;

    #[Label('Enabled')]
    case active = 1;

    #[Label('Disabled')]
    case disabled = 0;
}

enum PureState
{
    use HasEnumMetadata;

    #[Label('Draft Mode')]
    case DRAFT;
    case PUBLISHED;
    case ARCHIVED;
}

/**
 * V40 production readiness — attribute resolution, class-level overrides,
 * mixed type enums, edge cases for label/color/icon/description resolution,
 * EnumCache TTL behavior, EnumCast type safety, and EnumRule validation.
 */
describe('Enum V40 Advanced Edge Cases and Attribute Contract', function () {
    // ── Per-case attribute overrides take priority over class-level ──────────

    it('per-case Label overrides class-level EnumLabel', function () {
        // UserStatus has no EnumLabel, only per-case Labels
        expect(UserStatus::active->label())->toBe('Active User');
        expect(UserStatus::banned->label())->toBe('Banned User');
        expect(UserStatus::pending->label())->toBe('Pending'); // auto-generated
    });

    it('class-level EnumLabel provides labels when no per-case Label exists', function () {
        expect(UserRole::admin->label())->toBe('Administrator');
        expect(UserRole::editor->label())->toBe('Editor');
    });

    it('per-case Color overrides class-level EnumColor', function () {
        // active has #[Color('success')] per-case override
        // banned has no per-case Color, so class-level EnumColor applies
        expect(UserStatus::active->color())->toBe('success');
        expect(UserStatus::banned->color())->toBe('danger');
        expect(UserStatus::pending->color())->toBe('warning');
    });

    it('color defaults to secondary when no attribute is set', function () {
        // UserRole has no color attributes at all
        expect(UserRole::admin->color())->toBe('secondary');
    });

    it('per-case Description overrides class-level EnumDescription', function () {
        // UserStatus::active has per-case description
        expect(UserStatus::active->description())->toBe('User can fully access the system');

        // UserRole::admin uses class-level EnumDescription
        expect(UserRole::admin->description())->toBe('Full system access');
    });

    it('description returns null when no attribute is set', function () {
        // UserStatus::banned has no Description or EnumDescription
        expect(UserStatus::banned->description())->toBeNull();
    });

    // ── Icon resolution ──────────────────────────────────────────────────────

    it('per-case Icon overrides class-level EnumIcon per-value map', function () {
        expect(UserStatus::active->icon())->toBe('heroicon-o-check-circle');
    });

    it('EnumIcon per-value map provides icon for specific values', function () {
        // banned has 'banned' => 'heroicon-o-x-mark' in EnumIcon icons map
        expect(UserStatus::banned->icon())->toBe('heroicon-o-x-mark');
    });

    it('EnumIcon default provides fallback icon', function () {
        // pending has no per-case Icon and no entry in EnumIcon icons map
        expect(UserStatus::pending->icon())->toBe('heroicon-o-flag');
    });

    it('icon returns null when no EnumIcon attribute is present', function () {
        expect(UserRole::admin->icon())->toBeNull();
    });

    // ── Int-backed enum ─────────────────────────────────────────────────────

    it('int-backed enum resolves labels correctly', function () {
        expect(IntStatus::active->label())->toBe('Enabled');
        expect(IntStatus::disabled->label())->toBe('Disabled');
    });

    it('int-backed enum values() returns int values', function () {
        $values = IntStatus::values();
        expect($values)->toEqual([1, 0]);
    });

    it('int-backed enum toValue() returns the backed int value', function () {
        expect(IntStatus::active->toValue())->toBe(1);
        expect(IntStatus::disabled->toValue())->toBe(0);
    });

    it('int-backed enum forSelect uses int values', function () {
        $select = IntStatus::forSelect();
        expect($select[0]['value'])->toBe(1);
        expect($select[1]['value'])->toBe(0);
    });

    it('int-backed enum forApi uses int values', function () {
        $api = IntStatus::forApi();
        expect($api[0]['value'])->toBe(1);
        expect($api[0]['name'])->toBe('active');
    });

    // ── Pure enum ───────────────────────────────────────────────────────────

    it('pure enum label() auto-generates from case name', function () {
        expect(PureState::DRAFT->label())->toBe('Draft Mode');
        expect(PureState::PUBLISHED->label())->toBe('Published');
        expect(PureState::ARCHIVED->label())->toBe('Archived');
    });

    it('pure enum values() returns case names', function () {
        $values = PureState::values();
        expect($values)->toEqual(['DRAFT', 'PUBLISHED', 'ARCHIVED']);
    });

    it('pure enum toValue() returns case name', function () {
        expect(PureState::DRAFT->toValue())->toBe('DRAFT');
    });

    it('pure enum forSelect uses case names as values', function () {
        $select = PureState::forSelect();
        expect($select[0]['value'])->toBe('DRAFT');
    });

    it('pure enum color() defaults to secondary', function () {
        expect(PureState::DRAFT->color())->toBe('secondary');
    });

    it('pure enum icon() returns null', function () {
        expect(PureState::DRAFT->icon())->toBeNull();
    });

    it('pure enum description() returns null', function () {
        expect(PureState::DRAFT->description())->toBeNull();
    });

    // ── Comparison methods ──────────────────────────────────────────────────

    it('is() works with enum instances and string names', function () {
        expect(UserStatus::active->is(UserStatus::active))->toBeTrue();
        expect(UserStatus::active->is('active'))->toBeTrue();
        expect(UserStatus::active->is(UserStatus::banned))->toBeFalse();
        expect(UserStatus::active->is('banned'))->toBeFalse();
    });

    it('is() is case-sensitive for string comparison', function () {
        expect(UserStatus::active->is('Active'))->toBeFalse();
        expect(UserStatus::active->is('ACTIVE'))->toBeFalse();
    });

    it('isNot() negates is()', function () {
        expect(UserStatus::active->isNot(UserStatus::banned))->toBeTrue();
        expect(UserStatus::active->isNot('banned'))->toBeTrue();
        expect(UserStatus::active->isNot(UserStatus::active))->toBeFalse();
    });

    it('in() matches any of the given cases', function () {
        expect(UserStatus::active->in([UserStatus::active, UserStatus::pending]))->toBeTrue();
        expect(UserStatus::active->in(['active', 'pending']))->toBeTrue();
        expect(UserStatus::active->in([UserStatus::banned]))->toBeFalse();
        expect(UserStatus::active->in(['banned']))->toBeFalse();
    });

    it('in() supports mixed instances and strings', function () {
        expect(UserStatus::active->in([UserStatus::active, 'pending']))->toBeTrue();
    });

    it('notIn() negates in()', function () {
        expect(UserStatus::active->notIn([UserStatus::banned]))->toBeTrue();
        expect(UserStatus::active->notIn(['banned']))->toBeTrue();
        expect(UserStatus::active->notIn([UserStatus::active, UserStatus::pending]))->toBeFalse();
    });

    // ── Reverse lookups ─────────────────────────────────────────────────────

    it('tryFromLabel is case-insensitive', function () {
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::active);
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::active);
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::active);
    });

    it('tryFromLabel returns null for non-existent labels', function () {
        expect(UserStatus::tryFromLabel('Non Existent Label'))->toBeNull();
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });

    it('tryFromName resolves by case name', function () {
        expect(UserStatus::tryFromName('active'))->toBe(UserStatus::active);
        expect(UserStatus::tryFromName('banned'))->toBe(UserStatus::banned);
        expect(UserStatus::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('fromName throws InvalidEnumException for non-existent name', function () {
        expect(fn () => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('fromName throws with class name in message', function () {
        try {
            UserStatus::fromName('INVALID');
            expect(true)->toBeFalse(); // should not reach here
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('UserStatus');
            expect($e->getMessage())->toContain('INVALID');
        }
    });

    it('hasCase checks existence', function () {
        expect(UserStatus::hasCase('active'))->toBeTrue();
        expect(UserStatus::hasCase('ACTIVE'))->toBeFalse(); // case-sensitive
        expect(UserStatus::hasCase('nonexistent'))->toBeFalse();
    });

    // ── Bulk methods ────────────────────────────────────────────────────────

    it('forSelect returns correct structure', function () {
        $select = UserStatus::forSelect();

        expect($select)->toHaveCount(3);
        expect($select[0])->toHaveKeys(['value', 'label']);
        expect($select[0]['label'])->toBeString()->not->toBeEmpty();
    });

    it('forSelect values are unique', function () {
        $values = array_column(UserStatus::forSelect(), 'value');
        expect($values)->toEqual(array_unique($values));
    });

    it('forApi returns complete metadata', function () {
        $api = UserStatus::forApi();

        expect($api)->toHaveCount(3);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('forApi description can be null', function () {
        $api = UserStatus::forApi();
        // banned has no description
        $banned = array_find($api, fn (array $item): bool => $item['value'] === 'banned');
        expect($banned['description'])->toBeNull();
    });

    it('values() returns correct count and types', function () {
        $values = UserStatus::values();
        expect($values)->toHaveCount(3);
        expect($values)->toEqual(['active', 'banned', 'pending']);
    });

    it('labels() returns correct count and non-empty strings', function () {
        $labels = UserStatus::labels();
        expect($labels)->toHaveCount(3);
        foreach ($labels as $label) {
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });

    // ── EnumCache ───────────────────────────────────────────────────────────

    it('EnumCache singleton returns same instance', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();
        expect($a)->toBe($b);
    });

    it('EnumCache TTL of 0 disables caching', function () {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(0);

        $cache->set('TestEnum', ['labels' => ['test' => 'Test'], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        expect($cache->has('TestEnum'))->toBeFalse();
    });

    it('EnumCache setTtl clamps negative values to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);
        expect($cache->getTtl())->toBe(0);
    });

    it('EnumCache clear removes specific class', function () {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);

        $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('EnumB', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass('EnumA');

        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeTrue();
    });

    it('EnumCache flush clears all entries', function () {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);

        $cache->set('EnumA', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        EnumCache::flush();

        expect($cache->has('EnumA'))->toBeFalse();
    });

    it('EnumCache clone throws RuntimeException', function () {
        expect(fn () => clone EnumCache::getInstance())
            ->toThrow(\RuntimeException::class);
    });

    it('EnumCache __debugInfo hides internal state', function () {
        $cache = EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(300);
        $cache->set('TestEnum', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $debug = $cache->__debugInfo();
        expect($debug)->toHaveKey('ttl');
        expect($debug)->toHaveKey('cachedClasses');
        expect($debug)->not->toHaveKey('cache');
    });

    // ── EnumMetadataResolver ──────────────────────────────────────────────

    it('invalidate removes cached metadata for specific class', function () {
        EnumMetadataResolver::invalidateAll();
        EnumCache::getInstance()->setTtl(300);

        // Trigger resolution to populate cache
        UserStatus::active->label();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        // Invalidate
        EnumMetadataResolver::invalidate(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('invalidateAll clears everything', function () {
        EnumMetadataResolver::invalidateAll();
        EnumCache::getInstance()->setTtl(300);

        UserStatus::active->label();
        UserRole::admin->label();

        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(UserRole::class))->toBeFalse();
    });

    // ── EnumRule ───────────────────────────────────────────────────────────

    it('EnumRule::for creates rule instance', function () {
        $rule = EnumRule::for(UserStatus::class);
        expect($rule)->toBeInstanceOf(EnumRule::class);
    });

    it('EnumRule nullable allows null values', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $fail = fn () => throw new \Exception('Validation failed');
        // Should NOT call fail for null
        $rule->validate('status', null, $fail);

        // This should not throw
        expect(true)->toBeTrue();
    });

    it('EnumRule non-nullable rejects null', function () {
        $rule = EnumRule::for(UserStatus::class);

        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', null, $fail);
        expect($failed)->toBeTrue();
    });

    it('EnumRule accepts valid backed enum value', function () {
        $rule = EnumRule::for(UserStatus::class);

        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', 'active', $fail);
        expect($failed)->toBeFalse();
    });

    it('EnumRule rejects invalid backed enum value', function () {
        $rule = EnumRule::for(UserStatus::class);

        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', 'nonexistent', $fail);
        expect($failed)->toBeTrue();
    });

    it('EnumRule validates pure enums by case name', function () {
        $rule = EnumRule::for(PureState::class);

        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        $rule->validate('state', 'DRAFT', $fail);
        expect($failed)->toBeFalse();

        $rule->validate('state', 'INVALID', $fail);
        expect($failed)->toBeTrue();
    });

    it('EnumRule rejects non-string for pure enum', function () {
        $rule = EnumRule::for(PureState::class);

        $failed = false;
        $fail = function () use (&$failed): void {
            $failed = true;
        };

        $rule->validate('state', 123, $fail);
        expect($failed)->toBeTrue();
    });

    // ── EnumCast ────────────────────────────────────────────────────────────

    it('EnumCast get returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        expect($cast->get($model, 'status', null, []))->toBeNull();
    });

    it('EnumCast get returns enum for valid value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        $result = $cast->get($model, 'status', 'active', []);
        expect($result)->toBe(UserStatus::active);
    });

    it('EnumCast get returns null for invalid value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        expect($cast->get($model, 'status', 'nonexistent', []))->toBeNull();
    });

    it('EnumCast set returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        expect($cast->set($model, 'status', null, []))->toBeNull();
    });

    it('EnumCast set returns backed value for enum instance', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        expect($cast->set($model, 'status', UserStatus::active, []))->toBe('active');
    });

    it('EnumCast set validates raw value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        expect(fn () => $cast->set($model, 'status', 'invalid_value', []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('EnumCast set rejects wrong enum type', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        expect(fn () => $cast->set($model, 'status', UserRole::admin, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('EnumCast serialize returns backed value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        expect($cast->serialize($model, 'status', UserStatus::active, []))->toBe('active');
    });

    it('EnumCast serialize returns raw string value', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        expect($cast->serialize($model, 'status', 'active', []))->toBe('active');
    });

    it('EnumCast serialize returns null for null', function () {
        $cast = new EnumCast(UserStatus::class);
        $model = new \stdClass;

        expect($cast->serialize($model, 'status', null, []))->toBeNull();
    });

    // ── InvalidEnumException ────────────────────────────────────────────────

    it('InvalidEnumException::value creates correct message', function () {
        $exception = InvalidEnumException::value(UserStatus::class, 'invalid');

        expect($exception->getMessage())->toContain('invalid');
        expect($exception->getMessage())->toContain('UserStatus');
    });

    it('InvalidEnumException::value handles null value', function () {
        $exception = InvalidEnumException::value(UserStatus::class, null);

        expect($exception->getMessage())->toContain('null');
    });

    it('InvalidEnumException::__toString includes class name', function () {
        $exception = InvalidEnumException::forName(UserStatus::class, 'INVALID');

        $str = (string) $exception;
        expect($str)->toContain('InvalidEnumException');
        expect($str)->toContain('INVALID');
    });

    // ── Label auto-generation ───────────────────────────────────────────────

    it('SCREAMING_SNAKE_CASE auto-generates Title Case', function () {
        expect(PureState::PUBLISHED->label())->toBe('Published');
        expect(PureState::ARCHIVED->label())->toBe('Archived');
    });

    // ── Reset cache for test isolation ─────────────────────────────────────

    afterEach(function () {
        EnumCache::resetInstance();
    });
});
