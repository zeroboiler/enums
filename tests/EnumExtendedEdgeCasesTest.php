<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumCast serialization edge cases', function () {
    it('serializes raw string value as-is', function () {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'status',
            value: 'active',
            attributes: [],
        );

        expect($result)->toBe('active');
    });

    it('serializes raw int value as-is', function () {
        $cast = new EnumCast(Priority::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'priority',
            value: 2,
            attributes: [],
        );

        expect($result)->toBe(2);
    });

    it('serializes null as null', function () {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'status',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('throws InvalidArgumentException when setting wrong enum type', function () {
        $cast = new EnumCast(UserStatus::class);

        $cast->set(
            model: new class {},
            key: 'status',
            value: Priority::HIGH,
            attributes: [],
        );
    })->throws(\InvalidArgumentException::class, 'Expected enum');

    it('throws InvalidArgumentException when setting non-scalar non-enum value', function () {
        $cast = new EnumCast(UserStatus::class);

        $cast->set(
            model: new class {},
            key: 'status',
            value: ['array'],
            attributes: [],
        );
    })->throws(\InvalidArgumentException::class, 'Invalid value type');

    it('throws InvalidArgumentException when setting invalid raw value', function () {
        $cast = new EnumCast(UserStatus::class);

        $cast->set(
            model: new class {},
            key: 'status',
            value: 'nonexistent_status',
            attributes: [],
        );
    })->throws(\InvalidArgumentException::class, 'Invalid value');

    it('sets valid raw string value for string-backed enum', function () {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            model: new class {},
            key: 'status',
            value: 'banned',
            attributes: [],
        );

        expect($result)->toBe('banned');
    });

    it('sets valid raw int value for int-backed enum', function () {
        $cast = new EnumCast(Priority::class);

        $result = $cast->set(
            model: new class {},
            key: 'priority',
            value: 1,
            attributes: [],
        );

        expect($result)->toBe(1);
    });
});

describe('EnumManager edge cases', function () {
    it('throws BadMethodCallException when enum does not use trait', function () {
        $manager = new EnumManager;

        $manager->forSelect(\stdClass::class);
    })->throws(\BadMethodCallException::class, 'does not use HasEnumMetadata');

    it('throws BadMethodCallException for forApi with non-metadata class', function () {
        $manager = new EnumManager;

        $manager->forApi(\stdClass::class);
    })->throws(\BadMethodCallException::class, 'does not use HasEnumMetadata');

    it('throws BadMethodCallException for tryFromLabel with non-metadata class', function () {
        $manager = new EnumManager;

        $manager->tryFromLabel(\stdClass::class, 'label');
    })->throws(\BadMethodCallException::class, 'does not use HasEnumMetadata');
});

describe('generateLabel edge cases', function () {
    it('generates label for single character case name', function () {
        expect(SingleCharEnum::A->label())->toBe('A');
    });

    it('generates label for camelCase case name', function () {
        expect(CamelCaseEnum::IS_ACTIVE->label())->toBe('Is Active');
    });

    it('generates label for case with numbers', function () {
        expect(EdgeNameEnum::STATUS_2B->label())->toBe('Status 2b');
    });

    it('generates label for short SCREAMING_SNAKE', function () {
        expect(EdgeNameEnum::OK->label())->toBe('Ok');
    });
});

