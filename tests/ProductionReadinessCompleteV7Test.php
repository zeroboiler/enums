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
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;

/**
 * Fixture: Int-backed enum with all attribute combinations.
 */
#[EnumLabel(labels: ['active' => 'Aktif', 'inactive' => 'Pasif'])]
#[EnumColor(success: ['active'], danger: ['inactive'])]
#[EnumIcon(default: 'heroicon-o-circle')]
#[EnumDescription(descriptions: ['active' => 'User is active', 'inactive' => 'User is inactive'])]
enum FullAttributeEnum: int
{
    use HasEnumMetadata;

    #[Label('Aktif Kullanıcı')]
    #[Color('success')]
    #[Description('Aktif kullanıcı açıklama')]
    case ACTIVE = 1;

    #[Label('Pasif Kullanıcı')]
    #[Color('danger')]
    #[Description('Pasif kullanıcı açıklama')]
    case INACTIVE = 0;
}

/**
 * Fixture: Empty string-backed enum for edge cases.
 */
enum EmptyEnum: string
{
    use HasEnumMetadata;

    case DRAFT = '';
    case PUBLISHED = 'published';
}

describe('Production Readiness — Full Attribute Resolution', function () {
    it('resolves class-level label map correctly', function () {
        // Per-case Label should override class-level EnumLabel
        expect(FullAttributeEnum::ACTIVE->label())->toBe('Aktif Kullanıcı');
    });

    it('resolves class-level color map correctly', function () {
        expect(FullAttributeEnum::ACTIVE->color())->toBe('success');
        expect(FullAttributeEnum::INACTIVE->color())->toBe('danger');
    });

    it('resolves class-level default icon', function () {
        expect(FullAttributeEnum::ACTIVE->icon())->toBe('heroicon-o-circle');
        expect(FullAttributeEnum::INACTIVE->icon())->toBe('heroicon-o-circle');
    });

    it('resolves per-case descriptions', function () {
        expect(FullAttributeEnum::ACTIVE->description())->toBe('Aktif kullanıcı açıklama');
        expect(FullAttributeEnum::INACTIVE->description())->toBe('Pasif kullanıcı açıklama');
    });

    it('generates correct forSelect output', function () {
        $select = FullAttributeEnum::forSelect();

        expect($select)->toHaveCount(2);
        expect($select[0])->toHaveKeys(['value', 'label']);
        expect($select[0]['value'])->toBe(1);
    });

    it('generates correct forApi output with all metadata fields', function () {
        $api = FullAttributeEnum::forApi();

        expect($api)->toHaveCount(2);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($api[0]['icon'])->toBe('heroicon-o-circle');
        expect($api[0]['description'])->toBeString();
    });

    it('tryFromName resolves by case name', function () {
        expect(FullAttributeEnum::tryFromName('ACTIVE'))->toBeInstanceOf(FullAttributeEnum::class);
        expect(FullAttributeEnum::tryFromName('NON_EXISTENT'))->toBeNull();
    });

    it('fromName throws for invalid name', function () {
        expect(fn () => FullAttributeEnum::fromName('INVALID'))->toThrow(InvalidEnumException::class);
    });

    it('hasCase checks existence correctly', function () {
        expect(FullAttributeEnum::hasCase('ACTIVE'))->toBeTrue();
        expect(FullAttributeEnum::hasCase('BOGUS'))->toBeFalse();
    });

    it('is() works with instance and string', function () {
        expect(FullAttributeEnum::ACTIVE->is(FullAttributeEnum::ACTIVE))->toBeTrue();
        expect(FullAttributeEnum::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(FullAttributeEnum::ACTIVE->is('INACTIVE'))->toBeFalse();
    });

    it('isNot() negates is() correctly', function () {
        expect(FullAttributeEnum::ACTIVE->isNot(FullAttributeEnum::INACTIVE))->toBeTrue();
        expect(FullAttributeEnum::ACTIVE->isNot('ACTIVE'))->toBeFalse();
    });

    it('in() and notIn() work with mixed arguments', function () {
        expect(FullAttributeEnum::ACTIVE->in([FullAttributeEnum::ACTIVE, 'INACTIVE']))->toBeTrue();
        expect(FullAttributeEnum::ACTIVE->in(['INACTIVE']))->toBeFalse();
        expect(FullAttributeEnum::ACTIVE->notIn(['INACTIVE']))->toBeTrue();
        expect(FullAttributeEnum::ACTIVE->notIn([FullAttributeEnum::ACTIVE]))->toBeFalse();
    });

    it('values() returns backed values for int-backed enum', function () {
        $values = FullAttributeEnum::values();

        expect($values)->toHaveCount(2);
        expect($values)->toContain(1);
        expect($values)->toContain(0);
    });

    it('labels() returns all labels in declaration order', function () {
        $labels = FullAttributeEnum::labels();

        expect($labels)->toHaveCount(2);
        expect($labels[0])->toBe('Aktif Kullanıcı');
        expect($labels[1])->toBe('Pasif Kullanıcı');
    });

    it('tryFromLabel performs case-insensitive lookup', function () {
        expect(FullAttributeEnum::tryFromLabel('Aktif Kullanıcı'))->toBe(FullAttributeEnum::ACTIVE);
        expect(FullAttributeEnum::tryFromLabel('aktif kullanıcı'))->toBe(FullAttributeEnum::ACTIVE);
        expect(FullAttributeEnum::tryFromLabel('nonexistent'))->toBeNull();
    });
});

describe('Production Readiness — Empty String Edge Cases', function () {
    it('handles empty string backed value correctly', function () {
        expect(EmptyEnum::DRAFT->value)->toBe('');
        expect(EmptyEnum::DRAFT->label())->toBeString()->not->toBeEmpty();
    });

    it('tryFrom works with empty string', function () {
        expect(EmptyEnum::tryFrom(''))->toBe(EmptyEnum::DRAFT);
    });

    it('values() includes empty string', function () {
        expect(EmptyEnum::values())->toContain('');
    });
});

describe('Production Readiness — EnumCache Lifecycle', function () {
    beforeEach(function () {
        EnumCache::getInstance()->clear();
    });

    afterEach(function () {
        EnumCache::getInstance()->clear();
    });

    it('cache invalidation via invalidateClass works', function () {
        // Resolve to populate cache
        $meta1 = EmptyEnum::ACTIVE->label();
        EnumCache::getInstance()->clearClass(EmptyEnum::class);

        // Re-resolve after invalidation
        $meta2 = EmptyEnum::ACTIVE->label();

        expect($meta2)->toBe($meta1);
    });

    it('flush clears all cache entries', function () {
        FullAttributeEnum::ACTIVE->label();
        EmptyEnum::ACTIVE->label();

        EnumCache::flush();

        expect(EnumCache::getInstance()->has(FullAttributeEnum::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(EmptyEnum::class))->toBeFalse();
    });

    it('TTL expiration works for zero TTL', function () {
        EnumCache::getInstance()->setTtl(0);

        // With TTL=0, caching is disabled
        expect(EnumCache::getInstance()->has(FullAttributeEnum::class))->toBeFalse();

        // But resolution still works (just always misses cache)
        expect(FullAttributeEnum::ACTIVE->label())->toBeString();
    });

    it('singleton returns same instance', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resetInstance creates fresh singleton', function () {
        $a = EnumCache::getInstance();
        EnumCache::getInstance()->setTtl(999);
        EnumCache::resetInstance();

        $b = EnumCache::getInstance();

        // TTL should be reset to default (300)
        expect($b)->not->toBe($a);
        expect($b->getTtl())->toBe(300);
    });

    it('setTtl clamps negative values to zero', function () {
        EnumCache::getInstance()->setTtl(-10);

        expect(EnumCache::getInstance()->getTtl())->toBe(0);
    });
});

describe('Production Readiness — InvalidEnumException', function () {
    it('value() factory formats message correctly', function () {
        $e = InvalidEnumException::value('UserStatus', 'invalid');

        expect($e->getMessage())->toContain('invalid');
        expect($e->getMessage())->toContain('UserStatus');
    });

    it('value() factory handles null value', function () {
        $e = InvalidEnumException::value('UserStatus', null);

        expect($e->getMessage())->toContain('null');
    });

    it('forName() factory formats message correctly', function () {
        $e = InvalidEnumException::forName('UserStatus', 'BOGUS');

        expect($e->getMessage())->toContain('BOGUS');
        expect($e->getMessage())->toContain('UserStatus');
    });

    it('__toString returns class name and message', function () {
        $e = InvalidEnumException::forName('UserStatus', 'X');

        expect((string) $e)->toContain('InvalidEnumException');
        expect((string) $e)->toContain('X does not exist');
    });
});

describe('Production Readiness — Attribute Contracts', function () {
    it('Label attribute has readonly string value', function () {
        $attr = new Label('Test');

        expect($attr->value)->toBe('Test');
    });

    it('Color attribute has readonly string value', function () {
        $attr = new Color('danger');

        expect($attr->value)->toBe('danger');
    });

    it('Description attribute has readonly string value', function () {
        $attr = new Description('A description');

        expect($attr->value)->toBe('A description');
    });

    it('Icon attribute has readonly string value', function () {
        $attr = new Icon('heroicon-o-check');

        expect($attr->value)->toBe('heroicon-o-check');
    });

    it('EnumLabel supports both labels map and single label', function () {
        $classLevel = new EnumLabel(['a' => 'A']);
        expect($classLevel->labels)->toBe(['a' => 'A']);
        expect($classLevel->label)->toBeNull();

        $caseLevel = new EnumLabel(label: 'Override');
        expect($caseLevel->label)->toBe('Override');
        expect($caseLevel->labels)->toBeNull();
    });

    it('EnumColor has all color arrays', function () {
        $attr = new EnumColor(success: [1], danger: [0]);

        expect($attr->success)->toBe([1]);
        expect($attr->danger)->toBe([0]);
        expect($attr->warning)->toBe([]);
        expect($attr->info)->toBe([]);
        expect($attr->secondary)->toBe([]);
    });

    it('EnumIcon supports default and per-case icons', function () {
        $attr = new EnumIcon(default: 'heroicon-o-flag', icons: [1 => 'heroicon-o-check']);

        expect($attr->default)->toBe('heroicon-o-flag');
        expect($attr->icons)->toBe([1 => 'heroicon-o-check']);
    });

    it('EnumDescription supports descriptions map and single description', function () {
        $classLevel = new EnumDescription(descriptions: ['a' => 'Desc']);
        expect($classLevel->descriptions)->toBe(['a' => 'Desc']);

        $caseLevel = new EnumDescription(description: 'Single desc');
        expect($caseLevel->description)->toBe('Single desc');
    });
});

describe('Production Readiness — EnumRule Validation', function () {
    it('for() factory creates instance', function () {
        $rule = EnumRule::for(FullAttributeEnum::class);

        expect($rule)->toBeInstanceOf(EnumRule::class);
    });

    it('nullable() creates nullable variant', function () {
        $rule = EnumRule::for(FullAttributeEnum::class)->nullable();

        expect($rule)->toBeInstanceOf(EnumRule::class);
    });
});
