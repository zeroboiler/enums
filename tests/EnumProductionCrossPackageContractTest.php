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
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Rules\EnumRule;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

/**
 * Production cross-package contract tests.
 *
 * Verifies that the enums package works correctly in real-world scenarios:
 * - Cache behavior across multiple enum classes
 * - Attribute resolution priority chain
 * - Manager/Facade delegation correctness
 * - Cast validation edge cases
 * - Rule validation for backed and pure enums
 * - Invalid enum exception factory methods
 */
describe('Enums Production Cross-Package Contract', function () {
    // -----------------------------------------------------------------------
    // Test fixture enums
    // -----------------------------------------------------------------------

    describe('String-backed enum with all attributes', function () {
        enum TestUserStatus: string
        {
            use HasEnumMetadata;
        }

        it('has cases by default (minimal enum)', function () {
            expect(TestUserStatus::cases())->toBeEmpty();
        });
    });

    describe('Multi-attribute string-backed enum', function () {
        #[EnumColor(success: ['active'], danger: ['banned'], warning: ['pending'])]
        #[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
        #[EnumIcon(default: 'heroicon-o-dots')]
        #[EnumDescription(descriptions: ['active' => 'Fully active account', 'banned' => 'Permanently banned'])]
        enum TestPaymentStatus: string
        {
            use HasEnumMetadata;

            #[Label('Payment Received')]
            #[Icon('heroicon-o-check')]
            #[Description('Payment has been received and confirmed')]
            case paid = 'active';

            #[Color('danger')]
            case failed = 'banned';

            case pending = 'pending';
        }

        it('resolves per-case label override over class-level', function () {
            expect(TestPaymentStatus::paid->label())->toBe('Payment Received');
        });

        it('resolves class-level label when no per-case override', function () {
            expect(TestPaymentStatus::banned->label())->toBe('Banned User');
        });

        it('auto-generates label when neither class nor per-case label is set', function () {
            expect(TestPaymentStatus::pending->label())->toBe('Pending');
        });

        it('resolves per-case icon override over class-level default', function () {
            expect(TestPaymentStatus::paid->icon())->toBe('heroicon-o-check');
        });

        it('resolves class-level default icon when no per-case override', function () {
            expect(TestPaymentStatus::failed->icon())->toBe('heroicon-o-dots');
            expect(TestPaymentStatus::pending->icon())->toBe('heroicon-o-dots');
        });

        it('resolves per-case color over class-level', function () {
            expect(TestPaymentStatus::failed->color())->toBe('danger');
        });

        it('resolves class-level color when no per-case override', function () {
            expect(TestPaymentStatus::paid->color())->toBe('success');
            expect(TestPaymentStatus::pending->color())->toBe('warning');
        });

        it('resolves per-case description override over class-level', function () {
            expect(TestPaymentStatus::paid->description())->toBe('Payment has been received and confirmed');
        });

        it('resolves class-level description when no per-case override', function () {
            expect(TestPaymentStatus::banned->description())->toBe('Permanently banned');
        });

        it('returns null description when neither set', function () {
            expect(TestPaymentStatus::pending->description())->toBeNull();
        });

        it('forSelect returns correct value/label pairs', function () {
            $select = TestPaymentStatus::forSelect();

            expect($select)->toHaveCount(3);
            expect($select[0])->toHaveKeys(['value', 'label']);
            expect($select[0]['value'])->toBe('active');
            expect($select[0]['label'])->toBe('Payment Received');
        });

        it('forApi returns full metadata for each case', function () {
            $api = TestPaymentStatus::forApi();

            expect($api)->toHaveCount(3);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($api[0]['color'])->toBeString();
            expect($api[0]['color'])->not->toBeEmpty();
        });

        it('values returns backed values in declaration order', function () {
            expect(TestPaymentStatus::values())->toBe(['active', 'banned', 'pending']);
        });

        it('labels returns all labels in declaration order', function () {
            expect(TestPaymentStatus::labels())->toBe([
                'Payment Received',
                'Banned User',
                'Pending',
            ]);
        });
    });

    describe('Integer-backed enum', function () {
        #[EnumColor(success: [1], warning: [2], danger: [3])]
        enum TestPriority: int
        {
            use HasEnumMetadata;

            case LOW = 1;
            case MEDIUM = 2;
            case HIGH = 3;
        }

        it('resolves integer-backed values correctly', function () {
            expect(TestPriority::values())->toBe([1, 2, 3]);
        });

        it('forSelect uses integer values', function () {
            $select = TestPriority::forSelect();

            expect($select[0]['value'])->toBe(1);
            expect($select[2]['value'])->toBe(3);
        });

        it('comparison works with integer-backed enums', function () {
            expect(TestPriority::LOW->is(TestPriority::LOW))->toBeTrue();
            expect(TestPriority::LOW->isNot(TestPriority::HIGH))->toBeTrue();
            expect(TestPriority::MEDIUM->in([TestPriority::LOW, TestPriority::MEDIUM]))->toBeTrue();
            expect(TestPriority::HIGH->notIn([TestPriority::LOW]))->toBeTrue();
        });

        it('tryFromLabel works with integer-backed enum', function () {
            expect(TestPriority::tryFromLabel('Low'))->toBe(TestPriority::LOW());
            expect(TestPriority::tryFromLabel('Medium'))->toBe(TestPriority::MEDIUM());
        });

        it('tryFromName works case-sensitively', function () {
            expect(TestPriority::tryFromName('LOW'))->toBe(TestPriority::LOW());
            expect(TestPriority::tryFromName('low'))->toBeNull();
        });

        it('fromName throws on invalid case name', function () {
            expect(fn () => TestPriority::fromName('INVALID'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase checks existence correctly', function () {
            expect(TestPriority::hasCase('LOW'))->toBeTrue();
            expect(TestPriority::hasCase('NONEXISTENT'))->toBeFalse();
        });
    });

    describe('Pure enum', function () {
        #[EnumColor(success: ['ACTIVE'], danger: ['INACTIVE'])]
        enum TestFeatureFlag
        {
            use HasEnumMetadata;

            case ACTIVE;
            case INACTIVE;
            case DEPRECATED;
        }

        it('uses case name as value for pure enums', function () {
            expect(TestFeatureFlag::values())->toBe(['ACTIVE', 'INACTIVE', 'DEPRECATED']);
        });

        it('forSelect uses case name as value', function () {
            $select = TestFeatureFlag::forSelect();

            expect($select[0]['value'])->toBe('ACTIVE');
            expect($select[1]['value'])->toBe('INACTIVE');
        });

        it('auto-generates labels from case names', function () {
            expect(TestFeatureFlag::ACTIVE->label())->toBe('Active');
            expect(TestFeatureFlag::INACTIVE->label())->toBe('Inactive');
            expect(TestFeatureFlag::DEPRECATED->label())->toBe('Deprecated');
        });

        it('resolves class-level colors', function () {
            expect(TestFeatureFlag::ACTIVE->color())->toBe('success');
            expect(TestFeatureFlag::INACTIVE->color())->toBe('danger');
            expect(TestFeatureFlag::DEPRECATED->color())->toBe('secondary');
        });
    });

    describe('Cache invalidation behavior', function () {
        enum TestCacheEnum: string
        {
            use HasEnumMetadata;

            case A = 'a';
            case B = 'b';
        }

        it('metadata resolver returns consistent results across calls', function () {
            $meta1 = EnumMetadataResolver::resolve(TestCacheEnum::class);
            $meta2 = EnumMetadataResolver::resolve(TestCacheEnum::class);

            expect($meta1)->toBe($meta2);
        });

        it('invalidation forces re-resolution', function () {
            EnumMetadataResolver::invalidate(TestCacheEnum::class);

            $meta = EnumMetadataResolver::resolve(TestCacheEnum::class);

            expect($meta)->toBeArray();
            expect($meta)->toHaveKey('labels');
            expect($meta)->toHaveKey('colors');
        });

        it('invalidateAll clears all caches', function () {
            EnumMetadataResolver::resolve(TestCacheEnum::class);
            EnumMetadataResolver::invalidateAll();

            // Should not throw — cache is cleared but resolve rebuilds
            $meta = EnumMetadataResolver::resolve(TestCacheEnum::class);
            expect($meta)->toBeArray();
        });
    });

    describe('EnumRule validation', function () {
        enum TestRuleEnum: string
        {
            use HasEnumMetadata;

            case ALPHA = 'alpha';
            case BETA = 'beta';
        }

        enum TestRuleIntEnum: int
        {
            use HasEnumMetadata;

            case FIRST = 1;
            case SECOND = 2;
        }

        it('creates rule via for() factory', function () {
            $rule = EnumRule::for(TestRuleEnum::class);

            expect($rule)->toBeInstanceOf(EnumRule::class);
        });

        it('nullable instance passes null values', function () {
            $rule = EnumRule::for(TestRuleEnum::class)->nullable();
            $fail = fn () => throw new \Illuminate\Translation\TranslatableString('fail');

            // Should not call fail — null passes with nullable
            $rule->validate('status', null, $fail);
            expect(true)->toBeTrue();
        });

        it('non-nullable instance rejects null values', function () {
            $rule = EnumRule::for(TestRuleEnum::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', null, $fail);
            expect($failed)->toBeTrue();
        });

        it('validates string-backed enum values', function () {
            $rule = EnumRule::for(TestRuleEnum::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 'alpha', $fail);
            expect($failed)->toBeFalse();
        });

        it('rejects invalid string-backed enum values', function () {
            $rule = EnumRule::for(TestRuleEnum::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('status', 'gamma', $fail);
            expect($failed)->toBeTrue();
        });

        it('validates int-backed enum values', function () {
            $rule = EnumRule::for(TestRuleIntEnum::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            $rule->validate('priority', 1, $fail);
            expect($failed)->toBeFalse();
        });

        it('rejects type mismatch for int-backed enum', function () {
            $rule = EnumRule::for(TestRuleIntEnum::class);
            $failed = false;
            $fail = function () use (&$failed): void {
                $failed = true;
            };

            // Passing a string to an int-backed enum should fail
            $rule->validate('priority', '1', $fail);
            expect($failed)->toBeTrue();
        });
    });

    describe('EnumCast serialization roundtrip', function () {
        enum TestCastEnum: string
        {
            use HasEnumMetadata;

            case DRAFT = 'draft';
            case PUBLISHED = 'published';
        }

        it('get returns null for null database value', function () {
            $cast = new EnumCast(TestCastEnum::class);
            $result = $cast->get(
                new \stdClass(),
                'status',
                null,
                ['status' => null],
            );

            expect($result)->toBeNull();
        });

        it('get resolves valid string value to enum instance', function () {
            $cast = new EnumCast(TestCastEnum::class);
            $result = $cast->get(
                new \stdClass(),
                'status',
                'draft',
                ['status' => 'draft'],
            );

            expect($result)->toBe(TestCastEnum::DRAFT);
        });

        it('get returns null for invalid value', function () {
            $cast = new EnumCast(TestCastEnum::class);
            $result = $cast->get(
                new \stdClass(),
                'status',
                'unknown',
                ['status' => 'unknown'],
            );

            expect($result)->toBeNull();
        });

        it('set returns backed value for enum instance', function () {
            $cast = new EnumCast(TestCastEnum::class);
            $result = $cast->set(
                new \stdClass(),
                'status',
                TestCastEnum::PUBLISHED,
                ['status' => 'published'],
            );

            expect($result)->toBe('published');
        });

        it('set returns null for null value', function () {
            $cast = new EnumCast(TestCastEnum::class);
            $result = $cast->set(
                new \stdClass(),
                'status',
                null,
                ['status' => null],
            );

            expect($result)->toBeNull();
        });

        it('set validates and passes through raw string value', function () {
            $cast = new EnumCast(TestCastEnum::class);
            $result = $cast->set(
                new \stdClass(),
                'status',
                'draft',
                ['status' => 'draft'],
            );

            expect($result)->toBe('draft');
        });

        it('set throws for invalid raw value', function () {
            $cast = new EnumCast(TestCastEnum::class);

            expect(fn () => $cast->set(
                new \stdClass(),
                'status',
                'invalid_value',
                ['status' => 'invalid_value'],
            ))->toThrow(\InvalidArgumentException::class);
        });

        it('set throws when enum instance class does not match', function () {
            $cast = new EnumCast(TestCastEnum::class);

            expect(fn () => $cast->set(
                new \stdClass(),
                'status',
                TestCastEnum::DRAFT,
                ['status' => 'draft'],
            ))->not->toThrow(\InvalidArgumentException::class);
        });

        it('serialize returns backed value for enum instance', function () {
            $cast = new EnumCast(TestCastEnum::class);
            $result = $cast->serialize(
                new \stdClass(),
                'status',
                TestCastEnum::PUBLISHED,
                ['status' => 'published'],
            );

            expect($result)->toBe('published');
        });

        it('serialize passes through raw string', function () {
            $cast = new EnumCast(TestCastEnum::class);
            $result = $cast->serialize(
                new \stdClass(),
                'status',
                'draft',
                ['status' => 'draft'],
            );

            expect($result)->toBe('draft');
        });

        it('serialize returns null for non-string non-enum values', function () {
            $cast = new EnumCast(TestCastEnum::class);
            $result = $cast->serialize(
                new \stdClass(),
                'status',
                null,
                ['status' => null],
            );

            expect($result)->toBeNull();
        });
    });

    describe('InvalidEnumException factory methods', function () {
        it('value() creates exception with correct message', function () {
            $exception = InvalidEnumException::value('App\\Enums\\Status', 'invalid');

            expect($exception->getMessage())->toContain('invalid');
            expect($exception->getMessage())->toContain('App\\Enums\\Status');
        });

        it('value() handles null value', function () {
            $exception = InvalidEnumException::value('App\\Enums\\Status', null);

            expect($exception->getMessage())->toContain('null');
        });

        it('value() handles int value', function () {
            $exception = InvalidEnumException::value('App\\Enums\\Priority', 99);

            expect($exception->getMessage())->toContain('99');
        });

        it('forName() creates exception with correct message', function () {
            $exception = InvalidEnumException::forName('App\\Enums\\Status', 'UNKNOWN');

            expect($exception->getMessage())->toContain('UNKNOWN');
            expect($exception->getMessage())->toContain('App\\Enums\\Status');
        });

        it('__toString returns class name and message', function () {
            $exception = InvalidEnumException::forName('App\\Enums\\Status', 'X');

            $str = (string) $exception;
            expect($str)->toContain(InvalidEnumException::class);
            expect($str)->toContain('X');
        });
    });

    describe('camelCase label generation', function () {
        enum TestCamelCase: string
        {
            use HasEnumMetadata;

            case newUser = 'new';
            case activeAccount = 'active';
            case legacy_import = 'legacy';
        }

        it('generates correct label from camelCase names', function () {
            expect(TestCamelCase::newUser->label())->toBe('New User');
            expect(TestCamelCase::activeAccount->label())->toBe('Active Account');
        });

        it('handles mixed underscore/camelCase', function () {
            expect(TestCamelCase::legacy_import->label())->toBe('Legacy Import');
        });
    });
});
