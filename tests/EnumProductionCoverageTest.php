<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum production readiness coverage', function () {
    // -----------------------------------------------------------------------
    // EnumCache singleton isolation
    // -----------------------------------------------------------------------
    describe('EnumCache singleton behavior', function () {
        beforeEach(function () {
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::resetInstance();
        });

        it('returns the same instance on multiple calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('creates a fresh instance after resetInstance', function () {
            $a = EnumCache::getInstance();
            $a->set('test', [
                'labels' => ['x' => 'X'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            EnumCache::resetInstance();
            $b = EnumCache::getInstance();

            expect($b)->not->toBe($a);
            expect($b->has('test'))->toBeFalse();
        });

        it('flush clears all entries via static method', function () {
            $cache = EnumCache::getInstance();
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(OrderStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(OrderStatus::class))->toBeFalse();
        });

        it('clearClass removes only the specified class', function () {
            $cache = EnumCache::getInstance();
            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(OrderStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(OrderStatus::class))->toBeTrue();
        });

        it('get throws OutOfBoundsException when no entry exists', function () {
            EnumCache::getInstance()->get('NonExistentEnum');
        })->throws(\OutOfBoundsException::class, 'No cached metadata for [NonExistentEnum]');

        it('setTtl normalizes negative values to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-100);

            // After setting negative TTL, entries should always be stale
            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('entries survive within TTL', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->get(UserStatus::class))
                ->toBeArray()
                ->toHaveKey('labels');
        });
    });

    // -----------------------------------------------------------------------
    // InvalidEnumException factory methods
    // -----------------------------------------------------------------------
    describe('InvalidEnumException factories', function () {
        it('creates value exception with type info', function () {
            $ex = InvalidEnumException::value(UserStatus::class, 42);

            expect($ex)->toBeInstanceOf(InvalidEnumException::class);
            expect($ex->getMessage())->toContain('UserStatus');
            expect($ex->getMessage())->toContain('int');
        });

        it('creates value exception with string', function () {
            $ex = InvalidEnumException::value(UserStatus::class, 'invalid');

            expect($ex->getMessage())->toContain('string');
        });

        it('creates value exception with null', function () {
            $ex = InvalidEnumException::value(UserStatus::class, null);

            expect($ex->getMessage())->toContain('null');
        });

        it('creates forName exception with class and name', function () {
            $ex = InvalidEnumException::forName(UserStatus::class, 'NONEXISTENT');

            expect($ex->getMessage())->toContain('NONEXISTENT');
            expect($ex->getMessage())->toContain('UserStatus');
        });
    });

    // -----------------------------------------------------------------------
    // EnumRule with different enum types
    // -----------------------------------------------------------------------
    describe('EnumRule with backed enums', function () {
        it('validates valid string-backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = fn (string $msg): string => $msg;

            $error = null;
            $rule->validate('status', 'active', function (string $message) use (&$error): void {
                $error = $message;
            });

            expect($error)->toBeNull();
        });

        it('rejects invalid string-backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);

            $rule->validate('status', 'nonexistent', function (string $message): void {
                expect($message)->toBeString();
                expect($message)->toContain('status');
            });
        });

        it('validates valid int-backed enum value', function () {
            $rule = EnumRule::for(Priority::class);

            $error = null;
            $rule->validate('priority', 1, function (string $message) use (&$error): void {
                $error = $message;
            });

            expect($error)->toBeNull();
        });

        it('rejects wrong type for int-backed enum', function () {
            $rule = EnumRule::for(Priority::class);

            $rule->validate('priority', 'not-an-int', function (string $message): void {
                expect($message)->toContain('priority');
            });
        });

        it('rejects null when not nullable', function () {
            $rule = EnumRule::for(UserStatus::class);

            $rule->validate('status', null, function (string $message): void {
                expect($message)->toBeString();
            });
        });

        it('accepts null when nullable', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();

            $error = null;
            $rule->validate('status', null, function (string $message) use (&$error): void {
                $error = $message;
            });

            expect($error)->toBeNull();
        });

        it('validates zero value for int-backed enum', function () {
            $rule = EnumRule::for(ZeroPriority::class);

            $error = null;
            $rule->validate('priority', 0, function (string $message) use (&$error): void {
                $error = $message;
            });

            expect($error)->toBeNull();
        });
    });

    // -----------------------------------------------------------------------
    // forApi completeness
    // -----------------------------------------------------------------------
    describe('forApi output structure', function () {
        it('returns consistent structure for all cases', function () {
            $api = UserStatus::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
                expect($item['color'])->toBeString();
            }
        });

        it('preserves declaration order', function () {
            $api = UserStatus::forApi();
            $cases = UserStatus::cases();

            foreach ($cases as $i => $case) {
                expect($api[$i]['name'])->toBe($case->name);
            }
        });

        it('returns correct count matching cases()', function () {
            expect(UserStatus::forApi())->toHaveCount(count(UserStatus::cases()));
            expect(Priority::forApi())->toHaveCount(count(Priority::cases()));
            expect(ZeroPriority::forApi())->toHaveCount(count(ZeroPriority::cases()));
        });
    });

    // -----------------------------------------------------------------------
    // Class-level attribute inheritance
    // -----------------------------------------------------------------------
    describe('TicketStatus class-level attributes', function () {
        it('uses class-level labels', function () {
            expect(TicketStatus::OPEN->label())->toBe('Open');
            expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
            expect(TicketStatus::CLOSED->label())->toBe('Closed');
        });

        it('uses class-level descriptions', function () {
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
            expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
        });

        it('uses class-level default icon for all cases', function () {
            expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket');
            expect(TicketStatus::CLOSED->icon())->toBe('heroicon-o-ticket');
        });

        it('falls back to secondary color when not defined', function () {
            expect(TicketStatus::OPEN->color())->toBe('secondary');
        });
    });

    // -----------------------------------------------------------------------
    // fromName / tryFromName with int-backed enum
    // -----------------------------------------------------------------------
    describe('name lookups with int-backed enums', function () {
        it('tryFromName works with int-backed enum', function () {
            expect(Priority::tryFromName('LOW'))->toBe(Priority::LOW);
            expect(Priority::tryFromName('INVALID'))->toBeNull();
        });

        it('fromName throws for int-backed enum', function () {
            Priority::fromName('HIGH');
        })->throws(InvalidEnumException::class);

        it('hasCase works with int-backed enum', function () {
            expect(Priority::hasCase('NONE'))->toBeTrue();
            expect(Priority::hasCase('MEDIUM'))->toBeFalse();
        });

        it('zero value case works with name lookups', function () {
            expect(ZeroPriority::tryFromName('NONE'))->toBe(ZeroPriority::NONE);
            expect(ZeroPriority::fromName('NONE'))->toBe(ZeroPriority::NONE);
        });
    });

    // -----------------------------------------------------------------------
    // values() / labels() consistency
    // -----------------------------------------------------------------------
    describe('bulk method consistency', function () {
        it('values count matches cases count', function () {
            foreach ([UserStatus::class, Priority::class, ZeroPriority::class, TicketStatus::class, OrderStatus::class] as $enum) {
                expect($enum::values())->toHaveCount(count($enum::cases()));
            }
        });

        it('labels count matches cases count', function () {
            foreach ([UserStatus::class, Priority::class, ZeroPriority::class, TicketStatus::class, OrderStatus::class] as $enum) {
                expect($enum::labels())->toHaveCount(count($enum::cases()));
            }
        });

        it('each label is a non-empty string', function () {
            foreach ([UserStatus::class, Priority::class, ZeroPriority::class] as $enum) {
                foreach ($enum::labels() as $label) {
                    expect($label)->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('forSelect values are unique', function () {
            foreach ([UserStatus::class, Priority::class, ZeroPriority::class, TicketStatus::class] as $enum) {
                $values = array_column($enum::forSelect(), 'value');
                expect(array_unique($values))->toHaveCount(count($values));
            }
        });
    });
});
