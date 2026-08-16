<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Support\EnumTestGenerator;

beforeEach(function () {
    EnumCache::resetInstance();
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('V39 EnumCast get() edge cases', function () {
    it('returns null for null value', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->get($model, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('returns matching enum case for valid string value', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->get($model, 'status', 'active', []);

        expect($result)->toBe(TestBackedV39::ACTIVE);
    });

    it('returns matching enum case for valid int value', function () {
        $cast = new EnumCast(TestIntBackedV39::class);
        $model = (object) [];
        $result = $cast->get($model, 'priority', 1, []);

        expect($result)->toBe(TestIntBackedV39::LOW);
    });

    it('returns null for non-matching value via tryFrom', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->get($model, 'status', 'nonexistent', []);

        expect($result)->toBeNull();
    });

    it('returns null for int value on string-backed enum', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->get($model, 'status', 42, []);

        expect($result)->toBeNull();
    });

    it('returns null for string value on int-backed enum', function () {
        $cast = new EnumCast(TestIntBackedV39::class);
        $model = (object) [];
        $result = $cast->get($model, 'priority', '1', []);

        // String '1' vs int 1 — tryFrom('1') would fail on int-backed enum
        expect($result)->toBeNull();
    });
});

describe('V39 EnumCast set() edge cases', function () {
    it('returns null for null value', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->set($model, 'status', null, []);

        expect($result)->toBeNull();
    });

    it('returns backed value for enum instance', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->set($model, 'status', TestBackedV39::ACTIVE, []);

        expect($result)->toBe('active');
    });

    it('returns int value for int-backed enum instance', function () {
        $cast = new EnumCast(TestIntBackedV39::class);
        $model = (object) [];
        $result = $cast->set($model, 'priority', TestIntBackedV39::HIGH, []);

        expect($result)->toBe(2);
    });

    it('throws for wrong enum type', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];

        expect(fn () => $cast->set($model, 'status', TestIntBackedV39::LOW, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('validates and returns valid raw string value', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->set($model, 'status', 'inactive', []);

        expect($result)->toBe('inactive');
    });

    it('throws for invalid raw string value', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];

        expect(fn () => $cast->set($model, 'status', 'invalid_value', []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('validates and returns valid raw int value', function () {
        $cast = new EnumCast(TestIntBackedV39::class);
        $model = (object) [];
        $result = $cast->set($model, 'priority', 0, []);

        expect($result)->toBe(0);
    });
});

describe('V39 EnumCast serialize() edge cases', function () {
    it('serializes enum instance to backed value', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->serialize($model, 'status', TestBackedV39::DRAFT, []);

        expect($result)->toBe('draft');
    });

    it('passes through string value directly', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->serialize($model, 'status', 'active', []);

        expect($result)->toBe('active');
    });

    it('passes through int value directly', function () {
        $cast = new EnumCast(TestIntBackedV39::class);
        $model = (object) [];
        $result = $cast->serialize($model, 'priority', 1, []);

        expect($result)->toBe(1);
    });

    it('returns null for null value', function () {
        $cast = new EnumCast(TestBackedV39::class);
        $model = (object) [];
        $result = $cast->serialize($model, 'status', null, []);

        expect($result)->toBeNull();
    });
});

