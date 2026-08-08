<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;
use ZeroBoiler\Enums\Attributes\EnumIcon;

// ── Fixtures ──────────────────────────────────────────────────

#[\ZeroBoiler\Enums\Attributes\EnumLabel(labels: ['x' => 'X Label', 'y' => 'Y Label'])]
#[\ZeroBoiler\Enums\Attributes\EnumColor(success: ['x', 'y'])]
#[\ZeroBoiler\Enums\Attributes\EnumDescription(descriptions: ['x' => 'X desc'])]
#[\ZeroBoiler\Enums\Attributes\EnumIcon(default: 'heroicon-o-star')]
enum FullMetadataEnum: string
{
    use HasEnumMetadata;

    case X = 'x';
    case Y = 'y';
    case Z = 'z';
}

enum SingleCaseEnum: string
{
    use HasEnumMetadata;

    case ONLY = 'only';
}

enum LargeIntEnum: int
{
    use HasEnumMetadata;

    #[\ZeroBoiler\Enums\Attributes\Label('First Priority')]
    case P1 = 1;

    #[\ZeroBoiler\Enums\Attributes\Label('Second Priority')]
    case P2 = 2;

    #[\ZeroBoiler\Enums\Attributes\Label('Third Priority')]
    #[\ZeroBoiler\Enums\Attributes\Color('danger')]
    case P3 = 3;

    case P4 = 4;

    case P5 = 5;
}

enum NoAttributesEnum: string
{
    use HasEnumMetadata;

    case ALPHA = 'alpha';
    case BETA = 'beta';
}

#[\ZeroBoiler\Enums\Attributes\EnumIcon(default: 'heroicon-o-globe')]
enum PureWithDefaultIcon
{
    use HasEnumMetadata;

    case FEATURE_A;
    case FEATURE_B;
}

