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
use Cline\Money\Context\AutoContext;
use Cline\Money\Context\CashContext;
use Cline\Money\Currency;

test('apply to', function (string $amount, string $currency, RoundingMode $roundingMode, string $expected): void {
    $amount = BigNumber::of($amount);
    $currency = Currency::of($currency);

    $context = new AutoContext();

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
    ['1', 'USD', RoundingMode::Unnecessary, '1'],
    ['1.23', 'JPY', RoundingMode::Unnecessary, '1.23'],
    ['123/5000', 'EUR', RoundingMode::Unnecessary, '0.0246'],
    ['5/7', 'EUR', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    ['5/7', 'EUR', RoundingMode::Down, InvalidArgumentException::class],
]);
test('get step', function (): void {
    $context = new CashContext(5);
    self::assertSame(5, $context->getStep());
});
