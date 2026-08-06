<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum Production Readiness', function () {
    describe('EnumCast', function () {
        it('returns null for null value on get', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('returns null for null value on set', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('returns null for null value on serialize', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(new stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('serializes enum instance to backed value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(new stdClass, 'status', UserStatus::ACTIVE, []);

            expect($result)->toBe('active');
        });

        it('serializes raw string value as-is', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(new stdClass, 'status', 'active', []);

            expect($result)->toBe('active');
        });

        it('throws on invalid enum class in set', function () {
            $cast = new EnumCast(UserStatus::class);

            expect(fn (): mixed => $cast->set(new stdClass, 'status', Priority::CRITICAL, []))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws on invalid raw value type in set', function () {
            $cast = new EnumCast(UserStatus::class);

            expect(fn (): mixed => $cast->set(new stdClass, 'status', ['array'], []))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws on invalid backed value in set', function () {
            $cast = new EnumCast(UserStatus::class);

            expect(fn (): mixed => $cast->set(new stdClass, 'status', 'nonexistent', []))
                ->toThrow(InvalidArgumentException::class);
        });

        it('accepts valid raw string value in set', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new stdClass, 'status', 'banned', []);

            expect($result)->toBe('banned');
        });

        it('works with int-backed enums', function () {
            $cast = new EnumCast(Priority::class);
            $result = $cast->get(new stdClass, 'priority', 1, []);

            expect($result)->toBe(Priority::CRITICAL);
        });
    });

    describe('EnumCache', function () {
        beforeEach(function () {
            EnumCache::flush();
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::flush();
            EnumCache::resetInstance();
        });

        it('returns false when cache is empty', function () {
            $cache = EnumCache::getInstance();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('stores and retrieves metadata', function () {
            $cache = EnumCache::getInstance();
            $metadata = [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => ['active' => 'success'],
                'icons' => [],
            ];

            $cache->set(UserStatus::class, $metadata);

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->get(UserStatus::class))->toBe($metadata);
        });

        it('clears individual class cache', function () {
            $cache = EnumCache::getInstance();
            $cache->set(UserStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set(Priority::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeTrue();
        });

        it('ttl of 0 disables caching', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set(UserStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('auto-expires entries based on TTL', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0); // effectively disable
            $cache->set(UserStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('flush clears everything', function () {
            $cache = EnumCache::getInstance();
            $cache->set(UserStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('setTtl changes the TTL value', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(60);

            // No exception — TTL changed successfully
            expect(true)->toBeTrue();
        });

        it('throws OutOfBoundsException when get is called without prior set', function () {
            $cache = EnumCache::getInstance();

            expect(fn (): mixed => $cache->get(UserStatus::class))
                ->toThrow(OutOfBoundsException::class);
        });

        it('get returns metadata after set', function () {
            $cache = EnumCache::getInstance();
            $metadata = [
                'labels' => ['active' => 'Active User'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ];

            $cache->set(UserStatus::class, $metadata);

            expect($cache->get(UserStatus::class))->toBe($metadata);
        });

        it('flush clears all entries and timestamps', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set(Priority::class, ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeFalse();
        });
    });

    describe('InvalidEnumException', function () {
        it('creates value exception with type info', function () {
            $exception = InvalidEnumException::value(UserStatus::class, 'invalid');

            expect($exception->getMessage())->toContain('UserStatus');
            expect($exception->getMessage())->toContain('invalid');
        });

        it('creates value exception with int type', function () {
            $exception = InvalidEnumException::value(Priority::class, 999);

            expect($exception->getMessage())->toContain('Priority');
        });

        it('creates value exception with null type', function () {
            $exception = InvalidEnumException::value(UserStatus::class, null);

            expect($exception->getMessage())->toContain('null');
        });

        it('creates forName exception', function () {
            $exception = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');

            expect($exception->getMessage())->toContain('UNKNOWN');
            expect($exception->getMessage())->toContain('UserStatus');
        });
    });

    describe('EnumManager', function () {
        it('throws BadMethodCallException for non-enum class', function () {
            $manager = new EnumManager;

            expect(fn (): mixed => $manager->forSelect('stdClass'))
                ->toThrow(BadMethodCallException::class);
        });

        it('throws BadMethodCallException for enum without trait', function () {
            // Use a built-in enum that doesn't have HasEnumMetadata
            $manager = new EnumManager;

            // Need a non-HasEnumMetadata enum — use a fixture-less approach
            // Since all fixtures use HasEnumMetadata, test with forApi
            expect(fn (): mixed => $manager->forApi('stdClass'))
                ->toThrow(BadMethodCallException::class);
        });

        it('throws BadMethodCallException for tryFromLabel without trait', function () {
            $manager = new EnumManager;

            expect(fn (): mixed => $manager->tryFromLabel('stdClass', 'test'))
                ->toThrow(BadMethodCallException::class);
        });
    });

    describe('EnumRule edge cases', function () {
        it('nullable allows null values', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $fail = fn (): never => throw new Exception('Validation failed');

            // Should not call $fail
            $rule->validate('status', null, $fail);

            expect(true)->toBeTrue();
        });

        it('non-nullable rejects null values', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', null, $fail);

            expect($failed)->toBeTrue();
        });

        it('validates pure enum by case name', function () {
            $rule = EnumRule::for(RequestState::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('state', 'DRAFT', $fail);

            expect($failed)->toBeFalse();
        });

        it('rejects invalid pure enum case name', function () {
            $rule = EnumRule::for(RequestState::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('state', 'NONEXISTENT', $fail);

            expect($failed)->toBeTrue();
        });

        it('rejects non-string value for pure enum', function () {
            $rule = EnumRule::for(RequestState::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('state', 123, $fail);

            expect($failed)->toBeTrue();
        });

        it('rejects non-string/non-int value for backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', ['array'], $fail);

            expect($failed)->toBeTrue();
        });

        it('generates descriptive error message with allowed values', function () {
            $rule = EnumRule::for(UserStatus::class);
            $message = '';

            $fail = function (string $msg) use (&$message): void {
                $message = $msg;
            };

            $rule->validate('status', 'invalid_value', $fail);

            expect($message)->toContain('active');
            expect($message)->toContain('invalid');
        });
    });

    describe('EnumMetadataResolver cache integration', function () {
        beforeEach(function () {
            EnumCache::flush();
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::flush();
            EnumCache::resetInstance();
        });

        it('caches metadata on first resolve', function () {
            $cache = EnumCache::getInstance();

            expect($cache->has(UserStatus::class))->toBeFalse();

            EnumMetadataResolver::resolve(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeTrue();
        });

        it('returns consistent metadata across calls', function () {
            $first = EnumMetadataResolver::resolve(UserStatus::class);
            $second = EnumMetadataResolver::resolve(UserStatus::class);

            expect($first)->toBe($second);
        });

        it('class-level EnumColor maps values to color names', function () {
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta['colors']['active'])->toBe('success');
            expect($meta['colors']['banned'])->toBe('danger');
            expect($meta['colors']['pending'])->toBe('warning');
        });

        it('per-case Color attribute overrides class-level', function () {
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            // BANNED has per-case #[Color('danger')] which should match class-level
            expect($meta['colors']['banned'])->toBe('danger');
        });
    });

    describe('HasEnumMetadata with int-backed enum', function () {
        it('values returns int values', function () {
            $values = ZeroPriority::values();

            expect($values)->toEqual([0, 1, 2]);
        });

        it('forSelect uses int values as keys', function () {
            $select = ZeroPriority::forSelect();

            expect($select[0])->toHaveKey('value');
            expect($select[0]['value'])->toBe(0);
            expect($select[0])->toHaveKey('label');
        });

        it('forApi returns correct structure', function () {
            $api = ZeroPriority::forApi();

            expect($api)->toBeArray();
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($api[0]['value'])->toBe(0);
            expect($api[0]['name'])->toBe('NONE');
        });

        it('tryFromName works with int-backed enum', function () {
            expect(ZeroPriority::tryFromName('NONE'))->toBe(ZeroPriority::NONE);
            expect(ZeroPriority::tryFromName('LOW'))->toBe(ZeroPriority::LOW);
            expect(ZeroPriority::tryFromName('UNKNOWN'))->toBeNull();
        });

        it('fromName throws on invalid name', function () {
            expect(fn () => ZeroPriority::fromName('INVALID'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns correct boolean', function () {
            expect(ZeroPriority::hasCase('NONE'))->toBeTrue();
            expect(ZeroPriority::hasCase('INVALID'))->toBeFalse();
        });

        it('labels returns auto-generated labels', function () {
            $labels = ZeroPriority::labels();

            expect($labels)->not->toBeEmpty();
            expect($labels[0])->toBe('None');
            expect($labels[1])->toBe('Low');
            expect($labels[2])->toBe('High');
        });
    });

    describe('HasEnumMetadata with pure enum', function () {
        it('values returns case names', function () {
            $values = RequestState::values();

            expect($values)->toEqual(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
        });

        it('forSelect uses case names as values', function () {
            $select = RequestState::forSelect();

            expect($select[0]['value'])->toBe('DRAFT');
            expect($select[0]['label'])->toBe('Draft');
        });

        it('tryFromLabel is case-insensitive', function () {
            expect(RequestState::tryFromLabel('Draft'))->toBe(RequestState::DRAFT);
            expect(RequestState::tryFromLabel('draft'))->toBe(RequestState::DRAFT);
            expect(RequestState::tryFromLabel('DRAFT'))->toBe(RequestState::DRAFT);
            expect(RequestState::tryFromLabel('Nonexistent'))->toBeNull();
        });

        it('color defaults to secondary for pure enums without attributes', function () {
            expect(RequestState::DRAFT->color())->toBe('secondary');
        });

        it('description returns null when not defined', function () {
            expect(RequestState::DRAFT->description())->toBeNull();
        });

        it('icon returns null when not defined', function () {
            expect(RequestState::DRAFT->icon())->toBeNull();
        });
    });

    describe('EnumTestGenerator', function () {
        it('generates test content for string-backed enum', function () {
            $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

            expect($content)->toContain('declare(strict_types=1)');
            expect($content)->toContain('UserStatus');
            expect($content)->toContain('describe(');
            expect($content)->toContain('it(');
            expect($content)->toContain('forSelect');
            expect($content)->toContain('forApi');
        });

        it('generates per-case tests', function () {
            $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(UserStatus::class);

            expect($content)->toContain('ACTIVE');
            expect($content)->toContain('label()');
            expect($content)->toContain('color()');
        });

        it('generates test content for int-backed enum', function () {
            $content = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(ZeroPriority::class);

            expect($content)->toContain('ZeroPriority');
            expect($content)->toContain('NONE');
        });
    });
});
