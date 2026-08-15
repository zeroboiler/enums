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
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('V19 — Production Type Safety And Contract Hardening', function () {
    // ─── Enum: Attribute final classes are truly final ───────────────────

    it('all per-case attribute classes are final', function () {
        expect(new \ReflectionClass(Label::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(Color::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(Icon::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(Description::class))->isFinal()->toBeTrue();
    });

    it('all class-level attribute classes are final', function () {
        expect(new \ReflectionClass(EnumLabel::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(EnumColor::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(EnumIcon::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(EnumDescription::class))->isFinal()->toBeTrue();
    });

    it('infrastructure classes are final', function () {
        expect(new \ReflectionClass(EnumCache::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(\ZeroBoiler\Enums\EnumManager::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(EnumCast::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(EnumRule::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(InvalidEnumException::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(\ZeroBoiler\Enums\EnumsServiceProvider::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(EnumMetadataResolver::class))->isFinal()->toBeTrue();
        expect(new \ReflectionClass(\ZeroBoiler\Enums\Support\EnumTestGenerator::class))->isFinal()->toBeTrue();
    });

    // ─── Enum: EnumManager and EnumRule are readonly ────────────────────

    it('EnumManager is a readonly class', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\EnumManager::class);
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('EnumRule is a readonly class', function () {
        $ref = new \ReflectionClass(EnumRule::class);
        expect($ref->isReadOnly())->toBeTrue();
    });

    // ─── Enum: All attribute properties are readonly promoted ────────────

    it('Label attribute has readonly promoted property', function () {
        $ref = new \ReflectionProperty(Label::class, 'value');
        expect($ref->isReadOnly())->toBeTrue();
        expect($ref->isPromoted())->toBeTrue();
        expect($ref->getType()->getName())->toBe('string');
    });

    it('EnumColor attribute has all readonly promoted properties', function () {
        $props = ['success', 'danger', 'warning', 'info', 'secondary'];
        foreach ($props as $prop) {
            $ref = new \ReflectionProperty(EnumColor::class, $prop);
            expect($ref->isReadOnly())->toBeTrue("EnumColor::\${$prop} should be readonly");
            expect($ref->isPromoted())->toBeTrue("EnumColor::\${$prop} should be promoted");
            expect($ref->getType()->getName())->toBe('array');
        }
    });

    it('EnumLabel has both labels and label properties as readonly promoted', function () {
        $labelsRef = new \ReflectionProperty(EnumLabel::class, 'labels');
        expect($labelsRef->isReadOnly())->toBeTrue();
        expect($labelsRef->isPromoted())->toBeTrue();

        $labelRef = new \ReflectionProperty(EnumLabel::class, 'label');
        expect($labelRef->isReadOnly())->toBeTrue();
        expect($labelRef->isPromoted())->toBeTrue();
    });

    it('EnumIcon has readonly promoted properties with correct types', function () {
        $defaultRef = new \ReflectionProperty(EnumIcon::class, 'default');
        expect($defaultRef->isReadOnly())->toBeTrue();
        expect($defaultRef->getType()->allowsNull())->toBeTrue();

        $iconsRef = new \ReflectionProperty(EnumIcon::class, 'icons');
        expect($iconsRef->isReadOnly())->toBeTrue();
        expect($iconsRef->getType()->getName())->toBe('array');
    });

    // ─── Enum: EnumCache singleton lifecycle ────────────────────────────

    it('EnumCache singleton is shared across getInstance calls', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();
        expect($a)->toBe($b);
    });

    it('EnumCache resetInstance creates a fresh singleton', function () {
        EnumCache::getInstance()->setTtl(999);
        expect(EnumCache::getInstance()->getTtl())->toBe(999);

        EnumCache::resetInstance();

        $fresh = EnumCache::getInstance();
        expect($fresh->getTtl())->toBe(300); // default
        EnumCache::resetInstance();
    });

    it('EnumCache setTtl clamps negative values to 0', function () {
        EnumCache::getInstance()->setTtl(-5);
        expect(EnumCache::getInstance()->getTtl())->toBe(0);
        EnumCache::getInstance()->setTtl(300); // restore default
    });

    it('EnumCache clearClass only removes the target class', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->set('EnumA', ['labels' => ['a' => 'A'], 'descriptions' => [], 'colors' => [], 'icons' => []]);
        $cache->set('EnumB', ['labels' => ['b' => 'B'], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        $cache->clearClass('EnumA');
        expect($cache->has('EnumA'))->toBeFalse();
        expect($cache->has('EnumB'))->toBeTrue();

        EnumCache::resetInstance();
    });

    it('EnumCache flush clears everything', function () {
        EnumCache::resetInstance();
        $cache = EnumCache::getInstance();
        $cache->set('X', ['labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => []]);

        EnumCache::flush();
        expect($cache->has('X'))->toBeFalse();
        EnumCache::resetInstance();
    });

    // ─── Enum: InvalidEnumException factory methods ─────────────────────

    it('InvalidEnumException::value formats null correctly', function () {
        $ex = InvalidEnumException::value('MyEnum', null);
        expect($ex->getMessage())->toContain('null');
        expect($ex->getMessage())->toContain('MyEnum');
    });

    it('InvalidEnumException::forName includes class and name', function () {
        $ex = InvalidEnumException::forName('StatusEnum', 'UNKNOWN');
        expect($ex->getMessage())->toContain('UNKNOWN');
        expect($ex->getMessage())->toContain('StatusEnum');
    });

    it('InvalidEnumException::__toString includes class name', function () {
        $ex = InvalidEnumException::forName('TestEnum', 'BAD');
        $str = (string) $ex;
        expect($str)->toContain('InvalidEnumException');
    });

    // ─── Enum: EnumRule named constructors ─────────────────────────────

    it('EnumRule::for creates non-nullable rule', function () {
        $rule = EnumRule::for(UserStatus::class);
        $ref = new \ReflectionProperty($rule, 'nullable');
        expect($ref->getValue($rule))->toBeFalse();
    });

    it('EnumRule::nullable() creates nullable rule', function () {
        $rule = EnumRule::for(UserStatus::class)->nullable();
        $ref = new \ReflectionProperty($rule, 'nullable');
        expect($ref->getValue($rule))->toBeTrue();
    });

    it('EnumRule nullable() returns a new instance', function () {
        $original = EnumRule::for(UserStatus::class);
        $nullable = $original->nullable();
        expect($nullable)->not->toBe($original);
    });

    // ─── Enum: Metadata resolver contract ───────────────────────────────

    it('EnumMetadataResolver::invalidate removes cached metadata', function () {
        EnumCache::resetInstance();

        $meta = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta)->toBeArray();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidate(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();

        EnumCache::resetInstance();
    });

    it('EnumMetadataResolver::invalidateAll flushes everything', function () {
        EnumCache::resetInstance();

        EnumMetadataResolver::resolve(UserStatus::class);

        EnumMetadataResolver::invalidateAll();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();

        EnumCache::resetInstance();
    });

    // ─── Enum: Attribute TARGET correctness ────────────────────────────

    it('per-case attributes only target class constants', function () {
        $labelAttr = new \ReflectionClass(Label::class);
        $attrs = $labelAttr->getAttributes(\Attribute::class);
        $targets = $attrs[0]->getArguments();
        expect($targets[0])->toBe(Attribute::TARGET_CLASS_CONSTANT);

        $colorAttr = new \ReflectionClass(Color::class);
        $attrs = $colorAttr->getAttributes(\Attribute::class);
        $targets = $attrs[0]->getArguments();
        expect($targets[0])->toBe(Attribute::TARGET_CLASS_CONSTANT);
    });

    it('class-level attributes target both class and class constant', function () {
        $ref = new \ReflectionClass(EnumColor::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        $targets = $attrs[0]->getArguments();
        expect($targets)->toContain(Attribute::TARGET_CLASS);
        expect($targets)->toContain(Attribute::TARGET_CLASS_CONSTANT);
    });

    // ─── Enum: BackedEnum cast contract ────────────────────────────────

    it('EnumCast get returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->get(new \stdClass(), 'status', null, []);
        expect($result)->toBeNull();
    });

    it('EnumCast set returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->set(new \stdClass(), 'status', null, []);
        expect($result)->toBeNull();
    });

    it('EnumCast set rejects wrong enum type', function () {
        $cast = new EnumCast(UserStatus::class);
        expect(fn () => $cast->set(
            new \stdClass(),
            'status',
            Priority::ACTIVE,
            [],
        ))->toThrow(\InvalidArgumentException::class);
    });

    it('EnumCast serialize returns backed value for enum instance', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass(), 'status', UserStatus::ACTIVE, []);
        expect($result)->toBe('active');
    });

    it('EnumCast serialize passes through string values', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass(), 'status', 'active', []);
        expect($result)->toBe('active');
    });

    it('EnumCast serialize returns null for null', function () {
        $cast = new EnumCast(UserStatus::class);
        $result = $cast->serialize(new \stdClass(), 'status', null, []);
        expect($result)->toBeNull();
    });

    // ─── Enum: every source file has strict types ────────────────────────

    it('HasEnumMetadata trait has declare(strict_types=1)', function () {
        $content = file_get_contents((new \ReflectionClass(HasEnumMetadata::class))->getFileName());
        expect($content)->toContain('declare(strict_types=1)');
    });

    it('EnumCache has declare(strict_types=1)', function () {
        $content = file_get_contents((new \ReflectionClass(EnumCache::class))->getFileName());
        expect($content)->toContain('declare(strict_types=1)');
    });

    it('EnumMetadataResolver has declare(strict_types=1)', function () {
        $content = file_get_contents((new \ReflectionClass(EnumMetadataResolver::class))->getFileName());
        expect($content)->toContain('declare(strict_types=1)');
    });
});
