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
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum production readiness audit', function () {
    describe('strict types compliance', function () {
        it('all source files have declare(strict_types=1)', function () {
            $srcDir = __DIR__.'/../src';
            $files = glob($srcDir.'/**/*.php') ?: [];
            expect($files)->not->toBeEmpty();

            foreach ($files as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain('declare(strict_types=1)');
            }
        });

        it('all attribute classes are final', function () {
            $attributes = [
                Color::class,
                Description::class,
                EnumColor::class,
                EnumDescription::class,
                EnumIcon::class,
                EnumLabel::class,
                Icon::class,
                Label::class,
            ];

            foreach ($attributes as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final");
            }
        });

        it('all service classes are final', function () {
            $classes = [
                \ZeroBoiler\Enums\EnumCache::class,
                \ZeroBoiler\Enums\EnumManager::class,
                \ZeroBoiler\Enums\EnumsServiceProvider::class,
                \ZeroBoiler\Enums\Support\EnumMetadataResolver::class,
                \ZeroBoiler\Enums\Support\EnumTestGenerator::class,
                \ZeroBoiler\Enums\Rules\EnumRule::class,
                \ZeroBoiler\Enums\Casts\EnumCast::class,
                \ZeroBoiler\Enums\Exceptions\InvalidEnumException::class,
            ];

            foreach ($classes as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final");
            }
        });
    });

    describe('attribute readonly promoted properties', function () {
        it('Color attribute has readonly string value', function () {
            $attr = new Color('success');
            $ref = new ReflectionProperty($attr, 'value');
            expect($ref->isReadOnly())->toBeTrue();
            expect($ref->getType()->getName())->toBe('string');
            expect($attr->value)->toBe('success');
        });

        it('Label attribute has readonly string value', function () {
            $attr = new Label('Custom Label');
            $ref = new ReflectionProperty($attr, 'value');
            expect($ref->isReadOnly())->toBeTrue();
            expect($attr->value)->toBe('Custom Label');
        });

        it('EnumColor attribute has readonly array properties with defaults', function () {
            $attr = new EnumColor(success: ['active'], danger: ['banned']);
            $refSuccess = new ReflectionProperty($attr, 'success');
            expect($refSuccess->isReadOnly())->toBeTrue();
            expect($attr->success)->toBe(['active']);
            expect($attr->danger)->toBe(['banned']);
            expect($attr->warning)->toBe([]);
            expect($attr->info)->toBe([]);
            expect($attr->secondary)->toBe([]);
        });

        it('EnumLabel attribute has nullable properties', function () {
            $attr = new EnumLabel(labels: ['active' => 'Active']);
            expect($attr->labels)->toBe(['active' => 'Active']);
            expect($attr->label)->toBeNull();
        });
    });

    describe('HasEnumMetadata trait completeness', function () {
        it('provides all expected instance methods', function () {
            $methods = ['label', 'description', 'color', 'icon', 'is', 'isNot', 'in'];
            $trait = new ReflectionClass(HasEnumMetadata::class);

            foreach ($methods as $method) {
                expect($trait->hasMethod($method))->toBeTrue("Missing method: {$method}");
            }
        });

        it('provides all expected static methods', function () {
            $methods = ['forSelect', 'forApi', 'tryFromLabel', 'tryFromName', 'fromName', 'hasCase', 'values', 'labels'];
            $trait = new ReflectionClass(HasEnumMetadata::class);

            foreach ($methods as $method) {
                $ref = $trait->getMethod($method);
                expect($ref->isStatic())->toBeTrue("{$method} must be static");
            }
        });

        it('all public methods have return type declarations', function () {
            $trait = new ReflectionClass(HasEnumMetadata::class);
            $methods = $trait->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "Method {$method->getName()} must have a return type declaration"
                );
            }
        });
    });

    describe('label generation edge cases', function () {
        it('generates title case from SCREAMING_SNAKE_CASE', function () {
            expect(Priority::LOW->label())->toBe('Low');
            expect(Priority::MEDIUM->label())->toBe('Medium');
            expect(Priority::HIGH->label())->toBe('High');
            expect(Priority::URGENT->label())->toBe('Urgent');
        });

        it('generates title case from camelCase-like names', function () {
            // CamelCaseRole fixture
            expect(\ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::Admin->label())->toBeString();
            expect(\ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole::Admin->label())->not->toBeEmpty();
        });

        it('prefers per-case Label over auto-generated', function () {
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
            expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
        });

        it('uses auto-generated for cases without Label attribute', function () {
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('generates label for pure enums using case name', function () {
            expect(PureFeatureFlag::TWO_FACTOR_AUTH->label())->toBe('Two Factor Auth');
            expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
        });
    });

    describe('color resolution with class-level and per-case override', function () {
        it('returns class-level color for mapped cases', function () {
            expect(UserStatus::ACTIVE->color())->toBe('success');
            expect(UserStatus::PENDING->color())->toBe('warning');
            expect(UserStatus::SUSPENDED->color())->toBe('warning');
        });

        it('per-case Color overrides class-level EnumColor', function () {
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('returns secondary for unmapped cases', function () {
            expect(UserStatus::INACTIVE->color())->toBe('secondary');
        });
    });

    describe('icon and description resolution', function () {
        it('returns per-case icon when set', function () {
            expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
        });

        it('returns null for cases without icon', function () {
            expect(UserStatus::INACTIVE->icon())->toBeNull();
            expect(UserStatus::BANNED->icon())->toBeNull();
        });

        it('returns per-case description when set', function () {
            expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
            expect(UserStatus::BANNED->description())->toBe('User is permanently banned');
        });

        it('returns null for cases without description', function () {
            expect(UserStatus::INACTIVE->description())->toBeNull();
        });
    });

    describe('bulk methods structure', function () {
        it('forSelect returns correct structure for string-backed enum', function () {
            $options = UserStatus::forSelect();
            expect($options)->toBeArray();
            expect($options)->toHaveCount(5);

            foreach ($options as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString();
            }

            // Verify values match backed values
            expect(array_column($options, 'value'))->toBe([
                'active', 'inactive', 'pending', 'suspended', 'banned',
            ]);
        });

        it('forSelect returns correct structure for int-backed enum', function () {
            $options = Priority::forSelect();
            expect($options)->toBeArray();
            expect($options)->toHaveCount(4);

            // Int-backed values
            expect(array_column($options, 'value'))->toBe([1, 2, 3, 4]);
        });

        it('forSelect returns case names for pure enums', function () {
            $options = PureFeatureFlag::forSelect();
            expect($options)->toBeArray();

            $values = array_column($options, 'value');
            expect($values)->toContain('TWO_FACTOR_AUTH');
            expect($values)->toContain('DARK_MODE');
        });

        it('forApi returns full metadata structure', function () {
            $api = UserStatus::forApi();
            expect($api)->toBeArray();
            expect($api)->toHaveCount(5);

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['name'])->toBeString();
                expect($item['color'])->toBeString();
            }
        });

        it('values() returns backed values for backed enums', function () {
            expect(Priority::values())->toBe([1, 2, 3, 4]);
            expect(UserStatus::values())->toBe([
                'active', 'inactive', 'pending', 'suspended', 'banned',
            ]);
        });

        it('values() returns case names for pure enums', function () {
            expect(PureFeatureFlag::values())->toBe([
                'TWO_FACTOR_AUTH', 'DARK_MODE', 'BETA_ACCESS',
            ]);
        });

        it('labels() returns labels in declaration order', function () {
            $labels = UserStatus::labels();
            expect($labels)->toHaveCount(5);
            expect($labels[0])->toBe('Active User');
            expect($labels[4])->toBe('Banned');
        });
    });

    describe('lookup methods', function () {
        it('tryFromLabel finds case by label (case-insensitive)', function () {
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('Inactive'))->toBe(UserStatus::INACTIVE);
        });

        it('tryFromLabel returns null for non-existent labels', function () {
            expect(UserStatus::tryFromLabel('NonExistent'))->toBeNull();
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('tryFromName finds case by exact name', function () {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromName('BANNED'))->toBe(UserStatus::BANNED);
        });

        it('tryFromName returns null for non-existent names', function () {
            expect(UserStatus::tryFromName('NON_EXISTENT'))->toBeNull();
        });

        it('tryFromName is case-sensitive', function () {
            expect(UserStatus::tryFromName('active'))->toBeNull();
            expect(UserStatus::tryFromName('Active'))->toBeNull();
        });

        it('fromName throws InvalidEnumException for non-existent names', function () {
            expect(fn () => UserStatus::fromName('NON_EXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns correct boolean', function () {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('active'))->toBeFalse(); // case-sensitive
            expect(UserStatus::hasCase('NON_EXISTENT'))->toBeFalse();
        });
    });

    describe('comparison methods', function () {
        it('is() matches identical enum instances', function () {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
        });

        it('is() matches case name strings', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse(); // case-sensitive
        });

        it('isNot() is negation of is()', function () {
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
            expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
        });

        it('in() checks membership with mixed instances and strings', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->in([UserStatus::BANNED]))->toBeFalse();
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('comparison works with int-backed enums', function () {
            expect(Priority::HIGH->is(Priority::HIGH))->toBeTrue();
            expect(Priority::HIGH->is('HIGH'))->toBeTrue();
            expect(Priority::HIGH->in([Priority::LOW, Priority::MEDIUM]))->toBeFalse();
        });

        it('comparison works with pure enums', function () {
            expect(PureFeatureFlag::TWO_FACTOR_AUTH->is(PureFeatureFlag::TWO_FACTOR_AUTH))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
        });
    });

    describe('EnumRule type safety', function () {
        it('is a readonly class', function () {
            $ref = new ReflectionClass(EnumRule::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('validates string-backed enum values correctly', function () {
            $rule = EnumRule::for(UserStatus::class);
            $fail = fn () => throw new \RuntimeException('fail');
            $pass = fn () => null;

            // Valid values should not trigger fail
            $rule->validate('status', 'active', $pass);
            $rule->validate('status', 'banned', $pass);

            // Invalid value triggers fail
            $failed = false;
            $rule->validate('status', 'invalid', function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('validates int-backed enum values with type checking', function () {
            $rule = EnumRule::for(Priority::class);

            // Valid int
            $rule->validate('priority', 1, fn () => null);

            // String for int-backed should fail
            $failed = false;
            $rule->validate('priority', '1', function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });

        it('nullable variant passes null values', function () {
            $rule = EnumRule::for(UserStatus::class)->nullable();
            $fail = fn () => throw new \RuntimeException('fail');

            // Null should pass for nullable rule
            $rule->validate('status', null, $fail); // No exception thrown
        });

        it('non-nullable variant rejects null values', function () {
            $rule = EnumRule::for(UserStatus::class);
            $failed = false;
            $rule->validate('status', null, function () use (&$failed): void {
                $failed = true;
            });
            expect($failed)->toBeTrue();
        });
    });

    describe('EnumCache singleton lifecycle', function () {
        it('returns same instance on consecutive calls', function () {
            $a = \ZeroBoiler\Enums\EnumCache::getInstance();
            $b = \ZeroBoiler\Enums\EnumCache::getInstance();
            expect($a)->toBe($b);
        });

        it('flush clears all entries', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->set(UserStatus::class, [
                'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            expect($cache->has(UserStatus::class))->toBeTrue();

            $cache->clear();
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('resetInstance creates a new singleton', function () {
            $original = \ZeroBoiler\Enums\EnumCache::getInstance();
            \ZeroBoiler\Enums\EnumCache::resetInstance();
            $newInstance = \ZeroBoiler\Enums\EnumCache::getInstance();

            expect($original)->not->toBe($newInstance);

            // Restore for other tests
            \ZeroBoiler\Enums\EnumCache::resetInstance();
        });

        it('TTL of 0 disables caching', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $cache->setTtl(0);
            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Test'], 'descriptions' => [], 'colors' => [], 'icons' => [],
            ]);
            expect($cache->has(UserStatus::class))->toBeFalse();

            // Restore TTL
            $cache->setTtl(300);
        });

        it('clearClass removes only the specified class', function () {
            $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
            $meta = ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []];
            $cache->set(UserStatus::class, $meta);
            $cache->set(Priority::class, $meta);

            $cache->clearClass(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeTrue();

            $cache->clear();
        });
    });

    describe('EnumMetadataResolver caching', function () {
        it('resolves metadata and caches it', function () {
            \ZeroBoiler\Enums\EnumCache::getInstance()->clear();

            $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
            $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta1)->toBe($meta2);
            expect($meta1)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);

            \ZeroBoiler\Enums\EnumCache::getInstance()->clear();
        });

        it('metadata labels contain per-case overrides', function () {
            \ZeroBoiler\Enums\EnumCache::getInstance()->clear();
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta['labels']['active'])->toBe('Active User');
            expect($meta['labels']['pending'])->toBe('Awaiting Verification');

            \ZeroBoiler\Enums\EnumCache::getInstance()->clear();
        });
    });
});
