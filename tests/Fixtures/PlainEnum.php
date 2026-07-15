<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

/**
 * Plain enum without HasEnumMetadata trait — for error path testing.
 */
enum PlainEnum: string
{
    case FOO = 'foo';
    case BAR = 'bar';
}