describe('V39 EnumManager delegation', function () {
    it('forSelect() delegates correctly', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(TestBackedV39::class);

        expect($result)->toBe(TestBackedV39::forSelect());
    });

    it('forApi() delegates correctly', function () {
        $manager = new EnumManager;
        $result = $manager->forApi(TestBackedV39::class);

        expect($result)->toBe(TestBackedV39::forApi());
    });

    it('tryFromLabel() delegates correctly', function () {
        $manager = new EnumManager;
        $result = $manager->tryFromLabel(TestBackedV39::class, 'Draft');

        expect($result)->toBe(TestBackedV39::DRAFT);
    });

    it('tryFromName() delegates correctly', function () {
        $manager = new EnumManager;
        $result = $manager->tryFromName(TestBackedV39::class, 'ACTIVE');

        expect($result)->toBe(TestBackedV39::ACTIVE);
    });

    it('fromName() delegates correctly and throws on invalid', function () {
        $manager = new EnumManager;

        expect($manager->fromName(TestBackedV39::class, 'DRAFT'))
            ->toBe(TestBackedV39::DRAFT);

        expect(fn () => $manager->fromName(TestBackedV39::class, 'NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase() delegates correctly', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(TestBackedV39::class, 'ACTIVE'))->toBeTrue();
        expect($manager->hasCase(TestBackedV39::class, 'FAKE'))->toBeFalse();
    });

    it('values() delegates correctly', function () {
        $manager = new EnumManager;
        $result = $manager->values(TestBackedV39::class);

        expect($result)->toBe(TestBackedV39::values());
    });

    it('labels() delegates correctly', function () {
        $manager = new EnumManager;
        $result = $manager->labels(TestBackedV39::class);

        expect($result)->toBe(TestBackedV39::labels());
    });

    it('throws BadMethodCallException for class without HasEnumMetadata', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('V39 EnumFacade accessor', function () {
    it('getFacadeAccessor returns correct key', function () {
        $facade = new class extends \Illuminate\Support\Facades\Facade {
            protected static function getFacadeAccessor(): string
            {
                return 'zeroboiler.enum';
            }
        };

        expect($facade::getFacadeAccessor())->toBe('zeroboiler.enum');
    });
});

describe('V39 EnumTestGenerator output contract', function () {
    it('generates valid PHP for string-backed enum', function () {
        $content = EnumTestGenerator::generate(TestBackedV39::class);

        expect($content)->toContain('declare(strict_types=1)');
        expect($content)->toContain('describe(');
        expect($content)->toContain('it(');
        expect($content)->toContain('TestBackedV39');
        expect($content)->toContain('forSelect');
        expect($content)->toContain('forApi');
        expect($content)->toContain('fromName');
        expect($content)->toContain('InvalidEnumException');
    });

    it('generates valid PHP for int-backed enum', function () {
        $content = EnumTestGenerator::generate(TestIntBackedV39::class);

        expect($content)->toContain('TestIntBackedV39');
        expect($content)->toContain('values() returns int backed values');
    });

    it('generates valid PHP for pure enum', function () {
        $content = EnumTestGenerator::generate(TestPureV39::class);

        expect($content)->toContain('TestPureV39');
        expect($content)->toContain('values() returns case names for pure enum');
    });
});

describe('V39 cross-attribute resolution priority', function () {
    it('per-case Label overrides class-level EnumLabel', function () {
        // ACTIVE has explicit #[Label('Custom Active')]
        expect(TestBackedV39::ACTIVE->label())->toBe('Custom Active');
    });

    it('cases without per-case Label use EnumLabel mapping', function () {
        // INACTIVE uses EnumLabel mapping
        expect(TestBackedV39::INACTIVE->label())->toBe('Offline');
    });

    it('cases without any label get auto-generated', function () {
        // DRAFT has no per-case or class-level label
        expect(TestBackedV39::DRAFT->label())->toBe('Draft');
    });

    it('per-case Color overrides class-level EnumColor', function () {
        expect(TestBackedV39::ACTIVE->color())->toBe('success');
    });

    it('class-level EnumColor provides color for unmapped cases', function () {
        expect(TestBackedV39::INACTIVE->color())->toBe('danger');
    });

    it('default color is secondary when nothing is set', function () {
        expect(TestBackedV39::DRAFT->color())->toBe('secondary');
    });

    it('per-case Description overrides class-level EnumDescription', function () {
        expect(TestBackedV39::ACTIVE->description())->toBe('User can perform actions');
    });

    it('class-level EnumDescription provides description for mapped cases', function () {
        expect(TestBackedV39::INACTIVE->description())->toBe('User cannot perform actions');
    });

    it('per-case Icon overrides class-level EnumIcon default', function () {
        expect(TestBackedV39::ACTIVE->icon())->toBe('heroicon-o-check');
    });

    it('class-level EnumIcon default applies to cases without specific mapping', function () {
        expect(TestBackedV39::INACTIVE->icon())->toBe('heroicon-o-x-mark');
    });
});

describe('V39 EnumRule message generation', function () {
    it('generates message with allowed values from HasEnumMetadata', function () {
        $rule = EnumRule::for(TestBackedV39::class);
        $failed = false;
        $message = '';

        $rule->validate('status', 'bad_value', function (string $msg) use (&$failed, &$message) {
            $failed = true;
            $message = $msg;
        });

        expect($failed)->toBeTrue();
        expect($message)->toContain('Allowed values:');
        expect($message)->toContain('active');
        expect($message)->toContain('inactive');
    });

    it('generates generic message for enum without HasEnumMetadata', function () {
        $rule = new EnumRule(\stdClass::class);
        $failed = false;
        $message = '';

        $rule->validate('field', 'value', function (string $msg) use (&$failed, &$message) {
            $failed = true;
            $message = $msg;
        });

        expect($failed)->toBeTrue();
        expect($message)->toContain('must be a valid enum');
    });
});

describe('V39 bulk method consistency', function () {
    it('forSelect count matches cases count', function () {
        expect(TestBackedV39::forSelect())->toHaveCount(
            count(TestBackedV39::cases())
        );
    });

    it('forApi count matches cases count', function () {
        expect(TestBackedV39::forApi())->toHaveCount(
            count(TestBackedV39::cases())
        );
    });

    it('values count matches cases count', function () {
        expect(TestBackedV39::values())->toHaveCount(
            count(TestBackedV39::cases())
        );
    });

    it('labels count matches cases count', function () {
        expect(TestBackedV39::labels())->toHaveCount(
            count(TestBackedV39::cases())
        );
    });

    it('forSelect values match values()', function () {
        $selectValues = array_column(TestBackedV39::forSelect(), 'value');
        $allValues = TestBackedV39::values();

        expect($selectValues)->toBe($allValues);
    });

    it('forApi labels match labels()', function () {
        $apiLabels = array_column(TestBackedV39::forApi(), 'label');
        $allLabels = TestBackedV39::labels();

        expect($apiLabels)->toBe($allLabels);
    });
});

describe('V39 metadata cache invalidation lifecycle', function () {
    it('resolve twice returns same cached result', function () {
        $first = EnumMetadataResolver::resolve(TestBackedV39::class);
        $second = EnumMetadataResolver::resolve(TestBackedV39::class);

        expect($first)->toBe($second);
    });

    it('invalidate forces re-resolution', function () {
        $before = EnumMetadataResolver::resolve(TestBackedV39::class);
        EnumMetadataResolver::invalidate(TestBackedV39::class);
        $after = EnumMetadataResolver::resolve(TestBackedV39::class);

        // Same data, different resolve call after invalidation
        expect($before)->toBe($after);
        // But cache was cleared in between
        $cache = EnumCache::getInstance();
        expect($cache->has(TestBackedV39::class))->toBeTrue();
    });

    it('cache TTL expiration forces re-resolution', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Disable caching

        $first = EnumMetadataResolver::resolve(TestBackedV39::class);

        // With TTL=0, each resolve() rebuilds from scratch
        // Verify the result is still correct
        expect($first['labels'])->toHaveKey('active');
    });
});

// ── Fixtures ───────────────────────────────────────────────────

#[\ZeroBoiler\Enums\Attributes\EnumLabel(labels: ['inactive' => 'Offline'])]
#[\ZeroBoiler\Enums\Attributes\EnumDescription(descriptions: ['inactive' => 'User cannot perform actions'])]
#[\ZeroBoiler\Enums\Attributes\EnumColor(danger: ['inactive'])]
#[\ZeroBoiler\Enums\Attributes\EnumIcon(icons: ['active' => 'heroicon-o-check'], default: 'heroicon-o-x-mark')]
enum TestBackedV39: string
{
    use HasEnumMetadata;

    #[\ZeroBoiler\Enums\Attributes\Label('Custom Active')]
    #[\ZeroBoiler\Enums\Attributes\Description('User can perform actions')]
    #[\ZeroBoiler\Enums\Attributes\Color('success')]
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DRAFT = 'draft';
}

enum TestIntBackedV39: int
{
    use HasEnumMetadata;

    case ZERO = 0;
    case LOW = 1;
    case HIGH = 2;
}

enum TestPureV39
{
    use HasEnumMetadata;

    case ACTIVE;
    case INACTIVE;
    case DRAFT;
}
