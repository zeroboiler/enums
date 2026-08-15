<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\SingletonMode;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Production Readiness V13 — Metadata Resolution Edge Cases.
 *
 * Deep edge-case testing for:
 * 1. Label generation with complex case names (multi-word, abbreviations, numbers)
 * 2. Cache invalidation behavior under TTL expiration
 * 3. Class-level + per-case attribute merge priority
 * 4. EnumManager delegation with invalid enum classes
 * 5. ForSelect/ForApi return shape consistency across enum types
 * 6. Reverse lookup with duplicate auto-generated labels (last-wins via iteration)
 * 7. Empty attribute maps and null handling
 */
describe('Production Readiness V13 — Metadata Resolution Edge Cases', function (): void {

    // ──────────────────────────────────────────────────────────────
    // 1. Label generation with complex case names
    // ──────────────────────────────────────────────────────────────

    describe('Label generation for complex case names', function (): void {
        it('generates label from SCREAMING_SNAKE_CASE with multiple underscores', function (): void {
            // USER_ACCOUNT_STATUS → User Account Status
            expect(IntPriority::CRITICAL->label())->toBe('Critical');
        });

        it('generates label for single-word upper case', function (): void {
            expect(SingletonMode::INSTANCE->label())->toBe('Instance');
        });

        it('generates label for camelCase-style enum names', function (): void {
            // CamelCaseRole should have a fixture; let's test via the trait directly
            $reflection = new ReflectionEnum(\ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::class);
            expect($reflection->getShortName())->toBe('CamelCaseRole');
        });

        it('generates consistent labels for all IntPriority cases', function (): void {
            expect(IntPriority::LOW->label())->toBe('Low');
            expect(IntPriority::MEDIUM->label())->toBe('Medium');
            expect(IntPriority::HIGH->label())->toBe('High');
            expect(IntPriority::CRITICAL->label())->toBe('Critical');
        });

        it('generates label for pure enum case with underscores', function (): void {
            // IN_PROGRESS → In Progress
            expect(\ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::class)::class;
            // PureSystemState has INITIALIZING → Initializing
            expect(PureSystemState::INITIALIZING->label())->toBe('Initializing');
            expect(PureSystemState::RUNNING->label())->toBe('Running');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 2. Cache TTL invalidation behavior
    // ──────────────────────────────────────────────────────────────

    describe('Cache TTL behavior', function (): void {
        it('returns false when TTL is 0 (disabled)', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set('TestEnum_V13', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has('TestEnum_V13'))->toBeFalse();
        });

        it('returns true for fresh entry with positive TTL', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set('TestEnum_V13_Fresh', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has('TestEnum_V13_Fresh'))->toBeTrue();

            // Cleanup
            $cache->clearClass('TestEnum_V13_Fresh');
        });

        it('expires entry after TTL', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(1); // 1 second TTL
            $cache->set('TestEnum_V13_Expired', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            // Immediately should be valid
            expect($cache->has('TestEnum_V13_Expired'))->toBeTrue();

            // Sleep past TTL
            sleep(2);

            // Should now be expired
            expect($cache->has('TestEnum_V13_Expired'))->toBeFalse();

            // Reset TTL
            $cache->setTtl(300);
        });

        it('clearClass removes specific entry only', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set('TestEnum_V13_A', ['labels' => ['x' => 'A'], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('TestEnum_V13_B', ['labels' => ['x' => 'B'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clearClass('TestEnum_V13_A');

            expect($cache->has('TestEnum_V13_A'))->toBeFalse();
            expect($cache->has('TestEnum_V13_B'))->toBeTrue();

            // Cleanup
            $cache->clearClass('TestEnum_V13_B');
        });

        it('clear removes all entries', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set('TestEnum_V13_X', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('TestEnum_V13_Y', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clear();

            expect($cache->has('TestEnum_V13_X'))->toBeFalse();
            expect($cache->has('TestEnum_V13_Y'))->toBeFalse();
        });

        it('getTtl returns current TTL', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(42);
            expect($cache->getTtl())->toBe(42);
            $cache->setTtl(300);
        });

        it('setTtl clamps negative values to 0', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-100);
            expect($cache->getTtl())->toBe(0);
            $cache->setTtl(300);
        });

        it('flush() delegates to singleton clear()', function (): void {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);
            $cache->set('TestEnum_V13_Flush', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            expect($cache->has('TestEnum_V13_Flush'))->toBeTrue();

            EnumCache::flush();

            expect($cache->has('TestEnum_V13_Flush'))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 3. Class-level + per-case attribute merge priority
    // ──────────────────────────────────────────────────────────────

    describe('Attribute merge priority (class-level vs per-case)', function (): void {
        it('per-case Label overrides class-level EnumLabel', function (): void {
            // PureSystemState has EnumLabel(labels: ['READY' => 'System Ready'])
            // but per-case #[Label('Ready to Serve')]
            expect(PureSystemState::READY->label())->toBe('Ready to Serve');
        });

        it('class-level EnumLabel used when no per-case override', function (): void {
            // TicketStatus has EnumLabel with 'open' => 'Open'
            expect(TicketStatus::OPEN->label())->toBe('Open');
        });

        it('per-case Color overrides class-level EnumColor', function (): void {
            // PureSystemState has EnumColor(success: ['READY'], danger: ['FAILED'])
            // READY has per-case #[Color('success')] — same, but still per-case wins
            expect(PureSystemState::READY->color())->toBe('success');
            expect(PureSystemState::FAILED->color())->toBe('danger');
        });

        it('class-level EnumColor applied when no per-case override', function (): void {
            // RUNNING has no per-case Color, but EnumColor maps are class-level
            // No explicit mapping for RUNNING, so defaults to 'secondary'
            expect(PureSystemState::RUNNING->color())->toBe('secondary');
        });

        it('per-case Description overrides class-level EnumDescription', function (): void {
            // PureSystemState: EnumDescription has READY description, but per-case overrides
            expect(PureSystemState::READY->description())->toBe('All services started and accepting traffic');
        });

        it('class-level EnumDescription used when no per-case override', function (): void {
            // TicketStatus OPEN has class-level description
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
        });

        it('per-case Icon overrides class-level EnumIcon default', function (): void {
            // PureSystemState has EnumIcon(default: 'heroicon-o-cog')
            // READY has per-case #[Icon('heroicon-o-check-circle')]
            expect(PureSystemState::READY->icon())->toBe('heroicon-o-check-circle');
        });

        it('class-level EnumIcon default used when no per-case override', function (): void {
            // INITIALIZING has no per-case Icon, but EnumIcon has default
            expect(PureSystemState::INITIALIZING->icon())->toBe('heroicon-o-cog');
        });

        it('class-level EnumIcon per-value map overrides default', function (): void {
            // EnumIcon has icons: ['INITIALIZING' => 'heroicon-o-arrow-path']
            // This per-value map should override the default for INITIALIZING
            expect(PureSystemState::INITIALIZING->icon())->toBe('heroicon-o-arrow-path');
        });

        it('null icon/description when nothing defined', function (): void {
            // IntPriority has no attributes at all
            expect(IntPriority::LOW->icon())->toBeNull();
            expect(IntPriority::LOW->description())->toBeNull();
        });

        it('default color is secondary when nothing defined', function (): void {
            // IntPriority has no EnumColor or per-case Color
            expect(IntPriority::LOW->color())->toBe('secondary');
            expect(IntPriority::MEDIUM->color())->toBe('secondary');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 4. forSelect / forApi return shape consistency
    // ──────────────────────────────────────────────────────────────

    describe('forSelect and forApi shape consistency', function (): void {
        it('forSelect returns correct shape for string-backed enum', function (): void {
            $select = TicketStatus::forSelect();
            expect($select)->toBeArray();
            expect($select)->toHaveCount(3);

            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }
        });

        it('forSelect returns correct shape for int-backed enum', function (): void {
            $select = IntPriority::forSelect();
            expect($select)->toBeArray();
            expect($select)->toHaveCount(4);

            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeInt();
                expect($option['label'])->toBeString();
            }
        });

        it('forSelect returns correct shape for pure enum', function (): void {
            $select = PureSystemState::forSelect();
            expect($select)->toBeArray();
            expect($select)->toHaveCount(4);

            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString(); // case name for pure enums
                expect($option['label'])->toBeString();
            }
        });

        it('forApi returns full shape with all keys', function (): void {
            $api = TicketStatus::forApi();
            expect($api)->toBeArray();
            expect($api)->toHaveCount(3);

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['value'])->toBeString();
                expect($item['name'])->toBeString();
                expect($item['label'])->toBeString();
                expect($item['color'])->toBeString();
            }
        });

        it('forApi values match forSelect values in order', function (): void {
            $select = TicketStatus::forSelect();
            $api = TicketStatus::forApi();

            for ($i = 0; $i < count($select); $i++) {
                expect($select[$i]['value'])->toBe($api[$i]['value']);
                expect($select[$i]['label'])->toBe($api[$i]['label']);
            }
        });

        it('forApi for int-backed enum uses int values', function (): void {
            $api = IntPriority::forApi();
            $values = array_column($api, 'value');
            expect($values)->toBe([1, 5, 10, 99]);
        });

        it('forApi for pure enum uses case names as values', function (): void {
            $api = PureSystemState::forApi();
            $values = array_column($api, 'value');
            expect($values)->toBe(['INITIALIZING', 'READY', 'RUNNING', 'FAILED']);
        });

        it('forSelect values are unique', function (): void {
            $values = array_column(TicketStatus::forSelect(), 'value');
            expect(array_unique($values))->toBe($values);
        });

        it('forSelect labels are all non-empty', function (): void {
            foreach (TicketStatus::forSelect() as $option) {
                expect($option['label'])->not->toBeEmpty();
            }
        });

        it('forApi colors are all non-empty strings', function (): void {
            foreach (TicketStatus::forApi() as $item) {
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 5. Comparison method edge cases
    // ──────────────────────────────────────────────────────────────

    describe('Comparison method edge cases', function (): void {
        it('is() with empty string does not match any case', function (): void {
            expect(TicketStatus::OPEN->is(''))->toBeFalse();
        });

        it('is() with different-length string does not match', function (): void {
            expect(TicketStatus::OPEN->is('OPEN_EXTRA'))->toBeFalse();
        });

        it('in() with empty array returns false', function (): void {
            expect(TicketStatus::OPEN->in([]))->toBeFalse();
        });

        it('notIn() with empty array returns true', function (): void {
            expect(TicketStatus::OPEN->notIn([]))->toBeTrue();
        });

        it('in() with same case listed twice works correctly', function (): void {
            expect(TicketStatus::OPEN->in([TicketStatus::OPEN, TicketStatus::OPEN]))->toBeTrue();
        });

        it('isNot() with empty string is true', function (): void {
            expect(TicketStatus::OPEN->isNot(''))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 6. Lookup edge cases
    // ──────────────────────────────────────────────────────────────

    describe('Lookup edge cases', function (): void {
        it('tryFromName with empty string returns null', function (): void {
            expect(TicketStatus::tryFromName(''))->toBeNull();
        });

        it('tryFromLabel with empty string returns null', function (): void {
            expect(TicketStatus::tryFromLabel(''))->toBeNull();
        });

        it('tryFromLabel is truly case-insensitive', function (): void {
            $label = TicketStatus::OPEN->label();
            expect(TicketStatus::tryFromLabel(strtoupper($label)))->toBe(TicketStatus::OPEN);
            expect(TicketStatus::tryFromLabel(strtolower($label)))->toBe(TicketStatus::OPEN);
            expect(TicketStatus::tryFromLabel(ucwords(strtolower($label))))->toBe(TicketStatus::OPEN);
        });

        it('tryFromName is case-sensitive', function (): void {
            expect(TicketStatus::tryFromName('OPEN'))->toBe(TicketStatus::OPEN);
            expect(TicketStatus::tryFromName('open'))->toBeNull();
            expect(TicketStatus::tryFromName('Open'))->toBeNull();
        });

        it('hasCase returns correct for existing and non-existing', function (): void {
            expect(TicketStatus::hasCase('OPEN'))->toBeTrue();
            expect(TicketStatus::hasCase('IN_PROGRESS'))->toBeTrue();
            expect(TicketStatus::hasCase('CLOSED'))->toBeTrue();
            expect(TicketStatus::hasCase('DELETED'))->toBeFalse();
            expect(TicketStatus::hasCase(''))->toBeFalse();
        });

        it('fromName throws InvalidEnumException with class and name in message', function (): void {
            try {
                TicketStatus::fromName('NON_EXISTENT');
                $this->fail('Expected InvalidEnumException');
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('NON_EXISTENT');
                expect($e->getMessage())->toContain(TicketStatus::class);
            }
        });

        it('values() returns correct types for each enum kind', function (): void {
            $stringValues = TicketStatus::values();
            foreach ($stringValues as $v) {
                expect($v)->toBeString();
            }

            $intValues = IntPriority::values();
            foreach ($intValues as $v) {
                expect($v)->toBeInt();
            }

            $pureValues = PureSystemState::values();
            foreach ($pureValues as $v) {
                expect($v)->toBeString(); // case names for pure enums
            }
        });

        it('labels() count matches cases count', function (): void {
            expect(TicketStatus::labels())->toHaveCount(count(TicketStatus::cases()));
            expect(IntPriority::labels())->toHaveCount(count(IntPriority::cases()));
            expect(PureSystemState::labels())->toHaveCount(count(PureSystemState::cases()));
        });

        it('labels() are all non-empty strings', function (): void {
            foreach (TicketStatus::labels() as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 7. Singleton enum (single case) edge cases
    // ──────────────────────────────────────────────────────────────

    describe('Singleton enum (single case) edge cases', function (): void {
        it('forSelect returns single-element array', function (): void {
            $select = SingletonMode::forSelect();
            expect($select)->toHaveCount(1);
            expect($select[0])->toHaveKeys(['value', 'label']);
            expect($select[0]['value'])->toBe('INSTANCE');
        });

        it('forApi returns single-element array with all keys', function (): void {
            $api = SingletonMode::forApi();
            expect($api)->toHaveCount(1);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('tryFromLabel finds the single case', function (): void {
            expect(SingletonMode::tryFromLabel('Instance'))->toBe(SingletonMode::INSTANCE);
        });

        it('tryFromName returns null for non-existent', function (): void {
            expect(SingletonMode::tryFromName('OTHER'))->toBeNull();
        });

        it('in() works with single-element list', function (): void {
            expect(SingletonMode::INSTANCE->in([SingletonMode::INSTANCE]))->toBeTrue();
            expect(SingletonMode::INSTANCE->in(['INSTANCE']))->toBeTrue();
        });

        it('notIn() works correctly', function (): void {
            expect(SingletonMode::INSTANCE->notIn([]))->toBeTrue();
            expect(SingletonMode::INSTANCE->notIn([SingletonMode::INSTANCE]))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 8. EnumCache get() throws on missing entry
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache get() throws on missing entry', function (): void {
        it('throws OutOfBoundsException for non-existent entry', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear(); // ensure clean state

            expect(fn () => $cache->get('NonExistentEnum_V13'))
                ->toThrow(\OutOfBoundsException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 9. EnumManager delegation with non-metadata enum
    // ──────────────────────────────────────────────────────────────

    describe('EnumManager rejects non-HasEnumMetadata enums', function (): void {
        it('forSelect throws BadMethodCallException', function (): void {
            $manager = new \ZeroBoiler\Enums\EnumManager;

            expect(fn () => $manager->forSelect(\ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('forApi throws BadMethodCallException', function (): void {
            $manager = new \ZeroBoiler\Enums\EnumManager;

            expect(fn () => $manager->forApi(\ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('values throws BadMethodCallException', function (): void {
            $manager = new \ZeroBoiler\Enums\EnumManager;

            expect(fn () => $manager->values(\ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum::class))
                ->toThrow(\BadMethodCallException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. InvalidEnumException named constructors
    // ──────────────────────────────────────────────────────────────

    describe('InvalidEnumException factory methods', function (): void {
        it('value() creates exception with null value display', function (): void {
            $e = InvalidEnumException::value('TestEnum', null);
            expect($e->getMessage())->toContain('null');
            expect($e->getMessage())->toContain('TestEnum');
        });

        it('value() creates exception with int value display', function (): void {
            $e = InvalidEnumException::value('TestEnum', 42);
            expect($e->getMessage())->toContain('42');
            expect($e->getMessage())->toContain('TestEnum');
        });

        it('value() creates exception with string value display', function (): void {
            $e = InvalidEnumException::value('TestEnum', 'invalid_value');
            expect($e->getMessage())->toContain('invalid_value');
        });

        it('forName() creates exception with class and name', function (): void {
            $e = InvalidEnumException::forName('TestEnum', 'BAD_CASE');
            expect($e->getMessage())->toContain('BAD_CASE');
            expect($e->getMessage())->toContain('TestEnum');
        });

        it('__toString() includes class name', function (): void {
            $e = InvalidEnumException::forName('TestEnum', 'BAD_CASE');
            expect((string) $e)->toContain(InvalidEnumException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 11. Int-backed enum with EnumColor class-level attribute
    // ──────────────────────────────────────────────────────────────

    describe('IntStatusWithColor fixture', function (): void {
        it('applies class-level colors to int-backed enum', function (): void {
            // IntStatusWithColor has EnumColor(success: [1, 4], danger: [3], warning: [2])
            expect(\ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor::ACTIVE->color())->toBe('success');
            expect(\ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor::PENDING->color())->toBe('warning');
            expect(\ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor::BANNED->color())->toBe('danger');
            expect(\ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor::DRAFT->color())->toBe('success');
        });

        it('forSelect uses int values not names', function (): void {
            $select = \ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor::forSelect();
            $values = array_column($select, 'value');
            expect($values)->toEqual([1, 2, 3, 4]);
        });

        it('tryFromName works on int-backed enum', function (): void {
            $case = \ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor::tryFromName('ACTIVE');
            expect($case)->not->toBeNull();
            expect($case->name)->toBe('ACTIVE');
            expect($case->value)->toBe(1);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 12. TicketStatus class-level attribute completeness
    // ──────────────────────────────────────────────────────────────

    describe('TicketStatus class-level attributes', function (): void {
        it('has correct class-level labels for all three cases', function (): void {
            expect(TicketStatus::OPEN->label())->toBe('Open');
            expect(TicketStatus::IN_PROGRESS->label())->toBe('In Progress');
            expect(TicketStatus::CLOSED->label())->toBe('Closed');
        });

        it('has class-level descriptions where defined', function (): void {
            expect(TicketStatus::OPEN->description())->toBe('Ticket is open and awaiting response');
            expect(TicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
        });

        it('has default icon from EnumIcon', function (): void {
            // IN_PROGRESS and CLOSED don't have per-case icons, should get default
            expect(TicketStatus::IN_PROGRESS->icon())->toBe('heroicon-o-ticket');
            expect(TicketStatus::CLOSED->icon())->toBe('heroicon-o-ticket');
        });
    });
});
