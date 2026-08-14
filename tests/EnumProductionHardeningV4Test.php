<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ReflectionClass;
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
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseToggle;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('EnumAttribute Target Compliance — Verify attribute flags', function (): void {
    it('per-case attributes only allow TARGET_CLASS_CONSTANT', function (): void {
        $perCaseAttrs = [Label::class, Color::class, Icon::class, Description::class];

        foreach ($perCaseAttrs as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            $attrs = $ref->getAttributes(\Attribute::class)[0];
            $flags = $attrs->getArguments()[0];

            expect($flags)->toBe(\Attribute::TARGET_CLASS_CONSTANT);
        }
    });

    it('class-level attributes allow TARGET_CLASS | TARGET_CLASS_CONSTANT', function (): void {
        $classLevelAttrs = [EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class];

        foreach ($classLevelAttrs as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            $attrs = $ref->getAttributes(\Attribute::class)[0];
            $flags = $attrs->getArguments()[0];

            expect($flags)->toBe(\Attribute::TARGET_CLASS | \Attribute::TARGET_CLASS_CONSTANT);
        }
    });
});

describe('EnumCast edge cases — serialization contract', function (): void {
    it('get() returns null for non-matching string value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __construct(public readonly string $status = '') {}
        };

        expect($cast->get($model, 'status', 'nonexistent_value', []))->toBeNull();
    });

    it('get() returns null for non-matching int value on int-backed enum', function (): void {
        $cast = new EnumCast(Priority::class);
        $model = new class {
            public function __construct(public readonly string $priority = '') {}
        };

        expect($cast->get($model, 'priority', 99, []))->toBeNull();
    });

    it('set() throws InvalidArgumentException for wrong enum type', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __construct(public readonly string $status = '') {}
        };

        expect(fn () => $cast->set($model, 'status', Priority::LOW, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('set() throws InvalidArgumentException for invalid raw value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __construct(public readonly string $status = '') {}
        };

        expect(fn () => $cast->set($model, 'status', 'nonexistent', []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('set() accepts valid raw string value and returns it', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __construct(public readonly string $status = '') {}
        };

        expect($cast->set($model, 'status', 'active', []))->toBe('active');
    });

    it('set() accepts valid raw int value for int-backed enum', function (): void {
        $cast = new EnumCast(Priority::class);
        $model = new class {
            public function __construct(public readonly string $priority = '') {}
        };

        expect($cast->set($model, 'priority', 3, []))->toBe(3);
    });

    it('set() returns null for null value', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __construct(public readonly string $status = '') {}
        };

        expect($cast->set($model, 'status', null, []))->toBeNull();
    });

    it('serialize() returns backed value for enum instance', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __construct(public readonly string $status = '') {}
        };

        expect($cast->serialize($model, 'status', UserStatus::ACTIVE, []))->toBe('active');
    });

    it('serialize() returns raw int/string for non-enum values', function (): void {
        $cast = new EnumCast(Priority::class);
        $model = new class {
            public function __construct(public readonly string $priority = '') {}
        };

        expect($cast->serialize($model, 'priority', 3, []))->toBe(3);
    });

    it('serialize() returns null for non-scalar non-enum values', function (): void {
        $cast = new EnumCast(UserStatus::class);
        $model = new class {
            public function __construct(public readonly string $status = '') {}
        };

        $result = $cast->serialize($model, 'status', null, []);
        expect($result)->toBeNull();
    });
});

describe('EnumRule with pure enums', function (): void {
    it('validates case names for pure enums', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        // Valid case name
        $rule->validate('feature', 'DARK_MODE', $fail);
        expect($failCalled)->toBeFalse();

        // Invalid case name
        $rule->validate('feature', 'NONEXISTENT_FEATURE', $fail);
        expect($failCalled)->toBeTrue();
    });

    it('rejects non-string values for pure enums', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('feature', 123, $fail);
        expect($failCalled)->toBeTrue();
    });

    it('allows null when nullable is true', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('status', null, $fail);
        expect($failCalled)->toBeFalse();
    });

    it('rejects null when nullable is false', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('status', null, $fail);
        expect($failCalled)->toBeTrue();
    });

    it('rejects int value for string-backed enum', function (): void {
        $rule = EnumRule::for(UserStatus::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('status', 123, $fail);
        expect($failCalled)->toBeTrue();
    });

    it('rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);

        $failCalled = false;
        $fail = function (string $message) use (&$failCalled): void {
            $failCalled = true;
        };

        $rule->validate('priority', 'not_an_int', $fail);
        expect($failCalled)->toBeTrue();
    });
});

