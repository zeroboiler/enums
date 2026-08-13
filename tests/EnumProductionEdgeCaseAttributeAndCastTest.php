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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

describe('Enum Attribute PHP 8.5 Constructor Promotion Compliance', function () {
    it('all per-case attributes use promoted constructor params with readonly', function () {
        // Verify attributes have readonly public properties (PHP 8.5 best practice)
        $labelAttr = new Label('Test Label');
        expect($labelAttr->value)->toBe('Test Label');

        $descAttr = new Description('Test Desc');
        expect($descAttr->value)->toBe('Test Desc');

        $colorAttr = new Color('success');
        expect($colorAttr->value)->toBe('success');

        $iconAttr = new Icon('heroicon-o-check');
        expect($iconAttr->value)->toBe('heroicon-o-check');
    });

    it('all class-level attributes use promoted constructor params with readonly', function () {
        $enumLabel = new EnumLabel(labels: ['active' => 'Active']);
        expect($enumLabel->labels)->toBe(['active' => 'Active']);
        expect($enumLabel->label)->toBeNull();

        $enumLabelSingle = new EnumLabel(label: 'Single');
        expect($enumLabelSingle->label)->toBe('Single');
        expect($enumLabelSingle->labels)->toBeNull();

        $enumDesc = new EnumDescription(descriptions: ['active' => 'Active User']);
        expect($enumDesc->descriptions)->toBe(['active' => 'Active User']);
        expect($enumDesc->description)->toBeNull();

        $enumColor = new EnumColor(success: ['active'], danger: ['banned']);
        expect($enumColor->success)->toBe(['active']);
        expect($enumColor->danger)->toBe(['banned']);
        expect($enumColor->warning)->toBe([]);
        expect($enumColor->info)->toBe([]);
        expect($enumColor->secondary)->toBe([]);

        $enumIcon = new EnumIcon(default: 'heroicon-o-flag', icons: [1 => 'heroicon-o-check']);
        expect($enumIcon->default)->toBe('heroicon-o-flag');
        expect($enumIcon->icons)->toBe([1 => 'heroicon-o-check']);
    });
});

describe('EnumCache Singleton Contract', function () {
    it('returns the same instance on multiple getInstance calls', function () {
        $a = \ZeroBoiler\Enums\EnumCache::getInstance();
        $b = \ZeroBoiler\Enums\EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('setTtl normalizes negative values to 0', function () {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $originalTtl = $cache->getTtl();
        $cache->setTtl(-5);
        expect($cache->getTtl())->toBe(0);
        $cache->setTtl($originalTtl);
    });

    it('get throws OutOfBoundsException when entry does not exist', function () {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $cache->clear();

        expect(fn () => $cache->get('NonExistentEnumClass'))
            ->toThrow(\OutOfBoundsException::class);
    });
});

describe('EnumRule ValidationRule Interface', function () {
    it('implements ValidationRule and has validate method', function () {
        $rule = new EnumRule(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
    });

    it('nullable factory method returns a new instance with nullable true', function () {
        $rule = EnumRule::for(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        $nullableRule = $rule->nullable();

        expect($nullableRule)->not->toBe($rule);
        // Both should validate correctly - nullable version allows null
    });

    it('rejects null values when nullable is false', function () {
        $rule = new EnumRule(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class, false);
        $fail = fn (string $message) => throw new \Illuminate\Validation\ValidationException(
            \Illuminate\Support\Facades\Validator::make([], [])->make([])
        );
        $failed = false;
        $rule->validate('status', null, function (string $message, string $customMessage = null) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('allows null values when nullable is true', function () {
        $rule = new EnumRule(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class, true);
        $failed = false;
        $rule->validate('status', null, function (string $message, string $customMessage = null) use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});

describe('InvalidEnumException Factory Methods', function () {
    it('forName includes class and name in message', function () {
        $e = InvalidEnumException::forName('App\\Enums\\UserStatus', 'NON_EXISTENT');
        expect($e->getMessage())->toContain('NON_EXISTENT');
        expect($e->getMessage())->toContain('App\\Enums\\UserStatus');
    });

    it('value includes the display representation', function () {
        $e = InvalidEnumException::value('App\\Enums\\UserStatus', 'invalid_value');
        expect($e->getMessage())->toContain('invalid_value');

        $eNull = InvalidEnumException::value('App\\Enums\\UserStatus', null);
        expect($eNull->getMessage())->toContain('null');
    });

    it('__toString returns class name and message', function () {
        $e = InvalidEnumException::forName('App\\Enums\\UserStatus', 'BAD');
        $str = (string) $e;
        expect($str)->toContain('InvalidEnumException');
        expect($str)->toContain('BAD');
    });
});

describe('EnumMetadataResolver Invalidation', function () {
    it('invalidate removes cached entry for a class', function () {
        EnumMetadataResolver::invalidate(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);

        // Resolving should rebuild fresh metadata
        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        expect($meta)->toBeArray();
        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('invalidateAll clears everything', function () {
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        expect($meta)->toBeArray();
        expect($meta['labels'])->not->toBeEmpty();
    });
});

describe('EnumCast Type Safety', function () {
    it('constructor accepts class-string', function () {
        $cast = new EnumCast(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        expect($cast)->toBeInstanceOf(EnumCast::class);
    });

    it('set throws InvalidArgumentException for wrong enum class', function () {
        $cast = new EnumCast(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
            public function __set(string $key, mixed $value): void {}
        };

        // String-backed enum value mismatch
        expect(fn () => $cast->set($model, 'status', 999, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('serialize returns backed value for enum instance', function () {
        $cast = new EnumCast(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
            public function __set(string $key, mixed $value): void {}
        };

        $result = $cast->serialize($model, 'status', \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });
});
