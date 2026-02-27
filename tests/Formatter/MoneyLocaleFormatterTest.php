<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Money\Context\CustomContext;
use Cline\Money\Formatter\MoneyLocaleFormatter;
use Cline\Money\Money;

test('format to', function (array $money, string $locale, bool $allowWholeNumber, string $expected): void {
    $formatter = new MoneyLocaleFormatter($locale, $allowWholeNumber);
    $actual = $formatter->format(Money::of(...$money));

    self::assertSame(
        str_replace("\u{00A0}", ' ', $expected),
        str_replace("\u{00A0}", ' ', $actual),
    );
})->with('providerFormat');
dataset('providerFormat', fn (): array => [
    [['1.23', 'USD'], 'en_US', false, '$1.23'],
    [['1.23', 'USD'], 'fr_FR', false, '1,23 $US'],
    [['1.23', 'EUR'], 'fr_FR', false, '1,23 €'],
    [['1.234', 'EUR', new CustomContext(3)], 'fr_FR', false, '1,234 €'],
    [['234.0', 'EUR', new CustomContext(1)], 'fr_FR', false, '234,0 €'],
    [['234.0', 'EUR', new CustomContext(1)], 'fr_FR', true, '234 €'],
    [['234.00', 'GBP'], 'en_GB', false, '£234.00'],
    [['234.00', 'GBP'], 'en_GB', true, '£234'],
    [['234.000', 'EUR', new CustomContext(3)], 'fr_FR', false, '234,000 €'],
    [['234.000', 'EUR', new CustomContext(3)], 'fr_FR', true, '234 €'],
    [['234.001', 'GBP', new CustomContext(3)], 'en_GB', false, '£234.001'],
    [['234.001', 'GBP', new CustomContext(3)], 'en_GB', true, '£234.001'],
]);
