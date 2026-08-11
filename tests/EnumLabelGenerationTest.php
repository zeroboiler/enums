<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;

/**
 * Tests for auto-generated label behavior across various naming conventions.
 *
 * The HasEnumMetadata trait generates labels from case names when no
 * #[Label] or #[EnumLabel] attribute is present. This test verifies
 * the label generation algorithm handles all common PHP naming styles.
 */

// ─── Test Fixtures ───────────────────────────────────────────────────────────

namespace ZeroBoiler\Enums\Tests\EnumLabelGenerationTest;

enum ScreamingSnakeStatus: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING_REVIEW = 'pending_review';
    case ARCHIVED = 'archived';
}

enum CamelCaseFeature: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case darkMode = 'dark_mode';
    case pushNotifications = 'push_notifications';
    case twoFactorAuth = 'two_factor_auth';
}

enum PascalCaseRole: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case SuperAdmin = 'super_admin';
    case ContentManager = 'content_manager';
    case GuestUser = 'guest_user';
}

enum SingleChar: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case A = 'a';
    case B = 'b';
}

enum ShortName: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case OK = 'ok';
    case NO = 'no';
    case ID = 'id';
}

enum NumberInName: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case STATUS_2FA = 'status_2fa';
    case LEVEL_3 = 'level_3';
}

enum UnderscoreOnly: string
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case A_B = 'a_b';
    case _X = '_x';
}

enum PureEnumColor
{
    use \ZeroBoiler\Enums\Concerns\HasEnumMetadata;

    case RED;
    case DARK_BLUE;
    case LIGHT_GREEN;
}

namespace ZeroBoiler\Enums\Tests;

// ─── Test Suite ───────────────────────────────────────────────────────────────

describe('Auto-generated label from case names', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    describe('SCREAMING_SNAKE_CASE → Title Case', function () {
        it('converts ACTIVE to "Active"', function () {
            expect(ScreamingSnakeStatus::ACTIVE->label())->toBe('Active');
        });

        it('converts INACTIVE to "Inactive"', function () {
            expect(ScreamingSnakeStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('converts PENDING_REVIEW to "Pending Review"', function () {
            expect(ScreamingSnakeStatus::PENDING_REVIEW->label())->toBe('Pending Review');
        });

        it('converts ARCHIVED to "Archived"', function () {
            expect(ScreamingSnakeStatus::ARCHIVED->label())->toBe('Archived');
        });
    });

    describe('camelCase → Title Case', function () {
        it('converts darkMode to "Dark Mode"', function () {
            expect(CamelCaseFeature::darkMode->label())->toBe('Dark Mode');
        });

        it('converts pushNotifications to "Push Notifications"', function () {
            expect(CamelCaseFeature::pushNotifications->label())->toBe('Push Notifications');
        });

        it('converts twoFactorAuth to "Two Factor Auth"', function () {
            expect(CamelCaseFeature::twoFactorAuth->label())->toBe('Two Factor Auth');
        });
    });

    describe('PascalCase → Title Case', function () {
        it('converts SuperAdmin to "Super Admin"', function () {
            expect(PascalCaseRole::SuperAdmin->label())->toBe('Super Admin');
        });

        it('converts ContentManager to "Content Manager"', function () {
            expect(PascalCaseRole::ContentManager->label())->toBe('Content Manager');
        });

        it('converts GuestUser to "Guest User"', function () {
            expect(PascalCaseRole::GuestUser->label())->toBe('Guest User');
        });
    });

    describe('Edge cases', function () {
        it('handles single character case names', function () {
            expect(SingleChar::A->label())->toBe('A');
            expect(SingleChar::B->label())->toBe('B');
        });

        it('handles short uppercase names', function () {
            expect(ShortName::OK->label())->toBe('Ok');
            expect(ShortName::NO->label())->toBe('No');
            expect(ShortName::ID->label())->toBe('Id');
        });

        it('handles names with numbers', function () {
            expect(NumberInName::STATUS_2FA->label())->toBe('Status 2fa');
            expect(NumberInName::LEVEL_3->label())->toBe('Level 3');
        });

        it('handles underscore-separated short names', function () {
            expect(UnderscoreOnly::A_B->label())->toBe('A B');
        });

        it('handles name starting with underscore', function () {
            // Preg_replace on '_X' → ' X' → ucwords(trim(strtolower(' X'))) → 'X'
            expect(UnderscoreOnly::_X->label())->toBeString()->not->toBeEmpty();
        });
    });

    describe('Pure enum label generation', function () {
        it('generates labels for pure (non-backed) enums', function () {
            expect(PureEnumColor::RED->label())->toBe('Red');
            expect(PureEnumColor::DARK_BLUE->label())->toBe('Dark Blue');
            expect(PureEnumColor::LIGHT_GREEN->label())->toBe('Light Green');
        });

        it('values() returns case names for pure enums', function () {
            $values = PureEnumColor::values();

            expect($values)->toBe(['RED', 'DARK_BLUE', 'LIGHT_GREEN']);
        });

        it('forSelect() uses case names as values for pure enums', function () {
            $select = PureEnumColor::forSelect();

            expect($select)->toHaveCount(3);
            expect($select[0])->toBe(['value' => 'RED', 'label' => 'Red']);
            expect($select[1])->toBe(['value' => 'DARK_BLUE', 'label' => 'Dark Blue']);
            expect($select[2])->toBe(['value' => 'LIGHT_GREEN', 'label' => 'Light Green']);
        });
    });

    describe('Label consistency', function () {
        it('all labels are non-empty strings', function () {
            foreach (ScreamingSnakeStatus::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
            foreach (CamelCaseFeature::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
            foreach (PascalCaseRole::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });

        it('labels() returns same count as cases()', function () {
            expect(ScreamingSnakeStatus::labels())->toHaveCount(
                count(ScreamingSnakeStatus::cases())
            );
            expect(CamelCaseFeature::labels())->toHaveCount(
                count(CamelCaseFeature::cases())
            );
            expect(PascalCaseRole::labels())->toHaveCount(
                count(PascalCaseRole::cases())
            );
        });
    });
});
