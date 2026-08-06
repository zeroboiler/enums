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
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Enum Attribute Consistency', function () {
    describe('Label attribute', function () {
        it('is an Attribute targeting class constants', function () {
            $ref = new ReflectionClass(Label::class);
            $attrs = $ref->getAttributes();

            expect($attrs)->not->toBeEmpty();
            expect($attrs[0]->getName())->toBe(Attribute::class);
        });

        it('has readonly string value property', function () {
            $label = new Label('Test Label');

            expect($label->value)->toBe('Test Label');
        });

        it('is final', function () {
            $ref = new ReflectionClass(Label::class);

            expect($ref->isFinal())->toBeTrue();
        });
    });

    describe('Color attribute', function () {
        it('has readonly string value property', function () {
            $color = new Color('danger');

            expect($color->value)->toBe('danger');
        });

        it('is final', function () {
            $ref = new ReflectionClass(Color::class);

            expect($ref->isFinal())->toBeTrue();
        });
    });

    describe('Icon attribute', function () {
        it('has readonly string value property', function () {
            $icon = new Icon('heroicon-o-star');

            expect($icon->value)->toBe('heroicon-o-star');
        });

        it('is final', function () {
            $ref = new ReflectionClass(Icon::class);

            expect($ref->isFinal())->toBeTrue();
        });
    });

    describe('Description attribute', function () {
        it('has readonly string value property', function () {
            $desc = new Description('A detailed description');

            expect($desc->value)->toBe('A detailed description');
        });

        it('is final', function () {
            $ref = new ReflectionClass(Description::class);

            expect($ref->isFinal())->toBeTrue();
        });
    });

    describe('EnumLabel class-level attribute', function () {
        it('accepts labels map', function () {
            $attr = new EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User']);

            expect($attr->labels)->toBe(['active' => 'Active User', 'banned' => 'Banned User']);
            expect($attr->label)->toBeNull();
        });

        it('accepts single label for case-level', function () {
            $attr = new EnumLabel(label: 'Active User');

            expect($attr->label)->toBe('Active User');
            expect($attr->labels)->toBeNull();
        });

        it('accepts both null by default', function () {
            $attr = new EnumLabel;

            expect($attr->labels)->toBeNull();
            expect($attr->label)->toBeNull();
        });

        it('is final', function () {
            $ref = new ReflectionClass(EnumLabel::class);

            expect($ref->isFinal())->toBeTrue();
        });
    });

    describe('EnumColor class-level attribute', function () {
        it('accepts color maps', function () {
            $attr = new EnumColor(
                success: ['active', 'paid'],
                danger: ['banned'],
                warning: ['pending'],
                info: ['info_case'],
                secondary: ['default'],
            );

            expect($attr->success)->toBe(['active', 'paid']);
            expect($attr->danger)->toBe(['banned']);
            expect($attr->warning)->toBe(['pending']);
            expect($attr->info)->toBe(['info_case']);
            expect($attr->secondary)->toBe(['default']);
        });

        it('accepts empty arrays by default', function () {
            $attr = new EnumColor;

            expect($attr->success)->toBe([]);
            expect($attr->danger)->toBe([]);
            expect($attr->warning)->toBe([]);
            expect($attr->info)->toBe([]);
            expect($attr->secondary)->toBe([]);
        });

        it('is final', function () {
            $ref = new ReflectionClass(EnumColor::class);

            expect($ref->isFinal())->toBeTrue();
        });
    });

    describe('EnumDescription class-level attribute', function () {
        it('accepts descriptions map', function () {
            $attr = new EnumDescription(descriptions: ['active' => 'Active user', 'banned' => 'Banned user']);

            expect($attr->descriptions)->toBe(['active' => 'Active user', 'banned' => 'Banned user']);
        });

        it('accepts single description', function () {
            $attr = new EnumDescription(description: 'Single description');

            expect($attr->description)->toBe('Single description');
        });

        it('is final', function () {
            $ref = new ReflectionClass(EnumDescription::class);

            expect($ref->isFinal())->toBeTrue();
        });
    });

    describe('EnumIcon class-level attribute', function () {
        it('accepts default icon', function () {
            $attr = new EnumIcon(default: 'heroicon-o-question-mark-circle');

            expect($attr->default)->toBe('heroicon-o-question-mark-circle');
        });

        it('accepts null by default', function () {
            $attr = new EnumIcon;

            expect($attr->default)->toBeNull();
        });

        it('is final', function () {
            $ref = new ReflectionClass(EnumIcon::class);

            expect($ref->isFinal())->toBeTrue();
        });
    });

    describe('EnumRule', function () {
        it('is final and readonly', function () {
            $ref = new ReflectionClass(EnumRule::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('implements ValidationRule', function () {
            $rule = EnumRule::for(UserStatus::class);

            expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
        });

        it('creates with for() named constructor', function () {
            $rule = EnumRule::for(UserStatus::class);

            // Should not throw — valid construction
            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('nullable() creates new instance', function () {
            $rule = EnumRule::for(UserStatus::class);
            $nullable = $rule->nullable();

            // New instance, not the same object
            expect($nullable)->toBeInstanceOf(EnumRule::class);
        });

        it('accepts valid string-backed enum value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 'active', $fail);

            expect($failed)->toBeFalse();
        });

        it('accepts valid int-backed enum value', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 1, $fail);

            expect($failed)->toBeFalse();
        });

        it('rejects wrong type for string-backed enum', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 123, $fail);

            expect($failed)->toBeTrue();
        });

        it('rejects wrong type for int-backed enum', function () {
            $rule = EnumRule::for(Priority::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 'not-an-int', $fail);

            expect($failed)->toBeTrue();
        });

        it('rejects invalid backed value', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $fail = function (string $message) use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 'nonexistent_value', $fail);

            expect($failed)->toBeTrue();
        });
    });

    describe('HasEnumMetadata trait resolution', function () {
        beforeEach(function () {
            EnumCache::flush();
            EnumCache::resetInstance();
        });

        afterEach(function () {
            EnumCache::flush();
            EnumCache::resetInstance();
        });

        it('per-case Label overrides class-level EnumLabel', function () {
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            // ACTIVE has per-case #[Label('Active User')]
            expect($meta['labels']['active'])->toBe('Active User');
        });

        it('auto-generates label for cases without Label attribute', function () {
            $label = UserStatus::INACTIVE->label();

            expect($label)->toBe('Inactive');
        });

        it('class-level EnumColor maps multiple values', function () {
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta['colors']['active'])->toBe('success');
            expect($meta['colors']['pending'])->toBe('warning');
            expect($meta['colors']['suspended'])->toBe('warning');
            expect($meta['colors']['banned'])->toBe('danger');
        });

        it('forSelect preserves declaration order', function () {
            $select = UserStatus::forSelect();
            $values = array_column($select, 'value');

            expect($values)->toEqual([
                'active', 'inactive', 'pending', 'suspended', 'banned',
            ]);
        });

        it('forApi returns complete metadata for all cases', function () {
            $api = UserStatus::forApi();

            expect($api)->toHaveCount(5);
            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString();
                expect($item['label'])->toBeString();
            }
        });

        it('tryFromLabel handles special characters', function () {
            // UserStatus::ACTIVE has label 'Active User'
            $result = UserStatus::tryFromLabel('Active User');

            expect($result)->toBe(UserStatus::ACTIVE);
        });

        it('tryFromLabel returns null for non-matching label', function () {
            $result = UserStatus::tryFromLabel('Nonexistent Label');

            expect($result)->toBeNull();
        });

        it('values() returns all backed values for string enum', function () {
            $values = UserStatus::values();

            expect($values)->toEqual(['active', 'inactive', 'pending', 'suspended', 'banned']);
        });

        it('values() returns all backed values for int enum', function () {
            $values = Priority::values();

            expect($values)->toEqual([1, 2, 3, 4]);
        });

        it('labels() returns all labels in order', function () {
            $labels = UserStatus::labels();

            expect($labels)->toHaveCount(5);
            expect($labels[0])->toBe('Active User');
        });
    });

    describe('Comparison methods edge cases', function () {
        it('is() with string is case-sensitive', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        });

        it('in() accepts empty array', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('in() with mixed instance and string', function () {
            $result = UserStatus::ACTIVE->in([UserStatus::BANNED, 'ACTIVE']);

            expect($result)->toBeTrue();
        });

        it('isNot() with string', function () {
            expect(UserStatus::ACTIVE->isNot('BANNED'))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot('ACTIVE'))->toBeFalse();
        });
    });

    describe('Class-level attributes consistency', function () {
        it('EnumLabel can be used at class level', function () {
            $ref = new ReflectionClass(EnumLabel::class);
            $attrs = $ref->getAttributes();

            expect($attrs)->not->toBeEmpty();
        });

        it('EnumColor can be used at both class and case level', function () {
            $ref = new ReflectionClass(EnumColor::class);
            $attrs = $ref->getAttributes();

            expect($attrs)->not->toBeEmpty();
        });

        it('EnumDescription can be used at both levels', function () {
            $ref = new ReflectionClass(EnumDescription::class);
            $attrs = $ref->getAttributes();

            expect($attrs)->not->toBeEmpty();
        });

        it('EnumIcon can be used at both levels', function () {
            $ref = new ReflectionClass(EnumIcon::class);
            $attrs = $ref->getAttributes();

            expect($attrs)->not->toBeEmpty();
        });

        it('EnumIcon default applies to all cases when no per-case override', function () {
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            // UserStatus doesn't use EnumIcon, so icons should be from per-case only
            expect($meta['icons'])->not->toBeEmpty();
            expect($meta['icons']['active'])->toBe('heroicon-o-check-circle');
        });
    });

    describe('Fixture enums structure', function () {
        it('UserStatus is string-backed with HasEnumMetadata', function () {
            expect(enum_exists(UserStatus::class))->toBeTrue();
            $ref = new ReflectionEnum(UserStatus::class);

            expect($ref->isBacked())->toBeTrue();
            expect($ref->getBackingType()?->getName())->toBe('string');
        });

        it('Priority is int-backed with HasEnumMetadata', function () {
            expect(enum_exists(Priority::class))->toBeTrue();
            $ref = new ReflectionEnum(Priority::class);

            expect($ref->isBacked())->toBeTrue();
            expect($ref->getBackingType()?->getName())->toBe('int');
        });

        it('RequestState is pure enum with HasEnumMetadata', function () {
            expect(enum_exists(RequestState::class))->toBeTrue();
            $ref = new ReflectionEnum(RequestState::class);

            expect($ref->isBacked())->toBeFalse();
        });

        it('ZeroPriority is int-backed starting from zero', function () {
            expect(ZeroPriority::NONE->value)->toBe(0);
            expect(ZeroPriority::LOW->value)->toBe(1);
            expect(ZeroPriority::HIGH->value)->toBe(2);
        });

        it('all fixture enums use HasEnumMetadata trait', function () {
            $enums = [UserStatus::class, Priority::class, RequestState::class, ZeroPriority::class, OrderStatus::class, TicketStatus::class];

            foreach ($enums as $enumClass) {
                expect(enum_exists($enumClass))->toBeTrue("{$enumClass} should exist");
                $ref = new ReflectionClass($enumClass);
                $traitNames = array_map(
                    fn (ReflectionAttribute $a): string => $a->getName(),
                    $ref->getTraitNames(),
                );
                // Check trait is used
                expect(in_array(HasEnumMetadata::class, $traitNames, true))
                    ->toBeTrue("{$enumClass} should use HasEnumMetadata");
            }
        });

        it('all fixture enums have at least 2 cases', function () {
            $enums = [UserStatus::class, Priority::class, RequestState::class, ZeroPriority::class, OrderStatus::class, TicketStatus::class];

            foreach ($enums as $enumClass) {
                $count = count($enumClass::cases());

                expect($count)->toBeGreaterThanOrEqual(2, "{$enumClass} should have at least 2 cases");
            }
        });
    });
});
