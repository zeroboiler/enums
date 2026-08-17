<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Enum toValue() normalization and cross-type consistency tests.
 *
 * Validates that toValue() returns the correct representation for all three
 * PHP enum types (string-backed, int-backed, pure) and that the return types
 * are consistent with PHPStan Level 9 expectations.
 *
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::toValue()
 */

use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('toValue() cross-type normalization', function (): void {
    it('returns backed string value for string-backed enum', function (): void {
        expect(UserStatus::ACTIVE->toValue())->toBe('active');
        expect(UserStatus::BANNED->toValue())->toBe('banned');
    });

    it('returns backed int value for int-backed enum', function (): void {
        $firstCase = IntBackedPriority::cases()[0];
        expect($firstCase->toValue())->toBeInt();
        expect(is_int($firstCase->toValue()))->toBeTrue();
    });

    it('returns case name for pure enum', function (): void {
        $firstCase = PureFeatureFlag::cases()[0];
        expect($firstCase->toValue())->toBe($firstCase->name);
        expect(is_string($firstCase->toValue()))->toBeTrue();
    });

    it('toValue() is consistent with forSelect value key', function (): void {
        foreach (UserStatus::cases() as $case) {
            $select = UserStatus::forSelect();
            $matched = false;
            foreach ($select as $option) {
                if ($option['value'] === $case->toValue()) {
                    $matched = true;
                    break;
                }
            }
            expect($matched)->toBeTrue("Case {$case->name} toValue() not found in forSelect()");
        }
    });

    it('toValue() is consistent with values() output', function (): void {
        $values = UserStatus::values();
        foreach (UserStatus::cases() as $case) {
            expect($values)->toContain($case->toValue());
        }
    });

    it('toValue() returns string for string-backed, int for int-backed, string for pure', function (): void {
        foreach (UserStatus::cases() as $case) {
            expect(is_string($case->toValue()))->toBeTrue();
        }

        foreach (IntBackedPriority::cases() as $case) {
            expect(is_int($case->toValue()))->toBeTrue();
        }

        foreach (PureFeatureFlag::cases() as $case) {
            expect(is_string($case->toValue()))->toBeTrue();
        }
    });
});

describe('forApi() structural integrity', function (): void {
    it('returns correct number of entries matching cases count', function (): void {
        $api = UserStatus::forApi();
        expect($api)->toHaveCount(count(UserStatus::cases()));
    });

    it('every entry has all required keys', function (): void {
        $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];
        foreach (UserStatus::forApi() as $entry) {
            foreach ($requiredKeys as $key) {
                expect($entry)->toHaveKey($key);
            }
        }
    });

    it('color is always a non-empty string', function (): void {
        foreach (UserStatus::forApi() as $entry) {
            expect($entry['color'])->toBeString();
            expect($entry['color'])->not->toBeEmpty();
        }
    });

    it('label is always a non-empty string', function (): void {
        foreach (UserStatus::forApi() as $entry) {
            expect($entry['label'])->toBeString();
            expect($entry['label'])->not->toBeEmpty();
        }
    });

    it('name matches the enum case name exactly', function (): void {
        foreach (UserStatus::cases() as $case) {
            $api = UserStatus::forApi();
            $matched = false;
            foreach ($api as $entry) {
                if ($entry['name'] === $case->name) {
                    $matched = true;
                    break;
                }
            }
            expect($matched)->toBeTrue();
        }
    });

    it('forApi() values match toValue() for all cases', function (): void {
        $api = UserStatus::forApi();
        $apiMap = [];
        foreach ($api as $entry) {
            $apiMap[$entry['name']] = $entry['value'];
        }

        foreach (UserStatus::cases() as $case) {
            expect($apiMap[$case->name])->toBe($case->toValue());
        }
    });
});

describe('hasCase() edge cases', function (): void {
    it('returns true for valid case name', function (): void {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('BANNED'))->toBeTrue();
    });

    it('returns false for invalid case name', function (): void {
        expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
        expect(UserStatus::hasCase(''))->toBeFalse();
        expect(UserStatus::hasCase('active'))->toBeFalse(); // value, not name
    });

    it('is case-sensitive', function (): void {
        expect(UserStatus::hasCase('active'))->toBeFalse();
        expect(UserStatus::hasCase('Active'))->toBeFalse();
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
    });
});

describe('in() and notIn() edge cases', function (): void {
    it('in() returns false for empty array', function (): void {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('notIn() returns true for empty array', function (): void {
        expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
    });

    it('in() works with single-element array', function (): void {
        expect(UserStatus::ACTIVE->in(['ACTIVE']))->toBeTrue();
        expect(UserStatus::ACTIVE->in(['BANNED']))->toBeFalse();
    });

    it('in() works with all cases', function (): void {
        $allNames = array_map(fn ($c) => $c->name, UserStatus::cases());
        foreach (UserStatus::cases() as $case) {
            expect($case->in($allNames))->toBeTrue();
        }
    });

    it('notIn() returns false when case is the only element', function (): void {
        expect(UserStatus::ACTIVE->notIn(['ACTIVE']))->toBeFalse();
    });
});

describe('tryFromLabel() edge cases', function (): void {
    it('returns null for empty string', function (): void {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });

    it('is truly case-insensitive', function (): void {
        $label = UserStatus::ACTIVE->label();
        expect(UserStatus::tryFromLabel(strtoupper($label)))->not->toBeNull();
        expect(UserStatus::tryFromLabel(strtolower($label)))->not->toBeNull();
        expect(UserStatus::tryFromLabel(ucfirst($label)))->not->toBeNull();
    });

    it('returns the correct case for duplicate labels', function (): void {
        // If two cases happen to have the same auto-generated label,
        // tryFromLabel returns the first match
        $cases = UserStatus::cases();
        if (count($cases) >= 2) {
            $result = UserStatus::tryFromLabel($cases[0]->label());
            expect($result)->not->toBeNull();
            expect($result->name)->toBe($cases[0]->name);
        }
    });
});

describe('labels() consistency', function (): void {
    it('returns same count as cases', function (): void {
        expect(UserStatus::labels())->toHaveCount(count(UserStatus::cases()));
    });

    it('returns same values as individual label() calls', function (): void {
        $labels = UserStatus::labels();
        $cases = UserStatus::cases();
        for ($i = 0; $i < count($cases); $i++) {
            expect($labels[$i])->toBe($cases[$i]->label());
        }
    });

    it('all labels are non-empty strings', function (): void {
        foreach (UserStatus::labels() as $label) {
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        }
    });
});

describe('values() uniqueness for backed enums', function (): void {
    it('string-backed enum values are unique', function (): void {
        $values = UserStatus::values();
        expect($values)->toEqual(array_unique($values));
    });

    it('int-backed enum values are unique', function (): void {
        $values = IntBackedPriority::values();
        expect($values)->toEqual(array_unique($values));
    });

    it('pure enum values (case names) are unique', function (): void {
        $values = PureFeatureFlag::values();
        expect($values)->toEqual(array_unique($values));
    });
});
