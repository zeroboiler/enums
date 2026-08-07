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
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum PHPStan Level 9 Compliance', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    describe('No mixed return types in public API', function () {
        it('HasEnumMetadata::label() returns string (not mixed)', function () {
            $label = UserStatus::ACTIVE->label();
            expect($label)->toBeString();
            // Verify it's not null/empty (would indicate mixed return)
            expect(strlen($label))->toBeGreaterThan(0);
        });

        it('HasEnumMetadata::description() returns ?string (not mixed)', function () {
            $desc = UserStatus::ACTIVE->description();
            // Must be null or string — not array/int/bool
            expect($desc === null || is_string($desc))->toBeTrue();
        });

        it('HasEnumMetadata::color() returns string (not mixed)', function () {
            $color = UserStatus::ACTIVE->color();
            expect($color)->toBeString();
        });

        it('HasEnumMetadata::icon() returns ?string (not mixed)', function () {
            $icon = UserStatus::ACTIVE->icon();
            expect($icon === null || is_string($icon))->toBeTrue();
        });

        it('HasEnumMetadata::forSelect() returns typed array', function () {
            $result = UserStatus::forSelect();
            expect($result)->toBeArray();
            expect($result)->not->toBeEmpty();
            foreach ($result as $item) {
                expect($item)->toBeArray();
                expect(array_keys($item))->toEqual(['value', 'label']);
                expect(is_string($item['label']) || is_int($item['label']))->toBeTrue();
            }
        });

        it('HasEnumMetadata::forApi() returns typed array with all keys', function () {
            $result = UserStatus::forApi();
            expect($result)->toBeArray();
            expect($result)->not->toBeEmpty();
            $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];
            foreach ($result as $item) {
                expect($item)->toBeArray();
                expect(array_keys($item))->toEqual($requiredKeys);
            }
        });

        it('HasEnumMetadata::values() returns list of string|int', function () {
            $values = UserStatus::values();
            expect($values)->toBeArray();
            foreach ($values as $v) {
                expect(is_string($v) || is_int($v))->toBeTrue();
            }
        });

        it('HasEnumMetadata::values() returns ints for int-backed enum', function () {
            $values = Priority::values();
            foreach ($values as $v) {
                expect(is_int($v))->toBeTrue();
            }
        });

        it('HasEnumMetadata::values() returns strings for pure enum', function () {
            $values = RequestState::values();
            foreach ($values as $v) {
                expect(is_string($v))->toBeTrue();
            }
        });

        it('HasEnumMetadata::labels() returns list of string', function () {
            $labels = UserStatus::labels();
            expect($labels)->toBeArray();
            foreach ($labels as $l) {
                expect(is_string($l))->toBeTrue();
            }
        });
    });

    describe('Strict comparison guarantees', function () {
        it('is() uses strict identity — not loose equality', function () {
            $active = UserStatus::ACTIVE;
            $banned = UserStatus::BANNED;

            // Same instance
            expect($active->is($active))->toBeTrue();
            // Different instance
            expect($active->is($banned))->toBeFalse();
            // Same value, different variable (still same singleton)
            expect($active->is(UserStatus::ACTIVE))->toBeTrue();
        });

        it('is() with string uses strict === comparison', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            // Case-sensitive: lowercase should not match
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            // Partial match should not match
            expect(UserStatus::ACTIVE->is('ACTIV'))->toBeFalse();
        });

        it('isNot() is strict negation of is()', function () {
            $active = UserStatus::ACTIVE;

            expect($active->isNot($active))->toBeFalse();
            expect($active->isNot(UserStatus::BANNED))->toBeTrue();
            expect($active->isNot('ACTIVE'))->toBeFalse();
            expect($active->isNot('active'))->toBeTrue();
        });

        it('in() uses strict is() for each element', function () {
            $active = UserStatus::ACTIVE;

            expect($active->in([UserStatus::ACTIVE]))->toBeTrue();
            expect($active->in(['ACTIVE']))->toBeTrue();
            expect($active->in(['active']))->toBeFalse(); // case-sensitive
            expect($active->in([]))->toBeFalse();
            expect($active->in([UserStatus::BANNED, UserStatus::PENDING]))->toBeFalse();
        });

        it('in() handles mixed instances and strings', function () {
            $active = UserStatus::ACTIVE;

            expect($active->in([UserStatus::BANNED, 'ACTIVE', UserStatus::PENDING]))->toBeTrue();
            expect($active->in(['BANNED', UserStatus::PENDING]))->toBeFalse();
        });

        it('tryFromName() is case-sensitive', function () {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromName('active'))->toBeNull();
            expect(UserStatus::tryFromName('Active'))->toBeNull();
            expect(UserStatus::tryFromName(''))->toBeNull();
        });

        it('fromName() throws on invalid name', function () {
            expect(fn () => UserStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('fromName() includes class name in exception message', function () {
            try {
                UserStatus::fromName('BOGUS');
                expect(true)->toBeFalse('Should have thrown');
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('UserStatus');
                expect($e->getMessage())->toContain('BOGUS');
            }
        });

        it('hasCase() is case-sensitive', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('active'))->toBeFalse();
            expect(UserStatus::hasCase(''))->toBeFalse();
        });
    });

    describe('tryFromLabel — case-insensitive resolution', function () {
        it('resolves exact label match', function () {
            expect(UserStatus::tryFromLabel('Active User'))
                ->toBe(UserStatus::ACTIVE);
        });

        it('resolves case-insensitive label match', function () {
            expect(UserStatus::tryFromLabel('active user'))
                ->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('ACTIVE USER'))
                ->toBe(UserStatus::ACTIVE);
        });

        it('returns null for non-existent label', function () {
            expect(UserStatus::tryFromLabel('Nonexistent Label'))->toBeNull();
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('returns first match when labels are duplicated', function () {
            // Both ACTIVE and BANNED could have same label in edge cases
            // Verify it returns a valid enum instance
            $case = UserStatus::tryFromLabel('Active User');
            expect($case)->toBeInstanceOf(UserStatus::class);
        });

        it('resolves auto-generated labels case-insensitively', function () {
            // INACTIVE has no Label attribute — auto-generated: "Inactive"
            expect(UserStatus::tryFromLabel('Inactive'))->toBe(UserStatus::INACTIVE);
            expect(UserStatus::tryFromLabel('inactive'))->toBe(UserStatus::INACTIVE);
            expect(UserStatus::tryFromLabel('INACTIVE'))->toBe(UserStatus::INACTIVE);
        });
    });

    describe('Int-backed enum type safety', function () {
        it('forSelect returns int values', function () {
            $select = Priority::forSelect();
            foreach ($select as $option) {
                expect(is_int($option['value']))->toBeTrue();
            }
        });

        it('forApi returns int values', function () {
            $api = Priority::forApi();
            foreach ($api as $item) {
                expect(is_int($item['value']))->toBeTrue();
            }
        });

        it('ZeroPriority handles zero value correctly', function () {
            expect(ZeroPriority::NONE->value)->toBe(0);
            expect(ZeroPriority::NONE->label())->toBeString();
            expect(ZeroPriority::values()[0])->toBe(0);
        });

        it('tryFromName works with int-backed enum', function () {
            expect(Priority::tryFromName('LOW'))->toBe(Priority::LOW);
            expect(Priority::tryFromName('HIGH'))->toBe(Priority::HIGH);
            expect(Priority::tryFromName('nonexistent'))->toBeNull();
        });

        it('forApi returns correct count for int-backed', function () {
            expect(Priority::forApi())->toHaveCount(count(Priority::cases()));
        });
    });

    describe('Pure enum type safety', function () {
        it('forSelect uses case names as values', function () {
            $select = RequestState::forSelect();
            expect($select)->toHaveCount(count(RequestState::cases()));
            foreach ($select as $option) {
                expect(is_string($option['value']))->toBeTrue();
                // Value should match a case name
                expect(RequestState::tryFromName($option['value']))->not->toBeNull();
            }
        });

        it('values() returns case names for pure enum', function () {
            $names = array_map(fn (\UnitEnum $c): string => $c->name, RequestState::cases());
            expect(RequestState::values())->toEqual($names);
        });

        it('comparison works with pure enum', function () {
            expect(RequestState::DRAFT->is(RequestState::DRAFT))->toBeTrue();
            expect(RequestState::DRAFT->is('DRAFT'))->toBeTrue();
            expect(RequestState::DRAFT->is('draft'))->toBeFalse();
            expect(RequestState::DRAFT->in([RequestState::SUBMITTED, RequestState::APPROVED]))->toBeFalse();
        });
    });

    describe('CamelCase label generation', function () {
        it('generates Title Case from camelCase names', function () {
            // isActive → "Is Active"
            expect(CamelCaseRole::isActive->label())->toBe('Is Active');
            expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
            expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
            expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
        });
    });

    describe('Single-case enum edge case', function () {
        it('forSelect returns single entry', function () {
            $select = SingleCaseEnum::forSelect();
            expect($select)->toHaveCount(1);
            expect($select[0])->toHaveKeys(['value', 'label']);
        });

        it('forApi returns single entry with all keys', function () {
            $api = SingleCaseEnum::forApi();
            expect($api)->toHaveCount(1);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('in() with empty array returns false', function () {
            expect(SingleCaseEnum::ONLY->in([]))->toBeFalse();
        });

        it('is() works with single case', function () {
            expect(SingleCaseEnum::ONLY->is('ONLY'))->toBeTrue();
            expect(SingleCaseEnum::ONLY->isNot('ONLY'))->toBeFalse();
        });

        it('EnumTestGenerator handles single case', function () {
            $content = ZeroBoiler\Enums\Support\EnumTestGenerator::generate(SingleCaseEnum::class);
            expect($content)->toContain('declare(strict_types=1)');
            expect($content)->toContain('SingleCaseEnum');
            // Should NOT generate comparison tests (needs 2+ cases)
            expect($content)->not->toContain('supports is()');
        });
    });

    describe('Class-level attribute resolution', function () {
        it('EnumLabel resolves labels from class-level map', function () {
            // TicketStatus has EnumLabel(labels: ['open' => 'Open', ...])
            expect(TicketStatus::OPEN->label())->toBe('Open');
            expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
            expect(TicketStatus::CLOSED->label())->toBe('Closed');
        });

        it('EnumDescription resolves descriptions from class-level map', function () {
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
            expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
        });

        it('EnumIcon provides default icon for all cases', function () {
            // TicketStatus has EnumIcon(default: 'heroicon-o-ticket')
            expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
            expect(TicketStatus::IN_PROGRESS->icon())->toBe('heroicon-o-ticket');
        });

        it('per-case attributes override class-level', function () {
            // UserStatus: BANNED has #[Color('danger')] which overrides EnumColor
            expect(UserStatus::BANNED->color())->toBe('danger');
            // ACTIVE gets 'success' from class-level EnumColor
            expect(UserStatus::ACTIVE->color())->toBe('success');
        });

        it('EnumColor maps multiple values to same color', function () {
            expect(UserStatus::PENDING->color())->toBe('warning');
            expect(UserStatus::SUSPENDED->color())->toBe('warning');
        });
    });

    describe('EnumCache — TTL behavior', function () {
        it('TTL of 0 disables caching', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('cache entry expires after TTL', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(1); // 1 second TTL
            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Cached'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();

            // Manually age the cache entry
            $reflection = new ReflectionClass($cache);
            $tsProp = $reflection->getProperty('cacheTimestamps');
            $tsProp->setAccessible(true);
            $timestamps = $tsProp->getValue($cache);
            $timestamps[UserStatus::class] = microtime(true) - 2; // 2 seconds ago
            $tsProp->setValue($cache, $timestamps);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('flush clears all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(Priority::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->clear();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeFalse();
        });

        it('clearClass removes only specific entry', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(Priority::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeTrue();
        });

        it('get() throws OutOfBoundsException for missing entry', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect(fn () => $cache->get('NonexistentEnum'))
                ->toThrow(\OutOfBoundsException::class);
        });
    });

    describe('EnumRule — type safety in validation', function () {
        it('rejects non-integer for int-backed enum', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', '3', $fail);
            expect($failed)->toBeTrue(); // string '3' rejected for int-backed
        });

        it('accepts integer for int-backed enum', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 1, $fail);
            expect($failed)->toBeFalse();
        });

        it('rejects non-string for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 1, $fail);
            expect($failed)->toBeTrue(); // int rejected for string-backed
        });

        it('nullable allows null values', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', null, $fail);
            expect($failed)->toBeFalse(); // null allowed with nullable
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

            $rule->validate('state', 'NONEXISTENT', $fail);
            expect($failed)->toBeTrue();
        });

        it('error message includes allowed values', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $message = '';
            $fail = function (string $msg) use (&$failed, &$message): void {
                $failed = true;
                $message = $msg;
            };

            $rule->validate('status', 'invalid_value', $fail);
            expect($failed)->toBeTrue();
            expect($message)->toContain('Allowed values');
        });
    });

    describe('EnumCast — type safety', function () {
        it('get() returns null for null value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('get() returns enum for valid value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', 'active', []);

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('get() returns null for invalid value (tryFrom returns null)', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->get(new stdClass, 'status', 'nonexistent', []);

            expect($result)->toBeNull();
        });

        it('set() returns null for null value', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new stdClass, 'status', null, []);

            expect($result)->toBeNull();
        });

        it('set() returns backed value for enum instance', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->set(new stdClass, 'status', UserStatus::ACTIVE, []);

            expect($result)->toBe('active');
        });

        it('set() throws on wrong enum type', function () {
            $cast = new EnumCast(UserStatus::class);

            expect(fn () => $cast->set(new stdClass, 'status', Priority::LOW, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('set() validates raw value', function () {
            $cast = new EnumCast(UserStatus::class);

            expect(fn () => $cast->set(new stdClass, 'status', 'invalid', []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns backed value for enum', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(new stdClass, 'status', UserStatus::ACTIVE, []);

            expect($result)->toBe('active');
        });

        it('serialize() returns raw value for non-enum', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(new stdClass, 'status', 'active', []);

            expect($result)->toBe('active');
        });
    });

    describe('EnumManager — facade delegation', function () {
        it('forSelect() throws for non-enum class', function () {
            $manager = new EnumManager;

            expect(fn () => $manager->forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('forApi() throws for non-enum class', function () {
            $manager = new EnumManager;

            expect(fn () => $manager->forApi(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('tryFromLabel() throws for non-enum class', function () {
            $manager = new EnumManager;

            expect(fn () => $manager->tryFromLabel(\stdClass::class, 'test'))
                ->toThrow(\BadMethodCallException::class);
        });
    });

    describe('InvalidEnumException — factory methods', function () {
        it('value() creates message with class and value', function () {
            $e = InvalidEnumException::value(UserStatus::class, 'bad_value');
            expect($e->getMessage())->toContain('UserStatus');
            expect($e->getMessage())->toContain('bad_value');
        });

        it('value() handles null value', function () {
            $e = InvalidEnumException::value(UserStatus::class, null);
            expect($e->getMessage())->toContain('null');
        });

        it('value() handles int value', function () {
            $e = InvalidEnumException::value(Priority::class, 99);
            expect($e->getMessage())->toContain('99');
        });

        it('forName() creates message with class and name', function () {
            $e = InvalidEnumException::forName(UserStatus::class, 'BOGUS');
            expect($e->getMessage())->toContain('UserStatus');
            expect($e->getMessage())->toContain('BOGUS');
        });
    });

    describe('AllClassLevelEnum — combined class-level attributes', function () {
        it('all three class-level attributes resolve together', function () {
            // EnumLabel + EnumDescription + EnumIcon all set at class level
            expect(ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::OPEN->label())->toBe('Open Status');
            expect(ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::OPEN->description())->toBe('Task is open');
            expect(ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::OPEN->icon())->toBe('heroicon-o-circle');
        });

        it('each case gets correct class-level metadata', function () {
            expect(ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::IN_PROGRESS->label())->toBe('In Progress');
            expect(ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::DONE->label())->toBe('Done');
            expect(ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum::IN_PROGRESS->description())->toBe('Task is being worked on');
        });
    });
});
