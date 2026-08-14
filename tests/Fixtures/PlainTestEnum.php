<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

/**
 * Plain enum without HasEnumMetadata trait.
 *
 * Used to test that EnumManager correctly rejects enums
 * that do not use the HasEnumMetadata trait.
 */
enum PlainTestEnum
{
    case A;
    case B;
}
