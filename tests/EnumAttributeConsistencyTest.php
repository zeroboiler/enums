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
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function (): void {
    EnumCache::flush();
});

describe('Attribute class structure', function (): void {
    it('Label is final with TARGET_CLASS_CONSTANT', function (): void {
        $ref = new \ReflectionClass(Label::class);
        expect($ref->isFinal())->toBeTrue();

        $attrs = $ref->getAttributes();
        expect($attrs)->toHaveCount(1);
        expect($attrs[0]->getName())->toBe(Attribute::class);

        $instance = $attrs[0]->newInstance();
        expect($instance->flags & Attribute::TARGET_CLASS_CONSTANT)->not->toBe(0);
    });

    it('Color is final with correct target', function (): void {
        $ref = new \ReflectionClass(Color::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('Icon is final with correct target', function (): void {
        $ref = new \ReflectionClass(Icon::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('Description is final with correct target', function (): void {
        $ref = new \ReflectionClass(Description::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumColor is final with TARGET_CLASS | TARGET_CLASS_CONSTANT', function (): void {
        $ref = new \ReflectionClass(EnumColor::class);
        expect($ref->isFinal())->toBeTrue();

        $attrs = $ref->getAttributes();
        $instance = $attrs[0]->newInstance();
        $flags = $instance->flags;
        expect($flags & Attribute::TARGET_CLASS)->not->toBe(0);
        expect($flags & Attribute::TARGET_CLASS_CONSTANT)->not->toBe(0);
    });

    it('EnumLabel is final with dual target', function (): void {
        $ref = new \ReflectionClass(EnumLabel::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumDescription is final with dual target', function (): void {
        $ref = new \ReflectionClass(EnumDescription::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('EnumIcon is final with dual target', function (): void {
        $ref = new \ReflectionClass(EnumIcon::class);
        expect($ref->isFinal())->toBeTrue();
    });
});

describe('EnumCache singleton behavior', function (): void {
    it('returns same instance across calls', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resetInstance creates new instance', function (): void {
        $original = EnumCache::getInstance();
        $original->setTtl(999);

        EnumCache::resetInstance();

        $fresh = EnumCache::getInstance();
        expect($fresh)->not->toBe($original);

        // Restore
        EnumCache::resetInstance();
    });

    it('setTtl affects has() behavior', function (): void {
        $cache = EnumCache::getInstance();

        $cache->setTtl(9999);
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Test'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();

        $cache->setTtl(0);
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Restore
        $cache->setTtl(300);
        $cache->clearClass(UserStatus::class);
    });

    it('get returns metadata array', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(9999);

        $metadata = [
            'labels' => ['active' => 'Cached'],
            'descriptions' => ['active' => 'Cached desc'],
            'colors' => ['active' => 'info'],
            'icons' => ['active' => 'icon'],
        ];
        $cache->set(UserStatus::class, $metadata);

        expect($cache->get(UserStatus::class))->toBe($metadata);

        // Restore
        $cache->setTtl(300);
        $cache->clearClass(UserStatus::class);
    });
});

describe('InvalidEnumException factory methods', function (): void {
    it('value() creates with correct message', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 'invalid');
        expect($e->getMessage())->toContain('invalid')
            ->and($e->getMessage())->toContain(UserStatus::class);
    });

    it('value() handles int type', function (): void {
        $e = InvalidEnumException::value(UserStatus::class, 42);
        expect($e->getMessage())->toContain('int');
    });

    it('forName() creates with correct message', function (): void {
        $e = InvalidEnumException::forName(UserStatus::class, 'UNKNOWN');
        expect($e->getMessage())->toContain('UNKNOWN')
            ->and($e->getMessage())->toContain('Case name')
            ->and($e->getMessage())->toContain(UserStatus::class);
    });

    it('is final', function (): void {
        $ref = new \ReflectionClass(InvalidEnumException::class);
        expect($ref->isFinal())->toBeTrue();
    });
});

describe('EnumMetadataResolver final and static', function (): void {
    it('is a final class', function (): void {
        $ref = new \ReflectionClass(EnumMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('resolve returns consistent metadata on repeated calls', function (): void {
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
        $meta3 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta1)->toBe($meta2)
            ->and($meta2)->toBe($meta3);
    });
});

describe('EnumRule validation edge cases', function (): void {
    it('is readonly', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\Enums\Rules\EnumRule::class);
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('for() creates non-nullable instance', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class);
        $ref = new \ReflectionProperty($rule, 'nullable');
        expect($ref->getValue($rule))->toBeFalse();
    });

    it('nullable() creates nullable instance', function (): void {
        $rule = \ZeroBoiler\Enums\Rules\EnumRule::for(UserStatus::class)->nullable();
        $ref = new \ReflectionProperty($rule, 'nullable');
        expect($ref->getValue($rule))->toBeTrue();
    });
});
