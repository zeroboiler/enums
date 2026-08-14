<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumsServiceProvider;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCasePriority;
use ZeroBoiler\Enums\Tests\Fixtures\EmptyDefaultsStatus;
use ZeroBoiler\Enums\Tests\Fixtures\MixedTicketType;
use ZeroBoiler\Enums\Tests\Fixtures\NumericStatusCode;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\SingletonMode;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

describe('Enum Structural and Contract Integrity', function () {
    // ─── HasEnumMetadata trait: all methods present and correctly typed ───
    describe('HasEnumMetadata trait method completeness', function () {
        it('provides label() on all enum types', function () {
            // String-backed
            expect(EmptyDefaultsStatus::DRAFT->label())->toBeString()->not->toBeEmpty();
            // Int-backed
            expect(MixedTicketType::CRITICAL_BUG->label())->toBeString()->not->toBeEmpty();
            // Pure enum
            expect(SingletonMode::INSTANCE->label())->toBeString()->not->toBeEmpty();
        });

        it('provides description() returning nullable string', function () {
            expect(EmptyDefaultsStatus::DRAFT->description())->toBeNull();
            expect(MixedTicketType::CRITICAL_BUG->description())->toBeString()->not->toBeEmpty();
        });

        it('provides color() defaulting to secondary', function () {
            expect(EmptyDefaultsStatus::DRAFT->color())->toBe('secondary');
            expect(MixedTicketType::CRITICAL_BUG->color())->toBe('danger');
        });

        it('provides icon() returning nullable string', function () {
            expect(EmptyDefaultsStatus::DRAFT->icon())->toBeNull();
            expect(MixedTicketType::CRITICAL_BUG->icon())->toBe('heroicon-o-fire');
        });

        it('provides static forSelect() returning consistent structure', function () {
            $select = EmptyDefaultsStatus::forSelect();
            expect($select)->toBeArray();
            expect($select)->toHaveCount(3);
            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('provides static forApi() returning full metadata structure', function () {
            $api = MixedTicketType::forApi();
            expect($api)->toBeArray();
            expect($api)->toHaveCount(4);
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('provides static values() returning correct backing types', function () {
            // String-backed
            $stringValues = EmptyDefaultsStatus::values();
            expect($stringValues)->toEqual(['draft', 'published', 'archived']);

            // Int-backed
            $intValues = MixedTicketType::values();
            expect($intValues)->toEqual([1, 2, 3, 4]);

            // Pure enum
            $pureValues = SingletonMode::values();
            expect($pureValues)->toEqual(['INSTANCE']);
        });

        it('provides static labels() with same count as cases', function () {
            $labels = EmptyDefaultsStatus::labels();
            expect($labels)->toHaveCount(3);
            expect($labels)->each->toBeString()->not->toBeEmpty();
        });
    });

    // ─── Comparison methods ───
    describe('Comparison method contract', function () {
        it('is() works with enum instances (strict identity)', function () {
            expect(MixedTicketType::CRITICAL_BUG->is(MixedTicketType::CRITICAL_BUG))->toBeTrue();
            expect(MixedTicketType::CRITICAL_BUG->is(MixedTicketType::FEATURE))->toBeFalse();
        });

        it('is() works with case name strings (case-sensitive)', function () {
            expect(MixedTicketType::CRITICAL_BUG->is('CRITICAL_BUG'))->toBeTrue();
            expect(MixedTicketType::CRITICAL_BUG->is('critical_bug'))->toBeFalse();
            expect(MixedTicketType::CRITICAL_BUG->is('FEATURE'))->toBeFalse();
        });

        it('isNot() is the exact negation of is()', function () {
            $case = MixedTicketType::CRITICAL_BUG;
            $same = MixedTicketType::CRITICAL_BUG;
            $other = MixedTicketType::FEATURE;

            expect($case->isNot($same))->toBeFalse();
            expect($case->isNot($other))->toBeTrue();
        });

        it('in() matches against mixed instances and strings', function () {
            $case = MixedTicketType::CRITICAL_BUG;
            expect($case->in([MixedTicketType::CRITICAL_BUG, 'FEATURE']))->toBeTrue();
            expect($case->in(['CRITICAL_BUG', 'FEATURE']))->toBeTrue();
            expect($case->in(['FEATURE', 'SUPPORT']))->toBeFalse();
            expect($case->in([]))->toBeFalse();
        });

        it('notIn() is the exact negation of in()', function () {
            $case = MixedTicketType::CRITICAL_BUG;
            expect($case->notIn(['FEATURE', 'SUPPORT']))->toBeTrue();
            expect($case->notIn(['CRITICAL_BUG', 'FEATURE']))->toBeFalse();
        });
    });

    // ─── Lookup methods ───
    describe('Lookup method contract', function () {
        it('tryFromLabel() is case-insensitive', function () {
            expect(EmptyDefaultsStatus::tryFromLabel('Draft'))->toBe(EmptyDefaultsStatus::DRAFT);
            expect(EmptyDefaultsStatus::tryFromLabel('draft'))->toBe(EmptyDefaultsStatus::DRAFT);
            expect(EmptyDefaultsStatus::tryFromLabel('DRAFT'))->toBe(EmptyDefaultsStatus::DRAFT);
            expect(EmptyDefaultsStatus::tryFromLabel('nonexistent'))->toBeNull();
        });

        it('tryFromName() is case-sensitive', function () {
            expect(EmptyDefaultsStatus::tryFromName('DRAFT'))->toBe(EmptyDefaultsStatus::DRAFT);
            expect(EmptyDefaultsStatus::tryFromName('draft'))->toBeNull();
        });

        it('fromName() throws InvalidEnumException for invalid names', function () {
            expect(fn () => EmptyDefaultsStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase() returns correct boolean', function () {
            expect(EmptyDefaultsStatus::hasCase('DRAFT'))->toBeTrue();
            expect(EmptyDefaultsStatus::hasCase('NONEXISTENT'))->toBeFalse();
        });

        it('lookup methods work on int-backed enums', function () {
            expect(MixedTicketType::tryFromName('CRITICAL_BUG'))->toBe(MixedTicketType::CRITICAL_BUG);
            expect(MixedTicketType::tryFromLabel('Critical Bug'))->toBe(MixedTicketType::CRITICAL_BUG);
            expect(MixedTicketType::hasCase('CRITICAL_BUG'))->toBeTrue();
        });
    });

    // ─── Resolution priority ───
    describe('Metadata resolution priority', function () {
        it('per-case Label overrides class-level EnumLabel', function () {
            // Class-level: labels[1] => 'Bug Report'
            // Per-case: #[Label('Critical Bug')]
            expect(MixedTicketType::CRITICAL_BUG->label())->toBe('Critical Bug');
        });

        it('class-level EnumLabel provides fallback for cases without per-case', function () {
            // Class-level: labels[3] => 'Support Ticket'
            expect(MixedTicketType::SUPPORT->label())->toBe('Support Ticket');
        });

        it('per-case Color overrides class-level EnumColor', function () {
            // No class-level color maps '2' (FEATURE), but per-case sets it
            expect(MixedTicketType::FEATURE->color())->toBe('success');
        });

        it('per-case Description overrides class-level EnumDescription', function () {
            // Class-level: descriptions[1] => 'Report a bug'
            // Per-case: 'System-breaking bug — immediate fix required'
            expect(MixedTicketType::CRITICAL_BUG->description())
                ->toBe('System-breaking bug — immediate fix required');
        });

        it('class-level EnumIcon default applies to cases without per-case', function () {
            // Class-level: default => 'heroicon-o-question-mark-circle'
            // Per-case icons only for 1 and 2
            expect(MixedTicketType::SUPPORT->icon())->toBe('heroicon-o-question-mark-circle');
            expect(MixedTicketType::DOCS->icon())->toBe('heroicon-o-question-mark-circle');
        });

        it('per-case Icon overrides class-level EnumIcon', function () {
            expect(MixedTicketType::CRITICAL_BUG->icon())->toBe('heroicon-o-fire');
        });
    });

    // ─── Auto-label generation ───
    describe('Auto-label generation', function () {
        it('SCREAMING_SNAKE_CASE converts to Title Case', function () {
            expect(EmptyDefaultsStatus::DRAFT->label())->toBe('Draft');
            expect(EmptyDefaultsStatus::PUBLISHED->label())->toBe('Published');
            expect(EmptyDefaultsStatus::ARCHIVED->label())->toBe('Archived');
        });

        it('camelCase converts to Title Case', function () {
            // pendingReview → 'Pending Review' (auto-generated)
            // BUT: per-case #[Label('Awaiting Approval')] overrides
            expect(CamelCasePriority::active->label())->toBe('Online');
            expect(CamelCasePriority::pendingReview->label())->toBe('Awaiting Approval');
            expect(CamelCasePriority::archived->label())->toBe('Archived');
            expect(CamelCasePriority::softDeleted->label())->toBe('Soft Deleted');
        });

        it('color defaults to secondary when no attribute is set', function () {
            expect(EmptyDefaultsStatus::DRAFT->color())->toBe('secondary');
            expect(EmptyDefaultsStatus::PUBLISHED->color())->toBe('secondary');
        });

        it('icon defaults to null when no attribute is set', function () {
            expect(EmptyDefaultsStatus::DRAFT->icon())->toBeNull();
        });

        it('description defaults to null when no attribute is set', function () {
            expect(EmptyDefaultsStatus::DRAFT->description())->toBeNull();
        });
    });

    // ─── Zero-backed int enum edge cases ───
    describe('Zero-backed int enum edge cases', function () {
        it('zero value is a valid backed value', function () {
            expect(ZeroBackedPriority::NONE->value)->toBe(0);
            expect(ZeroBackedPriority::NONE->is('NONE'))->toBeTrue();
        });

        it('zero-backed enum resolves class-level labels correctly', function () {
            expect(ZeroBackedPriority::NONE->label())->toBe('None');
            expect(ZeroBackedPriority::LOW->label())->toBe('Low Priority');
            expect(ZeroBackedPriority::HIGH->label())->toBe('High Priority');
        });

        it('zero-backed enum resolves class-level colors correctly', function () {
            expect(ZeroBackedPriority::NONE->color())->toBe('secondary');
            expect(ZeroBackedPriority::LOW->color())->toBe('success');
            expect(ZeroBackedPriority::HIGH->color())->toBe('danger');
        });

        it('forSelect() preserves zero as a valid value', function () {
            $select = ZeroBackedPriority::forSelect();
            $none = array_filter($select, fn (array $opt): bool => $opt['value'] === 0);
            expect($none)->not->toBeEmpty();
            expect(array_values($none)[0]['label'])->toBe('None');
        });
    });

    // ─── Empty string backed value edge cases ───
    describe('Empty string backed value edge cases', function () {
        it('empty string is a valid backed value', function () {
            expect(NumericStatusCode::EMPTY_VALUE->value)->toBe('');
            expect(NumericStatusCode::EMPTY_VALUE->label())->toBe('None');
        });

        it('numeric string values are valid backed values', function () {
            expect(NumericStatusCode::ZERO->value)->toBe('0');
            expect(NumericStatusCode::ONE->value)->toBe('1');
            expect(NumericStatusCode::TWO->value)->toBe('2');
        });
    });

    // ─── Single-case enum edge cases ───
    describe('Single-case enum edge cases', function () {
        it('forSelect() returns exactly one element', function () {
            expect(SingleCaseToggle::forSelect())->toHaveCount(1);
        });

        it('forApi() returns exactly one element with full metadata', function () {
            $api = SingleCaseToggle::forApi();
            expect($api)->toHaveCount(1);
            expect($api[0])->toHaveKey('value');
            expect($api[0])->toHaveKey('name');
        });

        it('in() works with single-element array', function () {
            expect(SingletonMode::INSTANCE->in([SingletonMode::INSTANCE]))->toBeTrue();
            expect(SingletonMode::INSTANCE->in(['INSTANCE']))->toBeTrue();
        });

        it('in() with empty array returns false', function () {
            expect(SingletonMode::INSTANCE->in([]))->toBeFalse();
        });
    });

    // ─── EnumRule validation contract ───
    describe('EnumRule validation contract', function () {
        it('creates via for() named constructor', function () {
            $rule = EnumRule::for(EmptyDefaultsStatus::class);
            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('creates nullable variant', function () {
            $rule = EnumRule::for(EmptyDefaultsStatus::class)->nullable();
            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('non-nullable rule rejects null values', function () {
            $rule = EnumRule::for(EmptyDefaultsStatus::class);
            $failed = false;
            $rule->validate('status', null, function (string $message): void {
                expect($message)->toBeString()->not->toBeEmpty();
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('nullable rule accepts null values', function () {
            $rule = EnumRule::for(EmptyDefaultsStatus::class)->nullable();
            $failed = false;
            $rule->validate('status', null, function (): void {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        });
    });

    // ─── EnumManager delegation contract ───
    describe('EnumManager delegation contract', function () {
        it('forSelect() delegates correctly', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $select = $manager->forSelect(EmptyDefaultsStatus::class);
            expect($select)->toBe(EmptyDefaultsStatus::forSelect());
        });

        it('forApi() delegates correctly', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $api = $manager->forApi(MixedTicketType::class);
            expect($api)->toBe(MixedTicketType::forApi());
        });

        it('tryFromName() delegates correctly', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            $result = $manager->tryFromName(EmptyDefaultsStatus::class, 'DRAFT');
            expect($result)->toBe(EmptyDefaultsStatus::DRAFT);
        });

        it('hasCase() delegates correctly', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect($manager->hasCase(EmptyDefaultsStatus::class, 'DRAFT'))->toBeTrue();
            expect($manager->hasCase(EmptyDefaultsStatus::class, 'NONEXISTENT'))->toBeFalse();
        });

        it('throws BadMethodCallException for enums without trait', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect(fn () => $manager->forSelect(PlainTestEnum::class))
                ->toThrow(\BadMethodCallException::class);
        });

        it('values() delegates correctly', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect($manager->values(EmptyDefaultsStatus::class))
                ->toBe(EmptyDefaultsStatus::values());
        });

        it('labels() delegates correctly', function () {
            $manager = new \ZeroBoiler\Enums\EnumManager;
            expect($manager->labels(EmptyDefaultsStatus::class))
                ->toBe(EmptyDefaultsStatus::labels());
        });
    });

    // ─── EnumCache lifecycle ───
    describe('EnumCache lifecycle', function () {
        beforeEach(function () {
            \ZeroBoiler\Enums\EnumCache::resetInstance();
        });

        afterEach(function () {
            \ZeroBoiler\Enums\EnumCache::resetInstance();
        });

        it('singleton returns same instance', function () {
            $a = \ZeroBoiler\Enums\EnumCache::getInstance();
            $b = \ZeroBoiler\Enums\EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('flush() clears all cached entries', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->set('TestEnum', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            expect($cache->has('TestEnum'))->toBeTrue();
            \ZeroBoiler\Enums\EnumCache::flush();
            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('TTL of 0 disables caching', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set('TestEnum', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('clearClass() removes only the specified class', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $metadata = [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ];
            $cache->set('ClassA', $metadata);
            $cache->set('ClassB', $metadata);
            $cache->clearClass('ClassA');
            expect($cache->has('ClassA'))->toBeFalse();
            expect($cache->has('ClassB'))->toBeTrue();
        });
    });

    // ─── EnumMetadataResolver invalidation ───
    describe('EnumMetadataResolver invalidation', function () {
        it('invalidate() clears cache for specific class', function () {
            // First resolve to populate cache
            EnumMetadataResolver::resolve(EmptyDefaultsStatus::class);

            // Invalidate
            EnumMetadataResolver::invalidate(EmptyDefaultsStatus::class);

            // Re-resolve — should rebuild from reflection
            $meta = EnumMetadataResolver::resolve(EmptyDefaultsStatus::class);
            expect($meta)->toBeArray();
            expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        });

        it('invalidateAll() clears all enum metadata', function () {
            EnumMetadataResolver::resolve(EmptyDefaultsStatus::class);
            EnumMetadataResolver::resolve(MixedTicketType::class);

            EnumMetadataResolver::invalidateAll();

            // Both should re-resolve without error
            expect(EnumMetadataResolver::resolve(EmptyDefaultsStatus::class))->toBeArray();
            expect(EnumMetadataResolver::resolve(MixedTicketType::class))->toBeArray();
        });

        it('throws LogicException for non-enum classes', function () {
            expect(fn () => EnumMetadataResolver::resolve('stdClass'))
                ->toThrow(\LogicException::class);
        });
    });

    // ─── InvalidEnumException ───
    describe('InvalidEnumException', function () {
        it('value() creates with correct message', function () {
            $e = InvalidEnumException::value('App\Enums\Status', 'invalid');
            expect($e->getMessage())->toBeString()->toContain('invalid');
            expect($e->getMessage())->toContain('App\Enums\Status');
        });

        it('forName() creates with correct message', function () {
            $e = InvalidEnumException::forName('App\Enums\Status', 'UNKNOWN');
            expect($e->getMessage())->toContain('UNKNOWN');
            expect($e->getMessage())->toContain('App\Enums\Status');
        });

        it('__toString() returns class name and message', function () {
            $e = InvalidEnumException::forName('App\Enums\Status', 'UNKNOWN');
            $str = (string) $e;
            expect($str)->toBeString();
            expect($str)->toContain('InvalidEnumException');
        });

        it('value() handles null display correctly', function () {
            $e = InvalidEnumException::value('App\Enums\Status', null);
            expect($e->getMessage())->toContain('null');
        });
    });

    // ─── EnumCast edge cases ───
    describe('EnumCast edge cases', function () {
        it('get() returns null for null value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(EmptyDefaultsStatus::class);
            $result = $cast->get(new stdClass(), 'status', null, []);
            expect($result)->toBeNull();
        });

        it('get() returns null for non-matching value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(EmptyDefaultsStatus::class);
            $result = $cast->get(new stdClass(), 'status', 'nonexistent', []);
            expect($result)->toBeNull();
        });

        it('get() returns correct enum for valid value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(EmptyDefaultsStatus::class);
            $result = $cast->get(new stdClass(), 'status', 'draft', []);
            expect($result)->toBe(EmptyDefaultsStatus::DRAFT);
        });

        it('set() returns null for null value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(EmptyDefaultsStatus::class);
            $result = $cast->set(new stdClass(), 'status', null, []);
            expect($result)->toBeNull();
        });

        it('set() returns backed value for enum instance', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(EmptyDefaultsStatus::class);
            $result = $cast->set(new stdClass(), 'status', EmptyDefaultsStatus::DRAFT, []);
            expect($result)->toBe('draft');
        });

        it('set() throws for wrong enum type', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(EmptyDefaultsStatus::class);
            expect(fn () => $cast->set(new stdClass(), 'status', MixedTicketType::CRITICAL_BUG, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns backed value for enum instance', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(EmptyDefaultsStatus::class);
            $result = $cast->serialize(new stdClass(), 'status', EmptyDefaultsStatus::DRAFT, []);
            expect($result)->toBe('draft');
        });

        it('serialize() returns raw value for non-enum values', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(EmptyDefaultsStatus::class);
            expect($cast->serialize(new stdClass(), 'status', 'draft', []))->toBe('draft');
            expect($cast->serialize(new stdClass(), 'status', null, []))->toBeNull();
        });
    });

    // ─── select uniqueness ───
    describe('Select option uniqueness', function () {
        it('forSelect() values are unique for all fixture enums', function () {
            $enums = [
                EmptyDefaultsStatus::class,
                MixedTicketType::class,
                NumericStatusCode::class,
                ZeroBackedPriority::class,
            ];

            foreach ($enums as $enumClass) {
                $values = array_column($enumClass::forSelect(), 'value');
                expect(array_unique($values))->toEqual($values);
            }
        });
    });

    // ─── Type system compliance ───
    describe('Type system compliance', function () {
        it('string-backed enum values are all strings', function () {
            foreach (EmptyDefaultsStatus::values() as $value) {
                expect($value)->toBeString();
            }
        });

        it('int-backed enum values are all integers', function () {
            foreach (MixedTicketType::values() as $value) {
                expect($value)->toBeInt();
            }
        });

        it('pure enum values are all case name strings', function () {
            foreach (SingletonMode::values() as $value) {
                expect($value)->toBeString();
            }
        });

        it('all fixture enums use HasEnumMetadata trait', function () {
            $enums = [
                EmptyDefaultsStatus::class,
                MixedTicketType::class,
                NumericStatusCode::class,
                ZeroBackedPriority::class,
                SingletonMode::class,
                SingleCaseToggle::class,
                CamelCasePriority::class,
            ];

            foreach ($enums as $enumClass) {
                expect($enumClass::cases())->not->toBeEmpty();
                expect(method_exists($enumClass, 'label'))->toBeTrue();
                expect(method_exists($enumClass, 'forSelect'))->toBeTrue();
                expect(method_exists($enumClass, 'forApi'))->toBeTrue();
            }
        });
    });
});
