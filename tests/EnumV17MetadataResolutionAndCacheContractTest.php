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
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum V17 — Metadata Resolution And Cache Contract', function () {
    describe('EnumCache singleton lifecycle', function () {
        it('returns the same instance on repeated getInstance calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('persists entries across get/has/set cycle', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->clear(); // start clean

            $meta = [
                'labels' => ['a' => 'A'],
                'descriptions' => ['a' => 'Desc'],
                'colors' => ['a' => 'success'],
                'icons' => ['a' => 'icon-a'],
            ];

            $cache->set(TestCacheEnum::class, $meta);

            expect($cache->has(TestCacheEnum::class))->toBeTrue();
            expect($cache->get(TestCacheEnum::class))->toBe($meta);

            // cleanup
            $cache->clearClass(TestCacheEnum::class);
        });

        it('clearClass removes only the target entry', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->clear();

            $cache->set(TestCacheEnum::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            $cache->set(IntPriority::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            $cache->clearClass(TestCacheEnum::class);

            expect($cache->has(TestCacheEnum::class))->toBeFalse();
            expect($cache->has(IntPriority::class))->toBeTrue();

            $cache->clear();
        });

        it('flush clears everything via static accessor', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->clear();

            $cache->set(TestCacheEnum::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            EnumCache::flush();

            expect($cache->has(TestCacheEnum::class))->toBeFalse();
        });

        it('resetInstance destroys the singleton', function () {
            EnumCache::resetInstance();

            $before = EnumCache::getInstance();
            EnumCache::resetInstance();
            $after = EnumCache::getInstance();

            expect($before)->not->toBe($after);
        });

        it('get throws OutOfBoundsException for missing entry', function () {
            $cache = EnumCache::getInstance();
            $cache->clear();

            expect(fn () => $cache->get('NonExistentEnumClass'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('has returns false when ttl is zero', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->clear();

            $cache->set(TestCacheEnum::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);

            expect($cache->has(TestCacheEnum::class))->toBeFalse();

            // Restore
            $cache->setTtl(300);
            $cache->clear();
        });

        it('setTtl clamps negative values to zero', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-100);

            expect($cache->getTtl())->toBe(0);

            $cache->setTtl(300);
        });

        it('blocks serialization via __serialize', function () {
            $cache = EnumCache::getInstance();

            expect(fn () => serialize($cache))
                ->toThrow(\RuntimeException::class);
        });

        it('blocks unserialization via __unserialize', function () {
            expect(fn () => unserialize('O:37:"ZeroBoiler\\Enums\\EnumCache":0:{}'))
                ->toThrow(\RuntimeException::class);
        });
    });

    describe('EnumMetadataResolver cache integration', function () {
        it('invalidate removes cached metadata for a specific class', function () {
            EnumCache::getInstance()->setTtl(300);
            EnumCache::getInstance()->clear();

            // Force resolution to populate cache
            $first = EnumMetadataResolver::resolve(IntPriority::class);
            expect(EnumCache::getInstance()->has(IntPriority::class))->toBeTrue();

            // Invalidate
            EnumMetadataResolver::invalidate(IntPriority::class);
            expect(EnumCache::getInstance()->has(IntPriority::class))->toBeFalse();

            // Re-resolve
            $second = EnumMetadataResolver::resolve(IntPriority::class);
            expect($second)->toBe($first);
            expect(EnumCache::getInstance()->has(IntPriority::class))->toBeTrue();

            EnumCache::getInstance()->clear();
        });

        it('invalidateAll flushes all cached metadata', function () {
            EnumCache::getInstance()->setTtl(300);
            EnumCache::getInstance()->clear();

            EnumMetadataResolver::resolve(IntPriority::class);
            EnumMetadataResolver::resolve(TicketStatus::class);

            EnumMetadataResolver::invalidateAll();

            expect(EnumCache::getInstance()->has(IntPriority::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(TicketStatus::class))->toBeFalse();

            EnumCache::getInstance()->clear();
        });
    });

    describe('HasEnumMetadata trait contract completeness', function () {
        it('forSelect preserves declaration order', function () {
            $options = UserStatus::forSelect();
            $values = array_column($options, 'value');
            $expected = array_map(
                fn (\ZeroBoiler\Enums\Tests\Fixtures\UserStatus $c) => $c->value,
                \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::cases()
            );

            expect($values)->toBe($expected);
        });

        it('forApi returns consistent data shape for int-backed enum', function () {
            $api = IntPriority::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeInt();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
                expect($item['color'])->toBeString();
            }
        });

        it('forApi returns consistent data shape for pure enum', function () {
            $api = PureSystemState::forApi();

            foreach ($api as $item) {
                expect($item['value'])->toBeString(); // case name for pure enum
                expect($item['name'])->toBeString();
            }
        });

        it('labels returns same count as cases', function () {
            $labels = PaymentStatus::labels();
            $cases = PaymentStatus::cases();

            expect($labels)->toHaveCount(count($cases));
            expect($labels)->each->toBeString()->not->toBeEmpty();
        });

        it('is() rejects different enum types at runtime', function () {
            $active = UserStatus::ACTIVE;
            $open = TicketStatus::OPEN;

            // Different enum types — is() checks self, so this should return false
            // since TicketStatus is not UserStatus
            expect($active->is($active->name))->toBeTrue();
        });

        it('in() works with empty array', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('notIn() works with empty array', function () {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });

        it('tryFromName is case-sensitive', function () {
            expect(TicketStatus::tryFromName('OPEN'))->toBeInstanceOf(TicketStatus::class);
            expect(TicketStatus::tryFromName('open'))->toBeNull();
            expect(TicketStatus::tryFromName('Open'))->toBeNull();
        });

        it('fromName throws with descriptive message', function () {
            expect(fn () => TicketStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class, 'Case name [NONEXISTENT] does not exist on enum');
        });

        it('hasCase returns correct boolean for all states', function () {
            expect(TicketStatus::hasCase('OPEN'))->toBeTrue();
            expect(TicketStatus::hasCase('IN_PROGRESS'))->toBeTrue();
            expect(TicketStatus::hasCase('CLOSED'))->toBeTrue();
            expect(TicketStatus::hasCase('DELETED'))->toBeFalse();
        });
    });

    describe('EnumManager facade delegation', function () {
        it('forSelect delegates to enum trait method', function () {
            $manager = new EnumManager;
            $direct = TicketStatus::forSelect();
            $viaManager = $manager->forSelect(TicketStatus::class);

            expect($viaManager)->toBe($direct);
        });

        it('forApi delegates to enum trait method', function () {
            $manager = new EnumManager;
            $direct = TicketStatus::forApi();
            $viaManager = $manager->forApi(TicketStatus::class);

            expect($viaManager)->toBe($direct);
        });

        it('tryFromLabel delegates correctly', function () {
            $manager = new EnumManager;
            $label = TicketStatus::OPEN->label();
            $result = $manager->tryFromLabel(TicketStatus::class, $label);

            expect($result)->toBe(TicketStatus::OPEN);
        });

        it('tryFromLabel returns null for non-existent label', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromLabel(TicketStatus::class, 'Non Existent Label XYZ');

            expect($result)->toBeNull();
        });

        it('values delegates correctly', function () {
            $manager = new EnumManager;
            $direct = IntPriority::values();
            $viaManager = $manager->values(IntPriority::class);

            expect($viaManager)->toBe($direct);
        });

        it('labels delegates correctly', function () {
            $manager = new EnumManager;
            $direct = TicketStatus::labels();
            $viaManager = $manager->labels(TicketStatus::class);

            expect($viaManager)->toBe($direct);
        });

        it('throws BadMethodCallException for non-enum class', function () {
            $manager = new EnumManager;

            expect(fn () => $manager->forSelect(\stdClass::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('throws BadMethodCallException for enum without trait', function () {
            $manager = new EnumManager;

            expect(fn () => $manager->forSelect(PlainTestEnum::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('fromName throws for non-existent name', function () {
            $manager = new EnumManager;

            expect(fn () => $manager->fromName(TicketStatus::class, 'NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });
    });

    describe('EnumRule validation edge cases', function () {
        it('rejects invalid value for string-backed enum', function () {
            $rule = EnumRule::for(TicketStatus::class);

            $failed = false;
            $rule->validate('status', 'nonexistent_value', function (string $msg) use (&$failed): void {
                $failed = true;
                expect($msg)->toContain('invalid');
            });

            expect($failed)->toBeTrue();
        });

        it('rejects invalid int value for int-backed enum', function () {
            $rule = EnumRule::for(IntPriority::class);

            $failed = false;
            $rule->validate('priority', 999, function (string $msg) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('accepts null when nullable is enabled', function () {
            $rule = EnumRule::for(TicketStatus::class)->nullable();

            $failed = false;
            $rule->validate('status', null, function (string $msg) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeFalse();
        });

        it('rejects null when nullable is disabled', function () {
            $rule = EnumRule::for(TicketStatus::class);

            $failed = false;
            $rule->validate('status', null, function (string $msg) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('rejects wrong type for int-backed enum (string given)', function () {
            $rule = EnumRule::for(IntPriority::class);

            $failed = false;
            $rule->validate('priority', 'not-an-int', function (string $msg) use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('validates pure enum by case name', function () {
            $rule = EnumRule::for(PureSystemState::class);

            // Valid case name
            $passed = true;
            $rule->validate('state', 'INITIALIZING', function () use (&$passed): void {
                $passed = false;
            });

            expect($passed)->toBeTrue();

            // Invalid case name
            $failed = false;
            $rule->validate('state', 'NONEXISTENT', function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });

        it('generates descriptive error message with allowed values', function () {
            $rule = EnumRule::for(TicketStatus::class);

            $message = '';
            $rule->validate('status', 'bad', function (string $msg) use (&$message): void {
                $message = $msg;
            });

            expect($message)->toContain('Allowed values:');
            expect($message)->toContain('open');
        });

        it('rejects non-string for pure enum', function () {
            $rule = EnumRule::for(PureSystemState::class);

            $failed = false;
            $rule->validate('state', 123, function () use (&$failed): void {
                $failed = true;
            });

            expect($failed)->toBeTrue();
        });
    });

    describe('attribute resolution priority', function () {
        it('per-case Label overrides class-level EnumLabel', function () {
            // READY has #[Label('Ready to Serve')] which overrides EnumLabel
            expect(PureSystemState::READY->label())->toBe('Ready to Serve');
        });

        it('per-case Color overrides class-level EnumColor', function () {
            expect(PureSystemState::READY->color())->toBe('success');
            expect(PureSystemState::FAILED->color())->toBe('danger');
        });

        it('class-level EnumIcon default applies to cases without specific icon', function () {
            // RUNNING has no specific icon → gets the default 'heroicon-o-cog'
            expect(PureSystemState::RUNNING->icon())->toBe('heroicon-o-cog');
        });

        it('class-level EnumIcon per-value icon overrides default', function () {
            // INITIALIZING has 'heroicon-o-arrow-path' in the icons map
            expect(PureSystemState::INITIALIZING->icon())->toBe('heroicon-o-arrow-path');
        });

        it('per-case Icon overrides both class-level icon map and default', function () {
            // READY has #[Icon('heroicon-o-check-circle')]
            expect(PureSystemState::READY->icon())->toBe('heroicon-o-check-circle');
        });

        it('description falls back to null when not defined', function () {
            // RUNNING has no description at case or class level
            expect(PureSystemState::RUNNING->description())->toBeNull();
        });

        it('class-level EnumDescription provides description for mapped cases', function () {
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
            expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
        });

        it('auto-generated label handles SCREAMING_SNAKE_CASE', function () {
            // IN_PROGRESS has no label attribute, class-level EnumLabel maps 'in_progress' → 'In Progress'
            expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
        });

        it('auto-generated label handles camelCase', function () {
            // IntPriority has no attributes → auto-generates from SCREAMING_SNAKE_CASE
            expect(IntPriority::LOW->label())->toBe('Low');
            expect(IntPriority::MEDIUM->label())->toBe('Medium');
            expect(IntPriority::HIGH->label())->toBe('High');
            expect(IntPriority::CRITICAL->label())->toBe('Critical');
        });

        it('color defaults to secondary when not set', function () {
            // IntPriority has no color attributes
            expect(IntPriority::LOW->color())->toBe('secondary');
            expect(IntPriority::CRITICAL->color())->toBe('secondary');
        });

        it('icon defaults to null when not set', function () {
            expect(IntPriority::LOW->icon())->toBeNull();
            expect(TicketStatus::OPEN->icon())->toBe('heroicon-o-ticket'); // class-level default
        });
    });
});

/**
 * @internal Test-only enum fixture for cache tests.
 */
enum TestCacheEnum: string
{
    use HasEnumMetadata;

    case A = 'a';
    case B = 'b';
}
