<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Math\BigNumber;
use Cline\Math\Exception\RoundingNecessaryException;
use Cline\Math\RoundingMode;
use Cline\Money\Context\DefaultContext;
use Cline\Money\Currency;

test('apply to', function (string $amount, string $currency, RoundingMode $roundingMode, string $expected): void {
    $amount = BigNumber::of($amount);
    $currency = Currency::of($currency);

    $context = new DefaultContext();

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $context->applyTo($amount, $currency, $roundingMode);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertBigDecimalIs($expected, $actual);
})->with('providerApplyTo');
dataset('providerApplyTo', fn (): array => [
    ['1', 'USD', RoundingMode::Unnecessary, '1.00'],
    ['1.001', 'USD', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    ['1.001', 'USD', RoundingMode::Down, '1.00'],
    ['1.001', 'USD', RoundingMode::Up, '1.01'],
    ['1', 'JPY', RoundingMode::Unnecessary, '1'],
    ['1.00', 'JPY', RoundingMode::Unnecessary, '1'],
    ['1.01', 'JPY', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    ['1.01', 'JPY', RoundingMode::Down, '1'],
    ['1.01', 'JPY', RoundingMode::Up, '2'],
]);
test('get step', function (): void {
    $context = new DefaultContext();
    self::assertSame(1, $context->getStep());
});