describe('EnumManager delegation edge cases', function (): void {
    it('forSelect delegates correctly for int-backed enum', function (): void {
        $manager = new EnumManager;
        $result = $manager->forSelect(Priority::class);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result[0])->toHaveKeys(['value', 'label']);
        expect($result[0]['value'])->toBeInt();
    });

    it('forApi includes all metadata keys', function (): void {
        $manager = new EnumManager;
        $result = $manager->forApi(DetailedTicketStatus::class);

        expect($result)->not->toBeEmpty();
        foreach ($result as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBeString();
        }
    });

    it('tryFromName delegates correctly', function (): void {
        $manager = new EnumManager;

        expect($manager->tryFromName(UserStatus::class, 'ACTIVE'))->toBeInstanceOf(UserStatus::class);
        expect($manager->tryFromName(UserStatus::class, 'NONEXISTENT'))->toBeNull();
    });

    it('fromName throws InvalidEnumException for bad name', function (): void {
        $manager = new EnumManager;

        expect(fn () => $manager->fromName(UserStatus::class, 'NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase delegates correctly', function (): void {
        $manager = new EnumManager;

        expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
        expect($manager->hasCase(UserStatus::class, 'GHOST_CASE'))->toBeFalse();
    });

    it('values returns correct types for each enum backing', function (): void {
        $manager = new EnumManager;

        $stringValues = $manager->values(UserStatus::class);
        foreach ($stringValues as $v) {
            expect($v)->toBeString();
        }

        $intValues = $manager->values(Priority::class);
        foreach ($intValues as $v) {
            expect($v)->toBeInt();
        }
    });

    it('labels returns list of non-empty strings', function (): void {
        $manager = new EnumManager;
        $labels = $manager->labels(UserStatus::class);

        expect($labels)->not->toBeEmpty();
        foreach ($labels as $label) {
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        }
    });

    it('throws BadMethodCallException for enum without trait', function (): void {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('EnumMetadataResolver build metadata structure', function (): void {
    it('resolves all four metadata keys', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('caches result across multiple resolves', function (): void {
        $cache = EnumCache::getInstance();
        $cache->clearClass(UserStatus::class);

        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta1)->toBe($meta2);
        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('throws LogicException for non-enum class', function (): void {
        expect(fn () => EnumMetadataResolver::resolve(\stdClass::class))
            ->toThrow(\LogicException::class);
    });
});

describe('CamelCase enum label generation', function (): void {
    it('generates Title Case from camelCase names', function (): void {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
    });
});

describe('Single case enum', function (): void {
    it('works correctly with a single case', function (): void {
        expect(SingleCaseToggle::ON->label())->toBeString()->not->toBeEmpty();
        expect(SingleCaseToggle::cases())->toHaveCount(1);
        expect(SingleCaseToggle::forSelect())->toHaveCount(1);
        expect(SingleCaseToggle::forApi())->toHaveCount(1);
    });
});

describe('Zero value int-backed enum', function (): void {
    it('treats zero as a valid backed value', function (): void {
        expect(ZeroPriority::NONE->value)->toBe(0);
        expect(ZeroPriority::NONE->label())->toBeString()->not->toBeEmpty();
        expect(ZeroPriority::values())->toContain(0);
    });
});

describe('PaymentStatus — class-level attribute resolution', function (): void {
    it('resolves class-level descriptions', function (): void {
        expect(PaymentStatus::APPROVED->description())->toBe('Payment has been approved');
        expect(PaymentStatus::REJECTED->description())->toBe('Payment was rejected');
    });

    it('resolves class-level labels', function (): void {
        expect(PaymentStatus::APPROVED->label())->toBe('Approved Payment');
        expect(PaymentStatus::REVIEW->label())->toBe('Under Review');
    });

    it('resolves class-level colors correctly', function (): void {
        expect(PaymentStatus::APPROVED->color())->toBe('success');
        expect(PaymentStatus::REJECTED->color())->toBe('danger');
    });

    it('uses default icon from class-level EnumIcon', function (): void {
        expect(PaymentStatus::APPROVED->icon())->toBe('heroicon-o-banknotes');
        expect(PaymentStatus::REJECTED->icon())->toBe('heroicon-o-banknotes');
    });
});

describe('DetailedTicketStatus — per-case override of class-level description', function (): void {
    it('uses class-level description for OPEN', function (): void {
        expect(DetailedTicketStatus::OPEN->description())->toBe('Ticket is open and awaiting triage');
    });

    it('uses per-case Description override for IN_PROGRESS', function (): void {
        expect(DetailedTicketStatus::IN_PROGRESS->description())->toBe('Ticket is currently being worked on');
    });

    it('uses class-level description for CLOSED', function (): void {
        expect(DetailedTicketStatus::CLOSED->description())->toBe('Ticket has been resolved');
    });
});