describe('Enum production readiness comprehensive audit', function () {
    // ── EnumCache singleton behavior ──────────────────────────
    describe('EnumCache singleton immutability', function () {
        beforeEach(function () {
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::resetInstance();
        });

        it('returns same instance on multiple getInstance() calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('set() followed by has() and get() roundtrip', function () {
            $cache = EnumCache::getInstance();
            $metadata = [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ];

            $cache->set('TestEnum', $metadata);

            expect($cache->has('TestEnum'))->toBeTrue();
            expect($cache->get('TestEnum'))->toBe($metadata);
        });

        it('get() throws OutOfBoundsException for missing class', function () {
            $cache = EnumCache::getInstance();

            expect(fn () => $cache->get('NonExistent'))
                ->toThrow(\OutOfBoundsException::class);
        });

        it('clear() removes all entries', function () {
            $cache = EnumCache::getInstance();
            $cache->set('A', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('B', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clear();

            expect($cache->has('A'))->toBeFalse();
            expect($cache->has('B'))->toBeFalse();
        });

        it('clearClass() removes only the specified class', function () {
            $cache = EnumCache::getInstance();
            $cache->set('KeepMe', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);
            $cache->set('RemoveMe', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            $cache->clearClass('RemoveMe');

            expect($cache->has('KeepMe'))->toBeTrue();
            expect($cache->has('RemoveMe'))->toBeFalse();
        });

        it('static flush() delegates to singleton clear()', function () {
            $cache = EnumCache::getInstance();
            $cache->set('X', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            EnumCache::flush();

            expect($cache->has('X'))->toBeFalse();
        });

        it('resetInstance() creates a new singleton', function () {
            $a = EnumCache::getInstance();
            $a->set('Test', ['labels' => ['v' => 'L'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            EnumCache::resetInstance();
            $b = EnumCache::getInstance();

            expect($a)->not->toBe($b);
            expect($b->has('Test'))->toBeFalse();
        });

        it('has() returns false for zero TTL even after set()', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set('Test', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

            expect($cache->has('Test'))->toBeFalse();
        });
    });

    // ── EnumCache singleton clone/wakeup prevention ───────────
    describe('EnumCache singleton clone/wakeup prevention', function () {
        beforeEach(function () {
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::resetInstance();
        });

        it('clone throws RuntimeException', function () {
            $cache = EnumCache::getInstance();

            expect(fn () => clone $cache)
                ->toThrow(\RuntimeException::class, 'cannot be cloned');
        });

        it('wakeup throws RuntimeException', function () {
            $cache = EnumCache::getInstance();

            expect(fn () => $cache->__wakeup())
                ->toThrow(\RuntimeException::class, 'cannot be unserialized');
        });
    });

    // ── EnumMetadataResolver full metadata ────────────────────
    describe('EnumMetadataResolver with FullMetadataEnum', function () {
        it('resolves class-level EnumLabel labels', function () {
            $meta = EnumMetadataResolver::resolve(FullMetadataEnum::class);

            expect($meta['labels']['x'])->toBe('X Label');
            expect($meta['labels']['y'])->toBe('Y Label');
        });

        it('resolves class-level EnumColor colors', function () {
            $meta = EnumMetadataResolver::resolve(FullMetadataEnum::class);

            expect($meta['colors']['x'])->toBe('success');
            expect($meta['colors']['y'])->toBe('success');
        });

        it('resolves class-level EnumDescription descriptions', function () {
            $meta = EnumMetadataResolver::resolve(FullMetadataEnum::class);

            expect($meta['descriptions']['x'])->toBe('X desc');
        });

        it('applies EnumIcon default to all cases', function () {
            $meta = EnumMetadataResolver::resolve(FullMetadataEnum::class);

            expect($meta['icons']['x'])->toBe('heroicon-o-star');
            expect($meta['icons']['y'])->toBe('heroicon-o-star');
            expect($meta['icons']['z'])->toBe('heroicon-o-star');
        });

        it('Z gets auto-generated label (not in class-level map)', function () {
            expect(FullMetadataEnum::Z->label())->toBe('Z');
        });

        it('Z gets default secondary color', function () {
            expect(FullMetadataEnum::Z->color())->toBe('secondary');
        });
    });

    // ── Full metadata trait methods ────────────────────────────
    describe('FullMetadataEnum trait methods', function () {
        it('label() returns class-level then auto-generated', function () {
            expect(FullMetadataEnum::X->label())->toBe('X Label');
            expect(FullMetadataEnum::Z->label())->toBe('Z');
        });

        it('description() returns class-level or null', function () {
            expect(FullMetadataEnum::X->description())->toBe('X desc');
            expect(FullMetadataEnum::Z->description())->toBeNull();
        });

        it('color() returns class-level or secondary', function () {
            expect(FullMetadataEnum::X->color())->toBe('success');
            expect(FullMetadataEnum::Z->color())->toBe('secondary');
        });

        it('icon() returns class-level default or null', function () {
            expect(FullMetadataEnum::X->icon())->toBe('heroicon-o-star');
        });
    });

    // ── EnumIcon on pure enum ──────────────────────────────────
    describe('EnumIcon default on pure enum', function () {
        it('all cases get the default icon', function () {
            expect(PureWithDefaultIcon::FEATURE_A->icon())->toBe('heroicon-o-globe');
            expect(PureWithDefaultIcon::FEATURE_B->icon())->toBe('heroicon-o-globe');
        });
    });

    // ── NoAttributesEnum auto-generation ───────────────────────
    describe('enum with no attributes', function () {
        it('all labels are auto-generated', function () {
            expect(NoAttributesEnum::ALPHA->label())->toBe('Alpha');
            expect(NoAttributesEnum::BETA->label())->toBe('Beta');
        });

        it('all colors default to secondary', function () {
            expect(NoAttributesEnum::ALPHA->color())->toBe('secondary');
        });

        it('all icons are null', function () {
            expect(NoAttributesEnum::ALPHA->icon())->toBeNull();
        });

        it('all descriptions are null', function () {
            expect(NoAttributesEnum::ALPHA->description())->toBeNull();
        });
    });

    // ── SingleCaseEnum ───────────────────────────────────────
    describe('single case enum', function () {
        it('forSelect returns single option', function () {
            $select = SingleCaseEnum::forSelect();

            expect($select)->toHaveCount(1);
            expect($select[0]['value'])->toBe('only');
            expect($select[0]['label'])->toBe('Only');
        });

        it('forApi returns single entry', function () {
            $api = SingleCaseEnum::forApi();

            expect($api)->toHaveCount(1);
            expect($api[0]['name'])->toBe('ONLY');
        });

        it('values returns single element', function () {
            expect(SingleCaseEnum::values())->toEqual(['only']);
        });

        it('labels returns single element', function () {
            expect(SingleCaseEnum::labels())->toEqual(['Only']);
        });

        it('in() with self works', function () {
            expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
        });

        it('is() with self works', function () {
            expect(SingleCaseEnum::ONLY->is(SingleCaseEnum::ONLY))->toBeTrue();
        });
    });

    // ── LargeIntEnum bulk methods ──────────────────────────────
    describe('LargeIntEnum with 5 cases', function () {
        it('forSelect preserves declaration order with int values', function () {
            $select = LargeIntEnum::forSelect();

            expect($select)->toHaveCount(5);
            expect($select[0]['value'])->toBe(1);
            expect($select[4]['value'])->toBe(5);
        });

        it('labels() returns correct labels for all cases', function () {
            $labels = LargeIntEnum::labels();

            expect($labels[0])->toBe('First Priority');
            expect($labels[1])->toBe('Second Priority');
            expect($labels[2])->toBe('Third Priority');
            expect($labels[3])->toBe('P4');
            expect($labels[4])->toBe('P5');
        });

        it('colors reflect per-case overrides', function () {
            expect(LargeIntEnum::P3->color())->toBe('danger');
            expect(LargeIntEnum::P1->color())->toBe('secondary');
        });

        it('forApi has all keys for each entry', function () {
            $api = LargeIntEnum::forApi();

            foreach ($api as $entry) {
                expect($entry)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            }
        });

        it('values() returns int backed values', function () {
            expect(LargeIntEnum::values())->toEqual([1, 2, 3, 4, 5]);
        });
    });

    // ── EnumTestGenerator output structure ──────────────────
    describe('EnumTestGenerator output', function () {
        it('generates valid PHP for string-backed enum', function () {
            $output = EnumTestGenerator::generate(FullMetadataEnum::class);

            expect($output)->toContain('declare(strict_types=1)');
            expect($output)->toContain('describe(');
            expect($output)->toContain('it(');
            expect($output)->toContain('FullMetadataEnum::cases()');
            expect($output)->toContain('FullMetadataEnum::forSelect()');
            expect($output)->toContain('FullMetadataEnum::forApi()');
            expect($output)->toContain('FullMetadataEnum::tryFromName');
            expect($output)->toContain('FullMetadataEnum::hasCase');
            expect($output)->toContain('FullMetadataEnum::values()');
            expect($output)->toContain('FullMetadataEnum::labels()');
        });

        it('generates per-case label and color tests', function () {
            $output = EnumTestGenerator::generate(LargeIntEnum::class);

            expect($output)->toContain("has a label for case P1");
            expect($output)->toContain("has a color for case P1");
            expect($output)->toContain("has a label for case P5");
        });

        it('generates comparison tests for enums with >= 2 cases', function () {
            $output = EnumTestGenerator::generate(LargeIntEnum::class);

            expect($output)->toContain('supports is() comparison with instance');
            expect($output)->toContain('supports isNot() comparison');
            expect($output)->toContain('supports in() group matching');
            expect($output)->toContain('supports tryFromLabel reverse lookup');
        });

        it('does NOT generate comparison tests for single-case enum', function () {
            $output = EnumTestGenerator::generate(SingleCaseEnum::class);

            expect($output)->not->toContain('supports is() comparison with instance');
        });
    });

    // ── EnumRule with int-backed enum ────────────────────────
    describe('EnumRule with int-backed enum', function () {
        it('passes for valid int value', function () {
            $rule = EnumRule::for(LargeIntEnum::class);
            $fail = false;

            $rule->validate('priority', 1, function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });

        it('fails for string value on int-backed enum', function () {
            $rule = EnumRule::for(LargeIntEnum::class);
            $fail = false;

            $rule->validate('priority', '1', function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });

        it('fails for out-of-range int value', function () {
            $rule = EnumRule::for(LargeIntEnum::class);
            $fail = false;

            $rule->validate('priority', 999, function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });

        it('fails null when not nullable', function () {
            $rule = EnumRule::for(LargeIntEnum::class);
            $fail = false;

            $rule->validate('priority', null, function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });

        it('passes null when nullable', function () {
            $rule = EnumRule::for(LargeIntEnum::class)->nullable();
            $fail = false;

            $rule->validate('priority', null, function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });

        it('error message includes allowed values for enum with values() method', function () {
            $rule = EnumRule::for(LargeIntEnum::class);
            $failMsg = '';

            $rule->validate('priority', 'invalid', function (string $attribute, string|null $message = null) use (&$failMsg): void {
                $failMsg = $message ?? '';
            });

            expect($failMsg)->toContain('Allowed values');
            expect($failMsg)->toContain('1');
            expect($failMsg)->toContain('5');
        });
    });

    // ── EnumRule with string-backed enum ──────────────────────
    describe('EnumRule with string-backed enum', function () {
        it('passes for valid string value', function () {
            $rule = EnumRule::for(FullMetadataEnum::class);
            $fail = false;

            $rule->validate('status', 'x', function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeFalse();
        });

        it('fails for int value on string-backed enum', function () {
            $rule = EnumRule::for(FullMetadataEnum::class);
            $fail = false;

            $rule->validate('status', 123, function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });

        it('fails for non-existent string value', function () {
            $rule = EnumRule::for(FullMetadataEnum::class);
            $fail = false;

            $rule->validate('status', 'nonexistent', function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });
    });

    // ── tryFromLabel case insensitivity ───────────────────────
    describe('tryFromLabel case insensitivity', function () {
        it('matches regardless of case', function () {
            expect(FullMetadataEnum::tryFromLabel('x label'))->toBe(FullMetadataEnum::X);
            expect(FullMetadataEnum::tryFromLabel('X LABEL'))->toBe(FullMetadataEnum::X);
            expect(FullMetadataEnum::tryFromLabel('X Label'))->toBe(FullMetadataEnum::X);
        });

        it('returns null for non-existent label', function () {
            expect(FullMetadataEnum::tryFromLabel('Nonexistent Label'))->toBeNull();
        });

        it('returns null for empty string', function () {
            expect(FullMetadataEnum::tryFromLabel(''))->toBeNull();
        });
    });

    // ── InvalidEnumException factory methods ───────────────────
    describe('InvalidEnumException named constructors', function () {
        it('value() formats message correctly', function () {
            $e = InvalidEnumException::value(LargeIntEnum::class, 999);

            expect($e->getMessage())->toContain('999');
            expect($e->getMessage())->toContain(LargeIntEnum::class);
        });

        it('value() handles null value', function () {
            $e = InvalidEnumException::value(LargeIntEnum::class, null);

            expect($e->getMessage())->toContain('null');
        });

        it('value() handles string value', function () {
            $e = InvalidEnumException::value(FullMetadataEnum::class, 'z');

            expect($e->getMessage())->toContain('z');
        });

        it('forName() formats message correctly', function () {
            $e = InvalidEnumException::forName(LargeIntEnum::class, 'P99');

            expect($e->getMessage())->toContain('P99');
            expect($e->getMessage())->toContain(LargeIntEnum::class);
        });
    });

    // ── Cross-fixture cache isolation ──────────────────────────
    describe('cache isolation between different enums', function () {
        beforeEach(function () {
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::resetInstance();
        });

        it('resolving one enum does not pollute another', function () {
            $meta1 = EnumMetadataResolver::resolve(FullMetadataEnum::class);
            $meta2 = EnumMetadataResolver::resolve(NoAttributesEnum::class);

            expect($meta1['labels'])->toHaveKey('x');
            expect($meta1['labels'])->not->toHaveKey('alpha');
            expect($meta2['labels'])->toHaveKey('alpha');
            expect($meta2['labels'])->not->toHaveKey('x');
        });

        it('invalidating one enum does not affect another', function () {
            EnumMetadataResolver::resolve(FullMetadataEnum::class);
            EnumMetadataResolver::resolve(NoAttributesEnum::class);

            EnumMetadataResolver::invalidate(FullMetadataEnum::class);

            $cache = EnumCache::getInstance();
            expect($cache->has(FullMetadataEnum::class))->toBeFalse();
            expect($cache->has(NoAttributesEnum::class))->toBeTrue();
        });
    });

    // ── EnumRule for() named constructor ─────────────────────
    describe('EnumRule named constructor', function () {
        it('for() creates non-nullable rule', function () {
            $rule = EnumRule::for(FullMetadataEnum::class);

            $fail = false;
            $rule->validate('field', null, function (string $attribute, string|null $message = null) use (&$fail): void {
                $fail = true;
            });

            expect($fail)->toBeTrue();
        });

        it('nullable() returns new instance', function () {
            $original = EnumRule::for(FullMetadataEnum::class);
            $nullable = $original->nullable();

            expect($nullable)->not->toBe($original);
        });
    });

    // ── EnumCast::serialize output ─────────────────────────────
    describe('EnumCast serialize output type', function () {
        it('serializes backed enum value as-is', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(FullMetadataEnum::class);
            $model = new class {
                public function __construct(public array $attributes = []) {}
            };

            $result = $cast->serialize($model, 'status', FullMetadataEnum::X, []);

            expect($result)->toBe('x');
        });

        it('serializes raw value as-is', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(FullMetadataEnum::class);
            $model = new class {
                public function __construct(public array $attributes = []) {}
            };

            $result = $cast->serialize($model, 'status', 'x', []);

            expect($result)->toBe('x');
        });
    });

    // ── EnumCast::set type validation ─────────────────────────
    describe('EnumCast::set type validation', function () {
        it('rejects wrong enum type', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(FullMetadataEnum::class);
            $model = new class {
                public function __construct(public array $attributes = []) {}
            };

            expect(fn () => $cast->set($model, 'status', LargeIntEnum::P1, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('rejects non-scalar non-enum value', function () {
            $cast = new \ZeroBoiler\Enums\Casts\EnumCast(FullMetadataEnum::class);
            $model = new class {
                public function __construct(public array $attributes = []) {}
            };

            expect(fn () => $cast->set($model, 'status', ['array'], []))
                ->toThrow(\InvalidArgumentException::class);
        });
    });
});
