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
use Cline\Money\Context\CashContext;
use Cline\Money\Currency;
use Cline\Money\Exception\MoneyException;

test('apply to', function (int $step, string $amount, string $currency, RoundingMode $roundingMode, string $expected): void {
    $amount = BigNumber::of($amount);
    $currency = Currency::of($currency);

    $context = new CashContext($step);

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
    [1, '1', 'USD', RoundingMode::Unnecessary, '1.00'],
    [1, '1.001', 'USD', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [1, '1.001', 'USD', RoundingMode::Down, '1.00'],
    [1, '1.001', 'USD', RoundingMode::Up, '1.01'],
    [1, '1', 'JPY', RoundingMode::Unnecessary, '1'],
    [1, '1.00', 'JPY', RoundingMode::Unnecessary, '1'],
    [1, '1.01', 'JPY', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [1, '1.01', 'JPY', RoundingMode::Down, '1'],
    [1, '1.01', 'JPY', RoundingMode::Up, '2'],
    [5, '1', 'CHF', RoundingMode::Unnecessary, '1.00'],
    [5, '1.05', 'CHF', RoundingMode::Unnecessary, '1.05'],
    [5, '1.07', 'CHF', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [5, '1.07', 'CHF', RoundingMode::Down, '1.05'],
    [5, '1.07', 'CHF', RoundingMode::Up, '1.10'],
    [5, '1.075', 'CHF', RoundingMode::HalfDown, '1.05'],
    [5, '1.075', 'CHF', RoundingMode::HalfUp, '1.10'],
    [100, '-1', 'CZK', RoundingMode::Unnecessary, '-1.00'],
    [100, '-1.00', 'CZK', RoundingMode::Unnecessary, '-1.00'],
    [100, '-1.5', 'CZK', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [100, '-1.5', 'CZK', RoundingMode::Down, '-1.00'],
    [100, '-1.5', 'CZK', RoundingMode::Up, '-2.00'],
]);
test('get step', function (): void {
    $context = new CashContext(5);
    self::assertSame(5, $context->getStep());
});

test('constructor throws exception for invalid step', function (int $step): void {
    $this->expectException(MoneyException::class);
    $this->expectExceptionMessage('Invalid step: '.$step.'.');

    new CashContext($step);
})->with([
    0,
    -5,
    3,
    6,
    14,
]);
