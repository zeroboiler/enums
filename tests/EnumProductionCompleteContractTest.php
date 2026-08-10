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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Attribute final and readonly contract', function (): void {
    $attributes = [
        Label::class,
        Color::class,
        Icon::class,
        Description::class,
        EnumLabel::class,
        EnumColor::class,
        EnumIcon::class,
        EnumDescription::class,
    ];

    foreach ($attributes as $attrClass) {
        it("{$attrClass} is final", function () use ($attrClass): void {
            $ref = new ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue();
        });

        it("{$attrClass} constructor properties are readonly", function () use ($attrClass): void {
            $ref = new ReflectionClass($attrClass);
            $constructor = $ref->getConstructor();
            expect($constructor)->not->toBeNull();

            foreach ($constructor->getParameters() as $param) {
                if ($ref->hasProperty($param->getName())) {
                    $prop = $ref->getProperty($param->getName());
                    expect($prop->isReadOnly())->toBeTrue("Property {$param->getName()} on {$attrClass} must be readonly");
                }
            }
        });
    }
});

describe('Attribute target constraints', function (): void {
    it('per-case attributes target CLASS_CONSTANT only', function (): void {
        $perCase = [Label::class, Color::class, Icon::class, Description::class];
        foreach ($perCase as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            $attrs = $ref->getAttributes(\Attribute::class);
            $flags = $attrs[0]->getArguments()[0] ?? 0;
            expect($flags)->toBe(\Attribute::TARGET_CLASS_CONSTANT);
        }
    });

    it('class-level attributes target CLASS | CLASS_CONSTANT', function (): void {
        $classLevel = [EnumLabel::class, EnumColor::class, EnumIcon::class, EnumDescription::class];
        foreach ($classLevel as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            $attrs = $ref->getAttributes(\Attribute::class);
            $flags = $attrs[0]->getArguments()[0] ?? 0;
            expect($flags)->toBe(\Attribute::TARGET_CLASS | \Attribute::TARGET_CLASS_CONSTANT);
        }
    });
});

describe('EnumRule with all fixture enums', function (): void {
    it('accepts valid string-backed enum value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $fail = fn (): mixed => throw new \InvalidArgumentException('Should not fail');
        expect($rule->validate('status', 'active', $fail))->toBeNull();
    });

    it('rejects invalid string-backed enum value', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };
        $rule->validate('status', 'nonexistent', $fail);
        expect($failed)->toBeTrue();
    });

    it('rejects null when not nullable', function (): void {
        $rule = EnumRule::for(UserStatus::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };
        $rule->validate('status', null, $fail);
        expect($failed)->toBeTrue();
    });

    it('accepts null when nullable', function (): void {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };
        $rule->validate('status', null, $fail);
        expect($failed)->toBeFalse();
    });

    it('validates int-backed enums with int values', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };
        $rule->validate('priority', 1, $fail);
        expect($failed)->toBeFalse();
    });

    it('rejects string value for int-backed enum', function (): void {
        $rule = EnumRule::for(Priority::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };
        $rule->validate('priority', '1', $fail);
        expect($failed)->toBeTrue();
    });

    it('validates pure enums by case name', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };
        $rule->validate('flag', 'DARK_MODE', $fail);
        expect($failed)->toBeFalse();
    });

    it('rejects non-string value for pure enum', function (): void {
        $rule = EnumRule::for(PureFeatureFlag::class);
        $failed = false;
        $fail = function (string $message) use (&$failed): void {
            $failed = true;
        };
        $rule->validate('flag', 123, $fail);
        expect($failed)->toBeTrue();
    });
});

describe('InvalidEnumException factory methods', function (): void {
    it('creates value exception with correct message', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 'invalid');
        expect($e->getMessage())->toContain('invalid');
        expect($e->getMessage())->toContain(UserStatus::class);
    });

    it('creates value exception with null value', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, null);
        expect($e->getMessage())->toContain('null');
    });

    it('creates forName exception with correct message', function (): void {
        $e = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');
        expect($e->getMessage())->toContain('UNKNOWN');
        expect($e->getMessage())->toContain(UserStatus::class);
    });
});

describe('ZeroPriority edge case (zero as backed value)', function (): void {
    it('resolves label for zero value', function (): void {
        expect(ZeroPriority::ZERO->label())->toBe('Zero');
    });

    it('forSelect includes zero value', function (): void {
        $options = ZeroPriority::forSelect();
        $values = array_column($options, 'value');
        expect(in_array(0, $values, true))->toBeTrue();
    });

    it('values() returns zero', function (): void {
        $values = ZeroPriority::values();
        expect($values)->toContain(0);
    });

    it('hasCase works for ZERO', function (): void {
        expect(ZeroPriority::hasCase('ZERO'))->toBeTrue();
    });
});

