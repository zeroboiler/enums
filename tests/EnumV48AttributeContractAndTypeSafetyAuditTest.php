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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * V48 — Attribute Reflection Contract & Edge Case Audit
 *
 * Validates that all 8 attribute classes have correct structure:
 * - Final, readonly promoted properties, correct Attribute targets
 * - ValidationAttribute implementations return correct ruleKey()
 * - Class-level attributes support TARGET_CLASS | TARGET_CLASS_CONSTANT
 * - Per-case attributes are TARGET_CLASS_CONSTANT only
 */
describe('V48 Attribute Reflection Contract', function () {
    // --- Per-Case Attribute Structure ---

    it('Label attribute is final with single readonly string property', function () {
        $ref = new ReflectionClass(Label::class);
        expect($ref->isFinal())->toBeTrue();

        $prop = $ref->getProperty('value');
        expect($prop->isPublic())->toBeTrue();
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    it('Color attribute is final with single readonly string property', function () {
        $ref = new ReflectionClass(Color::class);
        expect($ref->isFinal())->toBeTrue();

        $prop = $ref->getProperty('value');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    it('Icon attribute is final with single readonly string property', function () {
        $ref = new ReflectionClass(Icon::class);
        expect($ref->isFinal())->toBeTrue();

        $prop = $ref->getProperty('value');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    it('Description attribute is final with single readonly string property', function () {
        $ref = new ReflectionClass(Description::class);
        expect($ref->isFinal())->toBeTrue();

        $prop = $ref->getProperty('value');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    // --- Class-Level Attribute Structure ---

    it('EnumLabel supports TARGET_CLASS and TARGET_CLASS_CONSTANT', function () {
        $ref = new ReflectionClass(EnumLabel::class);
        expect($ref->isFinal())->toBeTrue();

        $attrs = $ref->getAttributes(Attribute::class);
        $flags = $attrs[0]->newInstance()->flags;
        expect($flags & Attribute::TARGET_CLASS)->toBeGreaterThan(0);
        expect($flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);

        // Has labels (array|null) and label (string|null) properties
        expect($ref->hasProperty('labels'))->toBeTrue();
        expect($ref->hasProperty('label'))->toBeTrue();
    });

    it('EnumColor supports TARGET_CLASS and TARGET_CLASS_CONSTANT', function () {
        $ref = new ReflectionClass(EnumColor::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $flags = $attrs[0]->newInstance()->flags;
        expect($flags & Attribute::TARGET_CLASS)->toBeGreaterThan(0);
        expect($flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);

        // Has 5 color group properties, all arrays
        foreach (['success', 'danger', 'warning', 'info', 'secondary'] as $color) {
            $prop = $ref->getProperty($color);
            expect($prop->isReadOnly())->toBeTrue();
            expect($prop->getType()->getName())->toBe('array');
        }
    });

    it('EnumIcon supports TARGET_CLASS and TARGET_CLASS_CONSTANT', function () {
        $ref = new ReflectionClass(EnumIcon::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $flags = $attrs[0]->newInstance()->flags;
        expect($flags & Attribute::TARGET_CLASS)->toBeGreaterThan(0);
        expect($flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);

        // Has default (string|null) and icons (array) properties
        expect($ref->hasProperty('default'))->toBeTrue();
        expect($ref->hasProperty('icons'))->toBeTrue();
    });

    it('EnumDescription supports TARGET_CLASS and TARGET_CLASS_CONSTANT', function () {
        $ref = new ReflectionClass(EnumDescription::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $flags = $attrs[0]->newInstance()->flags;
        expect($flags & Attribute::TARGET_CLASS)->toBeGreaterThan(0);
        expect($flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);

        // Has descriptions (array|null) and description (string|null)
        expect($ref->hasProperty('descriptions'))->toBeTrue();
        expect($ref->hasProperty('description'))->toBeTrue();
    });

    // --- Per-Case Attribute Target Verification ---

    it('Label has only TARGET_CLASS_CONSTANT flag', function () {
        $ref = new ReflectionClass(Label::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $flags = $attrs[0]->newInstance()->flags;
        expect($flags & Attribute::TARGET_CLASS)->toBe(0);
        expect($flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);
    });

    it('Color has only TARGET_CLASS_CONSTANT flag', function () {
        $ref = new ReflectionClass(Color::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $flags = $attrs[0]->newInstance()->flags;
        expect($flags & Attribute::TARGET_CLASS)->toBe(0);
        expect($flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);
    });

    it('Icon has only TARGET_CLASS_CONSTANT flag', function () {
        $ref = new ReflectionClass(Icon::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $flags = $attrs[0]->newInstance()->flags;
        expect($flags & Attribute::TARGET_CLASS)->toBe(0);
        expect($flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);
    });

    it('Description has only TARGET_CLASS_CONSTANT flag', function () {
        $ref = new ReflectionClass(Description::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $flags = $attrs[0]->newInstance()->flags;
        expect($flags & Attribute::TARGET_CLASS)->toBe(0);
        expect($flags & Attribute::TARGET_CLASS_CONSTANT)->toBeGreaterThan(0);
    });

    // --- EnumCache Singleton Contract ---

    it('EnumCache is final class', function () {
        expect((new ReflectionClass(EnumCache::class))->isFinal())->toBeTrue();
    });

    it('EnumCache constructor is private', function () {
        $ctor = (new ReflectionClass(EnumCache::class))->getConstructor();
        expect($ctor)->not->toBeNull();
        expect($ctor->isPrivate())->toBeTrue();
    });

    it('EnumCache __clone has never return type', function () {
        $method = (new ReflectionClass(EnumCache::class))->getMethod('__clone');
        expect($method->getReturnType()->getName())->toBe('never');
    });

    it('EnumCache __wakeup has never return type', function () {
        $method = (new ReflectionClass(EnumCache::class))->getMethod('__wakeup');
        expect($method->getReturnType()->getName())->toBe('never');
    });

    it('EnumCache __serialize has never return type', function () {
        $method = (new ReflectionClass(EnumCache::class))->getMethod('__serialize');
        expect($method->getReturnType()->getName())->toBe('never');
    });

    it('EnumCache __unserialize has never return type', function () {
        $method = (new ReflectionClass(EnumCache::class))->getMethod('__unserialize');
        expect($method->getReturnType()->getName())->toBe('never');
    });

    // --- EnumManager Contract ---

    it('EnumManager is final readonly class', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumManager has 8 public delegation methods', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);
        $methods = array_filter(
            $ref->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn (ReflectionMethod $m): bool => ! $m->isConstructor()
        );
        expect(count($methods))->toBe(8);
    });

    // --- EnumRule Contract ---

    it('EnumRule is final readonly class', function () {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumRule implements ValidationRule interface', function () {
        $ref = new ReflectionClass(EnumRule::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Validation\ValidationRule::class))->toBeTrue();
    });

    it('EnumRule validate method has Override attribute', function () {
        $method = (new ReflectionClass(EnumRule::class))->getMethod('validate');
        $attrs = $method->getAttributes(\Override::class);
        expect($attrs)->not->toBeEmpty();
    });

    // --- EnumCast Contract ---

    it('EnumCast is final class', function () {
        $ref = new ReflectionClass(EnumCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumCast implements CastsAttributes interface', function () {
        $ref = new ReflectionClass(EnumCast::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
    });

    it('EnumCast constructor has readonly enumClass parameter', function () {
        $ref = new ReflectionClass(EnumCast::class);
        $ctor = $ref->getConstructor();
        $params = $ctor->getParameters();
        expect($params[0]->getName())->toBe('enumClass');
        expect($params[0]->isReadOnly())->toBeTrue();
    });

    // --- InvalidEnumException Contract ---

    it('InvalidEnumException is final class extending Exception', function () {
        $ref = new ReflectionClass(InvalidEnumException::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isSubclassOf(Exception::class))->toBeTrue();
    });

    it('InvalidEnumException has named constructors value() and forName()', function () {
        $ref = new ReflectionClass(InvalidEnumException::class);
        expect($ref->hasMethod('value'))->toBeTrue();
        expect($ref->hasMethod('forName'))->toBeTrue();
    });

    it('InvalidEnumException __toString returns class name prefix', function () {
        $e = InvalidEnumException::forName('TestEnum', 'INVALID');
        $str = (string) $e;
        expect($str)->toContain('InvalidEnumException');
        expect($str)->toContain('INVALID');
    });

    // --- EnumMetadataResolver Contract ---

    it('EnumMetadataResolver is final class', function () {
        $ref = new ReflectionClass(EnumMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumMetadataResolver resolve() returns array with 4 keys', function () {
        EnumCache::getInstance()->clear();
        EnumMetadataResolver::invalidateAll();

        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
        expect($meta)->toBeArray();
        expect($meta)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    });

    // --- Enum Facade Contract ---

    it('Enum facade is final class extending Facade', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isSubclassOf(\Illuminate\Support\Facades\Facade::class))->toBeTrue();
    });

    it('Enum facade getFacadeAccessor returns correct key', function () {
        $method = (new ReflectionClass(\ZeroBoiler\Enums\Facades\Enum::class))->getMethod('getFacadeAccessor');
        $method->setAccessible(true);
        $key = $method->invoke(null);
        expect($key)->toBe('zeroboiler.enum');
    });

    // --- EnumsServiceProvider Contract ---

    it('EnumsServiceProvider is final class', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumsServiceProvider has Override on register and boot', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class);

        $register = $ref->getMethod('register');
        expect($register->getAttributes(\Override::class))->not->toBeEmpty();

        $boot = $ref->getMethod('boot');
        expect($boot->getAttributes(\Override::class))->not->toBeEmpty();
    });

    // --- EnumTestGenerator Contract ---

    it('EnumTestGenerator is final class', function () {
        $ref = new ReflectionClass(\ZeroBoiler\Enums\Support\EnumTestGenerator::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumTestGenerator generate() returns non-empty string for a valid enum', function () {
        $output = \ZeroBoiler\Enums\Support\EnumTestGenerator::generate(
            \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class
        );
        expect($output)->toBeString();
        expect(strlen($output))->toBeGreaterThan(100);
        expect($output)->toContain('declare(strict_types=1)');
        expect($output)->toContain('describe(');
    });
});

describe('V48 EnumCache TTL Behavior', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('TTL of 0 disables caching (has() always returns false)', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set('TestClass', [
            'labels' => ['test' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestClass'))->toBeFalse();
    });

    it('Negative TTL is normalized to 0', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
    });

    it('Cache entry expires after TTL', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL
        $cache->set('TestEnum', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has('TestEnum'))->toBeTrue();

        // Wait for TTL to expire
        sleep(2);

        expect($cache->has('TestEnum'))->toBeFalse();
    });

    it('flush() clears all entries via static accessor', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set('ClassA', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('ClassB', [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::flush();

        expect($cache->has('ClassA'))->toBeFalse();
        expect($cache->has('ClassB'))->toBeFalse();
    });

    it('clearClass() removes specific class without affecting others', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);
        $cache->set('ClassA', [
            'labels' => ['a' => 'A'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set('ClassB', [
            'labels' => ['b' => 'B'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass('ClassA');

        expect($cache->has('ClassA'))->toBeFalse();
        expect($cache->has('ClassB'))->toBeTrue();
    });

    it('get() throws OutOfBoundsException for missing entry', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistent'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('resetInstance() allows new singleton creation', function () {
        $first = EnumCache::getInstance();
        $first->setTtl(999);

        EnumCache::resetInstance();

        $second = EnumCache::getInstance();
        // TTL resets to default 300
        expect($second->getTtl())->toBe(300);
    });
});

describe('V48 HasEnumMetadata Type Safety', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('toValue() returns string for string-backed enum', function () {
        $case = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::ACTIVE;
        expect($case->toValue())->toBeString();
    });

    it('toValue() returns int for int-backed enum', function () {
        $case = \ZeroBoiler\Enums\Tests\Fixtures\IntPriority::HIGH;
        expect($case->toValue())->toBeInt();
    });

    it('toValue() returns string for pure enum (case name)', function () {
        $case = \ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag::Enabled;
        expect($case->toValue())->toBeString();
        expect($case->toValue())->toBe($case->name);
    });

    it('forSelect() returns consistent structure across all enum types', function () {
        $stringBacked = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::forSelect();
        $intBacked = \ZeroBoiler\Enums\Tests\Fixtures\IntPriority::forSelect();
        $pure = \ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag::forSelect();

        foreach ([$stringBacked, $intBacked, $pure] as $select) {
            expect($select)->toBeArray();
            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        }
    });

    it('forApi() returns 6 keys per case for all enum types', function () {
        $api = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::forApi();
        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBeString()->not->toBeEmpty();
        }
    });

    it('in() with empty array always returns false', function () {
        $case = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::ACTIVE;
        expect($case->in([]))->toBeFalse();
    });

    it('notIn() with empty array always returns true', function () {
        $case = \ZeroBoiler\Enums\Tests\Fixtures\UserStatus::ACTIVE;
        expect($case->notIn([]))->toBeTrue();
    });
});
