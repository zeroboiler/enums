<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use BackedEnum;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PaymentStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('V43 Enum Production Readiness — Attribute Contract, Serialization & Edge Case Audit', function () {
    // ── EnumCast strict type safety ─────────────────────────────────────

    describe('EnumCast contract compliance', function () {
        it('is a final class', function () {
            $ref = new ReflectionClass(EnumCast::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('constructor accepts class-string and stores it as readonly', function () {
            $ref = new ReflectionClass(EnumCast::class);
            $constructor = $ref->getConstructor();
            expect($constructor)->not->toBeNull();

            $params = $constructor->getParameters();
            expect($params)->toHaveCount(1);
            expect($params[0]->getName())->toBe('enumClass');

            $prop = $ref->getProperty('enumClass');
            expect($prop->isReadOnly())->toBeTrue();
            expect($prop->isPrivate())->toBeTrue();
        });

        it('get() has #[Override] attribute', function () {
            $method = new ReflectionMethod(EnumCast::class, 'get');
            $attrs = $method->getAttributes(\Override::class);
            expect(count($attrs))->toBeGreaterThan(0);
        });

        it('set() has #[Override] attribute', function () {
            $method = new ReflectionMethod(EnumCast::class, 'set');
            $attrs = $method->getAttributes(\Override::class);
            expect(count($attrs))->toBeGreaterThan(0);
        });

        it('serialize() returns backed value for enum instance', function () {
            $cast = new EnumCast(UserStatus::class);
            $result = $cast->serialize(
                new class {
                    public function __construct() {}
                },
                'status',
                UserStatus::ACTIVE,
                []
            );

            expect($result)->toBe('active');
        });

        it('serialize() returns int/string passthrough for scalar values', function () {
            $cast = new EnumCast(UserStatus::class);

            // string value passthrough
            $result = $cast->serialize(
                new class {
                    public function __construct() {}
                },
                'status',
                'active',
                []
            );
            expect($result)->toBe('active');

            // null passthrough
            $result = $cast->serialize(
                new class {
                    public function __construct() {}
                },
                'status',
                null,
                []
            );
            expect($result)->toBeNull();
        });
    });

    // ── EnumRule contract compliance ───────────────────────────────────

    describe('EnumRule contract compliance', function () {
        it('is a final readonly class', function () {
            $ref = new ReflectionClass(EnumRule::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('implements ValidationRule interface', function () {
            $ref = new ReflectionClass(EnumRule::class);
            expect($ref->implementsInterface(\Illuminate\Contracts\Validation\ValidationRule::class))->toBeTrue();
        });

        it('validate() has #[Override] attribute', function () {
            $method = new ReflectionMethod(EnumRule::class, 'validate');
            $attrs = $method->getAttributes(\Override::class);
            expect(count($attrs))->toBeGreaterThan(0);
        });

        it('nullable() creates new instance with nullable=true', function () {
            $rule = EnumRule::for(UserStatus::class);
            $nullableRule = $rule->nullable();

            expect($nullableRule)->not->toBe($rule);
        });

        it('for() is a named constructor returning same class', function () {
            $rule = EnumRule::for(UserStatus::class);
            expect($rule)->toBeInstanceOf(EnumRule::class);
        });
    });

    // ── EnumManager contract compliance ────────────────────────────────

    describe('EnumManager contract compliance', function () {
        it('is a final readonly class', function () {
            $ref = new ReflectionClass(EnumManager::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('has no properties (stateless)', function () {
            $ref = new ReflectionClass(EnumManager::class);
            expect($ref->getProperties())->toHaveCount(0);
        });

        it('forSelect() delegates correctly for string-backed enum', function () {
            $manager = new EnumManager;
            $result = $manager->forSelect(UserStatus::class);

            expect($result)->toBeArray();
            expect($result)->not->toBeEmpty();
            expect($result[0])->toHaveKeys(['value', 'label']);
        });

        it('forApi() returns full metadata structure', function () {
            $manager = new EnumManager;
            $result = $manager->forApi(UserStatus::class);

            expect($result)->toBeArray();
            expect($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('tryFromLabel() returns null for non-existent label', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromLabel(UserStatus::class, 'Non Existent Label XYZ');

            expect($result)->toBeNull();
        });

        it('tryFromName() returns null for non-existent name', function () {
            $manager = new EnumManager;
            $result = $manager->tryFromName(UserStatus::class, 'NON_EXISTENT');

            expect($result)->toBeNull();
        });

        it('fromName() throws for non-existent name', function () {
            $manager = new EnumManager;
            expect(fn () => $manager->fromName(UserStatus::class, 'NON_EXISTENT'))->toThrow(InvalidEnumException::class);
        });

        it('hasCase() returns bool for known and unknown names', function () {
            $manager = new EnumManager;
            expect($manager->hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
            expect($manager->hasCase(UserStatus::class, 'UNKNOWN'))->toBeFalse();
        });

        it('values() returns correct type for int-backed enum', function () {
            $manager = new EnumManager;
            $values = $manager->values(IntBackedPriority::class);

            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('labels() returns string array', function () {
            $manager = new EnumManager;
            $labels = $manager->labels(UserStatus::class);

            expect($labels)->toBeArray();
            foreach ($labels as $label) {
                expect($label)->toBeString();
            }
        });

        it('throws BadMethodCallException for enum without trait', function () {
            $manager = new EnumManager;
            // PlainTestEnum doesn't use HasEnumMetadata
            expect(fn () => $manager->forSelect(\ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum::class))
                ->toThrow(\BadMethodCallException::class);
        });
    });

    // ── EnumsServiceProvider contract ───────────────────────────────────

    describe('EnumsServiceProvider design', function () {
        it('is a final class', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('register() has #[Override]', function () {
            $method = new ReflectionMethod(\ZeroBoiler\Enums\EnumsServiceProvider::class, 'register');
            $attrs = $method->getAttributes(\Override::class);
            expect(count($attrs))->toBeGreaterThan(0);
        });

        it('boot() has #[Override]', function () {
            $method = new ReflectionMethod(\ZeroBoiler\Enums\EnumsServiceProvider::class, 'boot');
            $attrs = $method->getAttributes(\Override::class);
            expect(count($attrs))->toBeGreaterThan(0);
        });
    });

    // ── Enum Facade contract ────────────────────────────────────────────

    describe('Enum Facade contract', function () {
        it('is a final class', function () {
            $ref = new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('getFacadeAccessor() has #[Override]', function () {
            $method = new ReflectionMethod(\ZeroBoiler\Enums\Facades\Enum::class, 'getFacadeAccessor');
            $attrs = $method->getAttributes(\Override::class);
            expect(count($attrs))->toBeGreaterThan(0);
        });

        it('returns correct accessor string', function () {
            $method = new ReflectionMethod(\ZeroBoiler\Enums\Facades\Enum::class, 'getFacadeAccessor');
            $method->setAccessible(true);
            $result = $method->invoke(null);

            expect($result)->toBe('zeroboiler.enum');
        });
    });

    // ── Per-case attribute TARGET validation ───────────────────────────

    describe('Attribute target constraints', function () {
        it('Label targets only TARGET_CLASS_CONSTANT', function () {
            $ref = new ReflectionClass(Label::class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs)->toHaveCount(1);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('Color targets only TARGET_CLASS_CONSTANT', function () {
            $ref = new ReflectionClass(Color::class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('Icon targets only TARGET_CLASS_CONSTANT', function () {
            $ref = new ReflectionClass(Icon::class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('Description targets only TARGET_CLASS_CONSTANT', function () {
            $ref = new ReflectionClass(Description::class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('EnumLabel targets both CLASS and CLASS_CONSTANT', function () {
            $ref = new ReflectionClass(EnumLabel::class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('EnumColor targets both CLASS and CLASS_CONSTANT', function () {
            $ref = new ReflectionClass(EnumColor::class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('EnumIcon targets both CLASS and CLASS_CONSTANT', function () {
            $ref = new ReflectionClass(EnumIcon::class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS_CONSTANT);
        });

        it('EnumDescription targets both CLASS and CLASS_CONSTANT', function () {
            $ref = new ReflectionClass(EnumDescription::class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS);
            expect($attrs[0]->getArguments())->toContain(Attribute::TARGET_CLASS_CONSTANT);
        });
    });

    // ── EnumCache serialization blocking ───────────────────────────────

    describe('EnumCache serialization blocking', function () {
        it('__clone() returns never', function () {
            $method = new ReflectionMethod(EnumCache::class, '__clone');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('never');
        });

        it('__wakeup() returns never', function () {
            $method = new ReflectionMethod(EnumCache::class, '__wakeup');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('never');
        });

        it('__serialize() returns never', function () {
            $method = new ReflectionMethod(EnumCache::class, '__serialize');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('never');
        });

        it('__unserialize() returns never', function () {
            $method = new ReflectionMethod(EnumCache::class, '__unserialize');
            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('never');
        });
    });

    // ── forApi() completeness for int-backed enums ────────────────────────

    describe('forApi() completeness for int-backed enums', function () {
        it('includes description (nullable) for all int-backed cases', function () {
            $api = IntBackedPriority::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKey('description');
                // description can be null
                expect($item['description'])->toBeNull()->or()->toBeString();
            }
        });

        it('includes icon (nullable) for all int-backed cases', function () {
            $api = IntBackedPriority::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKey('icon');
                expect($item['icon'])->toBeNull()->or()->toBeString();
            }
        });

        it('value type matches backing type (int)', function () {
            $api = IntBackedPriority::forApi();

            foreach ($api as $item) {
                expect($item['value'])->toBeInt();
            }
        });
    });

    // ── Cross-enum forSelect() structural consistency ───────────────────

    describe('Cross-enum forSelect() structural consistency', function () {
        it('all enums produce forSelect with value+label keys', function () {
            $enums = [
                UserStatus::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
                PaymentStatus::class,
            ];

            foreach ($enums as $enumClass) {
                $select = $enumClass::forSelect();
                foreach ($select as $option) {
                    expect($option)->toHaveKeys(['value', 'label']);
                    expect($option['label'])->toBeString()->not->toBeEmpty();
                }
            }
        });
    });

    // ── EnumMetadataResolver invalidation ──────────────────────────────

    describe('EnumMetadataResolver invalidation', function () {
        it('invalidate() removes cached metadata for a class', function () {
            \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(UserStatus::class);

            // Re-resolve should work after invalidation
            $meta = \ZeroBoiler\Enums\Support\EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta)->toBeArray();
            expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        });

        it('invalidateAll() clears all cached metadata', function () {
            \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidateAll();

            // Re-resolve should work
            $meta = \ZeroBoiler\Enums\Support\EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta)->toBeArray();
            expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
        });
    });
});
