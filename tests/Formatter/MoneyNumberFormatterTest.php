<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Money\Context\AutoContext;
use Cline\Money\Formatter\MoneyNumberFormatter;
use Cline\Money\Money;

test('format', function (array $money, string $locale, string $symbol, string $expected): void {
    $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    $numberFormatter->setSymbol(NumberFormatter::MONETARY_SEPARATOR_SYMBOL, $symbol);

    $formatter = new MoneyNumberFormatter($numberFormatter);

    $actual = $formatter->format(Money::of(...$money));
    self::assertSame(
        str_replace("\u{00A0}", ' ', $expected),
        str_replace("\u{00A0}", ' ', $actual),
    );
})->with('providerFormat');
dataset('providerFormat', fn (): array => [
    [['1.23', 'USD'], 'en_US', ';', '$1;23'],
    [['1.7', 'EUR', new AutoContext()], 'fr_FR', '~', '1~70 €'],
]);