describe('SingleCaseEnum edge case', function (): void {
    it('has exactly one case', function (): void {
        expect(SingleCaseEnum::cases())->toHaveCount(1);
    });

    it('forSelect returns one item', function (): void {
        expect(SingleCaseEnum::forSelect())->toHaveCount(1);
    });

    it('forApi returns one item with all keys', function (): void {
        $api = SingleCaseEnum::forApi();
        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('is() returns true for same case', function (): void {
        expect(SingleCaseEnum::ONLY->is(SingleCaseEnum::ONLY))->toBeTrue();
    });

    it('isNot() returns false for same case', function (): void {
        expect(SingleCaseEnum::ONLY->isNot(SingleCaseEnum::ONLY))->toBeFalse();
    });
});

describe('CamelCaseRole label generation', function (): void {
    it('generates Title Case from camelCase', function (): void {
        expect(CamelCaseRole::Admin->label())->toBe('Admin');
        expect(CamelCaseRole::SuperAdmin->label())->toBe('Super Admin');
    });

    it('forApi uses case name for pure-enum-like name lookup', function (): void {
        $api = CamelCaseRole::forApi();
        expect($api[0]['name'])->toBe('Admin');
    });
});

describe('IntStatusWithColor resolution', function (): void {
    it('resolves class-level color by int value', function (): void {
        expect(IntStatusWithColor::Active->color())->toBe('success');
    });

    it('resolves per-case color override by int value', function (): void {
        expect(IntStatusWithColor::Banned->color())->toBe('danger');
    });

    it('forSelect uses int values', function (): void {
        $options = IntStatusWithColor::forSelect();
        $values = array_column($options, 'value');
        foreach ($values as $v) {
            expect(is_int($v))->toBeTrue();
        }
    });
});

describe('MixedAttributeStatus resolution', function (): void {
    it('resolves mixed attribute combinations', function (): void {
        $label = MixedAttributeStatus::Draft->label();
        expect($label)->toBeString()->not->toBeEmpty();
    });

    it('has consistent forApi structure', function (): void {
        $api = MixedAttributeStatus::forApi();
        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBeString();
            expect($item['label'])->toBeString()->not->toBeEmpty();
        }
    });
});

describe('AllClassLevelEnum full coverage', function (): void {
    it('uses all class-level attributes', function (): void {
        expect(AllClassLevelEnum::cases())->not->toBeEmpty();
    });

    it('resolves label via class-level EnumLabel', function (): void {
        $label = AllClassLevelEnum::cases()[0]->label();
        expect($label)->toBeString()->not->toBeEmpty();
    });

    it('resolves color via class-level EnumColor', function (): void {
        $color = AllClassLevelEnum::cases()[0]->color();
        expect($color)->toBeString();
    });
});

describe('Comparison methods comprehensive', function (): void {
    it('is() with instance comparison is identity-based', function (): void {
        $active1 = UserStatus::ACTIVE;
        $active2 = UserStatus::ACTIVE;
        // Same case, different variable — should be === true in PHP enums
        expect($active1->is($active2))->toBeTrue();
    });

    it('is() with string is case-sensitive', function (): void {
        expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
    });

    it('in() with empty array returns false', function (): void {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('in() with single matching element', function (): void {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
    });

    it('in() with only non-matching elements', function (): void {
        expect(UserStatus::ACTIVE->in([UserStatus::BANNED, UserStatus::INACTIVE]))->toBeFalse();
    });

    it('isNot() is exact negation of is()', function (): void {
        $case = UserStatus::ACTIVE;
        $targets = [UserStatus::ACTIVE, UserStatus::INACTIVE, UserStatus::BANNED];
        foreach ($targets as $target) {
            expect($case->isNot($target))->toBe(! $case->is($target));
        }
    });
});

describe('Lookup methods comprehensive', function (): void {
    it('tryFromName is case-sensitive (lowercase fails)', function (): void {
        expect(UserStatus::tryFromName('active'))->toBeNull();
    });

    it('tryFromName is case-sensitive (uppercase works)', function (): void {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
    });

    it('fromName throws for non-existent case', function (): void {
        expect(fn () => UserStatus::fromName('NONEXISTENT'))->toThrow(InvalidEnumException::class);
    });

    it('hasCase returns correct boolean', function (): void {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('active'))->toBeFalse();
        expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
    });

    it('tryFromLabel finds by exact label', function (): void {
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel finds by case-insensitive label', function (): void {
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel returns null for non-existent', function (): void {
        expect(UserStatus::tryFromLabel('Totally Nonexistent Label'))->toBeNull();
    });

    it('tryFromLabel returns null for empty string', function (): void {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });
});

describe('values() and labels() consistency', function (): void {
    it('values() and labels() have same count as cases', function (): void {
        foreach ([UserStatus::class, Priority::class, PureFeatureFlag::class, OrderStatus::class] as $enumClass) {
            $count = count($enumClass::cases());
            expect($enumClass::values())->toHaveCount($count);
            expect($enumClass::labels())->toHaveCount($count);
        }
    });

    it('values() contains unique elements', function (): void {
        $values = UserStatus::values();
        expect($values)->toBe(array_values(array_unique($values)));
    });

    it('labels() are all non-empty strings', function (): void {
        $labels = UserStatus::labels();
        foreach ($labels as $label) {
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });
});

describe('forApi() structure contract', function (): void {
    it('all items have required keys with correct types', function (): void {
        $enums = [UserStatus::class, Priority::class, PureFeatureFlag::class];

        foreach ($enums as $enumClass) {
            $api = $enumClass::forApi();
            foreach ($api as $item) {
                expect($item)->toHaveKey('value');
                expect($item)->toHaveKey('name');
                expect($item)->toHaveKey('label');
                expect($item)->toHaveKey('description');
                expect($item)->toHaveKey('color');
                expect($item)->toHaveKey('icon');
                expect($item['name'])->toBeString()->not->toBeEmpty();
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        }
    });
});

describe('EnumCache singleton behavior', function (): void {
    it('getInstance returns same instance', function (): void {
        $a = \ZeroBoiler\Enums\EnumCache::getInstance();
        $b = \ZeroBoiler\Enums\EnumCache::getInstance();
        expect($a)->toBe($b);
    });

    it('resetInstance creates new instance', function (): void {
        $original = \ZeroBoiler\Enums\EnumCache::getInstance();
        \ZeroBoiler\Enums\EnumCache::resetInstance();
        $newInstance = \ZeroBoiler\Enums\EnumCache::getInstance();
        expect($newInstance)->not->toBe($original);
    });

    it('TTL 0 means no caching', function (): void {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        $cache->clear();
        $cache->setTtl(0);
        expect($cache->has('AnyClass'))->toBeFalse();
    });
});