describe('forApi with pure enum', function () {
    it('returns case name as value for pure enums', function () {
        $api = RequestState::forApi();

        expect($api)->toHaveCount(4);
        expect($api[0]['value'])->toBe('DRAFT');
        expect($api[0]['name'])->toBe('DRAFT');
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('returns case names as values for pure enum values()', function () {
        $values = RequestState::values();

        expect($values)->toEqual(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
    });
});

describe('forApi with int-backed enum', function () {
    it('returns int values for int-backed enums', function () {
        $api = Priority::forApi();

        expect($api)->toHaveCount(4);
        expect($api[0]['value'])->toBe(1);
        expect($api[0]['name'])->toBe('LOW');
    });

    it('returns int values for int-backed enum values()', function () {
        $values = Priority::values();

        expect($values)->toEqual([1, 2, 3, 4]);
    });
});

describe('tryFromLabel case sensitivity', function () {
    it('matches label case-insensitively', function () {
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
    });

    it('returns null for non-existent label', function () {
        expect(UserStatus::tryFromLabel('nonexistent label'))->toBeNull();
    });

    it('returns null for empty label', function () {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });
});

describe('fromName / tryFromName / hasCase', function () {
    it('fromName returns correct case for valid name', function () {
        expect(UserStatus::fromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::fromName('BANNED'))->toBe(UserStatus::BANNED);
    });

    it('fromName throws for invalid name', function () {
        UserStatus::fromName('NONEXISTENT');
    })->throws(InvalidEnumException::class, 'NONEXISTENT');

    it('fromName throws for empty string', function () {
        UserStatus::fromName('');
    })->throws(InvalidEnumException::class);

    it('tryFromName returns null for invalid name', function () {
        expect(UserStatus::tryFromName('NONEXISTENT'))->toBeNull();
        expect(UserStatus::tryFromName(''))->toBeNull();
    });

    it('hasCase returns true for existing case', function () {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('INACTIVE'))->toBeTrue();
    });

    it('hasCase returns false for non-existent case', function () {
        expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
        expect(UserStatus::hasCase(''))->toBeFalse();
    });

    it('works with int-backed enums', function () {
        expect(Priority::fromName('LOW'))->toBe(Priority::LOW);
        expect(Priority::hasCase('HIGH'))->toBeTrue();
        expect(Priority::tryFromName('INVALID'))->toBeNull();
    });

    it('works with pure enums', function () {
        expect(RequestState::fromName('DRAFT'))->toBe(RequestState::DRAFT);
        expect(RequestState::hasCase('SUBMITTED'))->toBeTrue();
        expect(RequestState::tryFromName('INVALID'))->toBeNull();
    });
});

describe('forSelect consistency', function () {
    it('returns consistent value-label pairs for string-backed enum', function () {
        $select = UserStatus::forSelect();

        expect($select)->toHaveCount(5);
        expect($select[0])->toHaveKeys(['value', 'label']);
        expect($select[0]['value'])->toBe('active');
        expect($select[0]['label'])->toBe('Active User');
    });

    it('returns consistent value-label pairs for int-backed enum', function () {
        $select = ZeroPriority::forSelect();

        expect($select)->toHaveCount(3);
        expect($select[0]['value'])->toBe(0);
        expect($select[0]['label'])->toBe('None');
    });
});

describe('labels method', function () {
    it('returns all labels in order', function () {
        $labels = UserStatus::labels();

        expect($labels)->toHaveCount(5);
        expect($labels[0])->toBe('Active User');
        expect($labels[1])->toBe('Inactive');
        expect($labels[2])->toBe('Awaiting Verification');
    });
});

describe('EnumRule with pure enum', function () {
    it('accepts valid case name for pure enum', function () {
        $rule = EnumRule::for(RequestState::class);

        $failed = false;
        $fail = function (string $attr, string $msg = '') use (&$failed): void {
            $failed = true;
        };

        $rule->validate('state', 'DRAFT', $fail);

        expect($failed)->toBeFalse();
    });

    it('rejects invalid case name for pure enum', function () {
        $rule = EnumRule::for(RequestState::class);

        $failed = false;
        $fail = function (string $attr, string $msg = '') use (&$failed): void {
            $failed = true;
        };

        $rule->validate('state', 'INVALID_STATE', $fail);

        expect($failed)->toBeTrue();
    });

    it('rejects non-string value for pure enum', function () {
        $rule = EnumRule::for(RequestState::class);

        $failed = false;
        $fail = function (string $attr, string $msg = '') use (&$failed): void {
            $failed = true;
        };

        $rule->validate('state', 123, $fail);

        expect($failed)->toBeTrue();
    });

    it('rejects int value for string-backed enum', function () {
        $rule = EnumRule::for(UserStatus::class);

        $failed = false;
        $fail = function (string $attr, string $msg = '') use (&$failed): void {
            $failed = true;
        };

        $rule->validate('status', 42, $fail);

        expect($failed)->toBeTrue();
    });

    it('includes allowed values in error message', function () {
        $rule = EnumRule::for(UserStatus::class);

        $message = '';
        $fail = function (string $attr, string $msg = '') use (&$message): string {
            $message = $msg;

            return $msg;
        };

        $rule->validate('status', 'invalid', $fail);

        expect($message)->toContain('Allowed values');
        expect($message)->toContain('active');
    });
});

describe('EnumCache setTtl', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('applies new TTL after setTtl call', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        $cache->set(MetadataTTLTestEnum::class, [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(MetadataTTLTestEnum::class))->toBeTrue();

        $cache->setTtl(0);

        // TTL now 0, should always be stale
        expect($cache->has(MetadataTTLTestEnum::class))->toBeFalse();
    });
});

describe('EnumMetadataResolver with empty enum', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('resolves metadata for enum with no cases (edge case)', function () {
        $meta = EnumMetadataResolver::resolve(SingleCaseEnum::class);

        expect($meta)->toHaveKey('labels');
        expect($meta)->toHaveKey('descriptions');
        expect($meta)->toHaveKey('colors');
        expect($meta)->toHaveKey('icons');
        expect($meta['labels'])->toHaveKey('only');
    });
});

// ─── Test Fixtures ───────────────────────────────────────────────

enum SingleCharEnum: string
{
    use HasEnumMetadata;

    case A = 'a';
}

enum CamelCaseEnum: string
{
    use HasEnumMetadata;

    case IS_ACTIVE = 'active';
}

enum EdgeNameEnum: string
{
    use HasEnumMetadata;

    case OK = 'ok';
    case STATUS_2B = 'status_2b';
}

enum MetadataTTLTestEnum: string
{
    use HasEnumMetadata;

    case ONLY = 'only';
}

enum SingleCaseEnum: string
{
    use HasEnumMetadata;

    case ONLY = 'only';
}
