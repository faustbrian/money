<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Money\Context\AutoContext;
use Cline\Money\Formatter\MoneyExactFormatter;
use Cline\Money\Money;

test('format preserves exact amount digits', function (array $money, string $separator, string $expected): void {
    $formatter = new MoneyExactFormatter($separator);

    self::assertSame($expected, $formatter->format(Money::of(...$money)));
})->with([
    [['1.23', 'USD'], ' ', 'USD 1.23'],
    [['12345678901234567890.12', 'EUR'], ' ', 'EUR 12345678901234567890.12'],
    [['12345678901234567890.123456789', 'EUR', new AutoContext()], ':', 'EUR:12345678901234567890.123456789'],
]);
