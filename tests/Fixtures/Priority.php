<?php

declare(strict_types=1);

namespace NovaForge\Enums\Tests\Fixtures;

use NovaForge\Enums\Concerns\HasEnumMetadata;

/**
 * Int-backed enum test.
 */
enum Priority: int
{
    use HasEnumMetadata;

    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
    case URGENT = 4;
}
