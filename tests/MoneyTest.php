<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Math\BigDecimal;
use Cline\Math\BigInteger;
use Cline\Math\BigRational;
use Cline\Math\Exception\DivisionByZeroException;
use Cline\Math\Exception\NumberFormatException;
use Cline\Math\Exception\RoundingNecessaryException;
use Cline\Math\RoundingMode;
use Cline\Money\Context;
use Cline\Money\Context\AutoContext;
use Cline\Money\Context\CashContext;
use Cline\Money\Context\CustomContext;
use Cline\Money\Context\DefaultContext;
use Cline\Money\Currency;
use Cline\Money\Exception\MoneyMismatchException;
use Cline\Money\Money;

test('of', function (string $expectedResult, mixed ...$args): void {
    if (self::isExceptionClass($expectedResult)) {
        $this->expectException($expectedResult);
    }

    $money = Money::of(...$args);

    if (self::isExceptionClass($expectedResult)) {
        return;
    }

    self::assertMoneyIs($expectedResult, $money);
})->with('providerOf');
dataset('providerOf', fn (): array => [
    ['USD 1.00', 1, 'USD'],
    ['USD 5.60', '5.6', 840],
    ['JPY 1', '1.0', 'JPY'],
    ['JPY 1.200', '1.2', 'JPY', new CustomContext(3)],
    ['EUR 9.00', 9, 978],
    ['EUR 0.42', BigRational::of('3/7'), 'EUR', null, RoundingMode::Down],
    ['EUR 0.43', BigRational::of('3/7'), 'EUR', null, RoundingMode::Up],
    ['CUSTOM 0.428', BigRational::of('3/7'), new Currency('CUSTOM', 0, '', 3), null, RoundingMode::Down],
    ['CUSTOM 0.4286', BigRational::of('3/7'), new Currency('CUSTOM', 0, '', 3), new CustomContext(4, 1), RoundingMode::Up],
    [RoundingNecessaryException::class, '1.2', 'JPY'],
    [NumberFormatException::class, '1..', 'JPY'],
]);
test('of minor', function (string $expectedResult, mixed ...$args): void {
    if (self::isExceptionClass($expectedResult)) {
        $this->expectException($expectedResult);
    }

    $money = Money::ofMinor(...$args);

    if (self::isExceptionClass($expectedResult)) {
        return;
    }

    self::assertMoneyIs($expectedResult, $money);
})->with('providerOfMinor');
dataset('providerOfMinor', fn (): array => [
    ['EUR 0.01', 1, 'EUR'],
    ['USD 6.00', 600, 'USD'],
    ['JPY 600', 600, 'JPY'],
    ['USD 1.2350', '123.5', 'USD', new CustomContext(4)],
    [RoundingNecessaryException::class, '123.5', 'USD'],
    [NumberFormatException::class, '123..', 'USD'],
]);
test('zero', function (string $currency, ?Context $context, string $expected): void {
    $actual = Money::zero($currency, $context);
    self::assertMoneyIs($expected, $actual, $context ?? new DefaultContext());
})->with('providerZero');
dataset('providerZero', fn (): array => [
    ['USD', null, 'USD 0.00'],
    ['TND', null, 'TND 0.000'],
    ['JPY', null, 'JPY 0'],
    ['USD', new CustomContext(4), 'USD 0.0000'],
    ['USD', new AutoContext(), 'USD 0'],
]);
test('to', function (array $money, Context $context, RoundingMode $roundingMode, string $expected): void {
    $money = Money::of(...$money);

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $result = $money->to($context, $roundingMode);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertMoneyIs($expected, $result);
})->with('providerTo');
dataset('providerTo', fn (): array => [
    [['1.234', 'USD', new AutoContext()], new DefaultContext(), RoundingMode::Down, 'USD 1.23'],
    [['1.234', 'USD', new AutoContext()], new DefaultContext(), RoundingMode::Up, 'USD 1.24'],
    [['1.234', 'USD', new AutoContext()], new DefaultContext(), RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['1.234', 'USD', new AutoContext()], new CustomContext(2, 5), RoundingMode::Down, 'USD 1.20'],
    [['1.234', 'USD', new AutoContext()], new CustomContext(2, 5), RoundingMode::Up, 'USD 1.25'],
    [['1.234', 'USD', new AutoContext()], new AutoContext(), RoundingMode::Unnecessary, 'USD 1.234'],
    [['1.234', 'USD', new AutoContext()], new CustomContext(1, 1), RoundingMode::Down, 'USD 1.2'],
    [['1.234', 'USD', new AutoContext()], new CustomContext(1, 1), RoundingMode::Up, 'USD 1.3'],
    [['1.234', 'USD', new AutoContext()], new CustomContext(1, 2), RoundingMode::Down, 'USD 1.2'],
    [['1.234', 'USD', new AutoContext()], new CustomContext(1, 2), RoundingMode::Up, 'USD 1.4'],
]);
test('plus', function (array $money, mixed $plus, RoundingMode $roundingMode, string $expected): void {
    $money = Money::of(...$money);

    if (is_array($plus)) {
        $plus = Money::of(...$plus);
    }

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $money->plus($plus, $roundingMode);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertMoneyIs($expected, $actual);
})->with('providerPlus');
dataset('providerPlus', fn (): array => [
    [['12.34', 'USD'], 1, RoundingMode::Unnecessary, 'USD 13.34'],
    [['12.34', 'USD'], '1.23', RoundingMode::Unnecessary, 'USD 13.57'],
    [['12.34', 'USD'], '12.34', RoundingMode::Unnecessary, 'USD 24.68'],
    [['12.34', 'USD'], '0.001', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['12.340', 'USD', new AutoContext()], '0.001', RoundingMode::Unnecessary, 'USD 12.341'],
    [['12.34', 'USD'], '0.001', RoundingMode::Down, 'USD 12.34'],
    [['12.34', 'USD'], '0.001', RoundingMode::Up, 'USD 12.35'],
    [['1', 'JPY'], '2', RoundingMode::Unnecessary, 'JPY 3'],
    [['1', 'JPY'], '2.5', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['1.20', 'USD'], ['1.80', 'USD'], RoundingMode::Unnecessary, 'USD 3.00'],
    [['1.20', 'USD'], ['0.80', 'EUR'], RoundingMode::Unnecessary, MoneyMismatchException::class],
    [['1.23', 'USD', new AutoContext()], '0.01', RoundingMode::Up, InvalidArgumentException::class],
    [['123.00', 'CZK', new CashContext(100)], '12.50', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['123.00', 'CZK', new CashContext(100)], '12.50', RoundingMode::Down, 'CZK 135.00'],
    [['123.00', 'CZK', new CashContext(1)], '12.50', RoundingMode::Unnecessary, 'CZK 135.50'],
    [['12.25', 'CHF', new CustomContext(2, 25)], ['1.25', 'CHF', new CustomContext(2, 25)], RoundingMode::Unnecessary, 'CHF 13.50'],
]);
test('plus different context throws exception', function (): void {
    $a = Money::of(50, 'CHF', new CashContext(5));
    $b = Money::of(20, 'CHF');

    $this->expectException(MoneyMismatchException::class);
    $a->plus($b);
});
test('minus', function (array $money, mixed $minus, RoundingMode $roundingMode, string $expected): void {
    $money = Money::of(...$money);

    if (is_array($minus)) {
        $minus = Money::of(...$minus);
    }

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $money->minus($minus, $roundingMode);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertMoneyIs($expected, $actual);
})->with('providerMinus');
dataset('providerMinus', fn (): array => [
    [['12.34', 'USD'], 1, RoundingMode::Unnecessary, 'USD 11.34'],
    [['12.34', 'USD'], '1.23', RoundingMode::Unnecessary, 'USD 11.11'],
    [['12.34', 'USD'], '12.34', RoundingMode::Unnecessary, 'USD 0.00'],
    [['12.34', 'USD'], '0.001', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['12.340', 'USD', new AutoContext()], '0.001', RoundingMode::Unnecessary, 'USD 12.339'],
    [['12.34', 'USD'], '0.001', RoundingMode::Down, 'USD 12.33'],
    [['12.34', 'USD'], '0.001', RoundingMode::Up, 'USD 12.34'],
    [['1', 'EUR'], '2', RoundingMode::Unnecessary, 'EUR -1.00'],
    [['2', 'JPY'], '1.5', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['1.50', 'JPY', new AutoContext()], ['0.50', 'JPY', new AutoContext()], RoundingMode::Unnecessary, 'JPY 1'],
    [['2', 'JPY'], ['1', 'USD'], RoundingMode::Unnecessary, MoneyMismatchException::class],
]);
test('multiplied by', function (array $money, Money|int|float|string $multiplier, RoundingMode $roundingMode, string $expected): void {
    $money = Money::of(...$money);

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $money->multipliedBy($multiplier, $roundingMode);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertMoneyIs($expected, $actual);
})->with('providerMultipliedBy');
dataset('providerMultipliedBy', fn (): array => [
    [['12.34', 'USD'], 2, RoundingMode::Unnecessary, 'USD 24.68'],
    [['12.34', 'USD'], '1.5', RoundingMode::Unnecessary, 'USD 18.51'],
    [['12.34', 'USD'], '1.2', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['12.34', 'USD'], '1.2', RoundingMode::Down, 'USD 14.80'],
    [['12.34', 'USD'], '1.2', RoundingMode::Up, 'USD 14.81'],
    [['12.340', 'USD', new AutoContext()], '1.2', RoundingMode::Unnecessary, 'USD 14.808'],
    [['1', 'USD', new AutoContext()], '2', RoundingMode::Unnecessary, 'USD 2'],
    [['1.0', 'USD', new AutoContext()], '2', RoundingMode::Unnecessary, 'USD 2'],
    [['1', 'USD', new AutoContext()], '2.0', RoundingMode::Unnecessary, 'USD 2'],
    [['1.1', 'USD', new AutoContext()], '2.0', RoundingMode::Unnecessary, 'USD 2.2'],
]);
test('divided by', function (array $money, int|float|string $divisor, RoundingMode $roundingMode, string $expected): void {
    $money = Money::of(...$money);

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $money->dividedBy($divisor, $roundingMode);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertMoneyIs($expected, $actual);
})->with('providerDividedBy');
dataset('providerDividedBy', fn (): array => [
    [['12.34', 'USD'], 0, RoundingMode::Down, DivisionByZeroException::class],
    [['12.34', 'USD'], '2', RoundingMode::Unnecessary, 'USD 6.17'],
    [['10.28', 'USD'], '0.5', RoundingMode::Unnecessary, 'USD 20.56'],
    [['1.234', 'USD', new AutoContext()], '2.0', RoundingMode::Unnecessary, 'USD 0.617'],
    [['12.34', 'USD'], '20', RoundingMode::Down, 'USD 0.61'],
    [['12.34', 'USD'], 20, RoundingMode::Up, 'USD 0.62'],
    [['1.2345', 'USD', new CustomContext(4)], '2', RoundingMode::Ceiling, 'USD 0.6173'],
    [['1.2345', 'USD', new CustomContext(4)], 2, RoundingMode::Floor, 'USD 0.6172'],
    [['12.34', 'USD'], 20, RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['10.28', 'USD'], '8', RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['1.1', 'USD', new AutoContext()], 2, RoundingMode::Unnecessary, 'USD 0.55'],
    [['1.2', 'USD', new AutoContext()], 2, RoundingMode::Unnecessary, 'USD 0.6'],
]);
test('quotient and remainder', function (array $money, int $divisor, string $expectedQuotient, string $expectedRemainder): void {
    $money = Money::of(...$money);
    [$quotient, $remainder] = $money->quotientAndRemainder($divisor);

    self::assertMoneyIs($expectedQuotient, $quotient);
    self::assertMoneyIs($expectedRemainder, $remainder);
})->with('providerQuotientAndRemainder');
dataset('providerQuotientAndRemainder', fn (): array => [
    [['10', 'USD'], 3, 'USD 3.33', 'USD 0.01'],
    [['100', 'USD'], 9, 'USD 11.11', 'USD 0.01'],
    [['20', 'CHF', new CustomContext(2, 5)], 3, 'CHF 6.65', 'CHF 0.05'],
    [['50', 'CZK', new CustomContext(2, 100)], 3, 'CZK 16.00', 'CZK 2.00'],
]);
test('quotient and remainder throw exception on decimal', function (): void {
    $money = Money::of(50, 'USD');

    $this->expectException(RoundingNecessaryException::class);
    $money->quotientAndRemainder('1.1');
});
test('allocate', function (array $money, array $ratios, array $expected): void {
    $money = Money::of(...$money);
    $monies = $money->allocate(...$ratios);
    self::assertMoniesAre($expected, $monies);
})->with('providerAllocate');
dataset('providerAllocate', fn (): array => [
    [['99.99', 'USD'], [100], ['USD 99.99']],
    [['99.99', 'USD'], [100, 100], ['USD 50.00', 'USD 49.99']],
    [[100, 'USD'], [30, 20, 40], ['USD 33.34', 'USD 22.22', 'USD 44.44']],
    [[100, 'USD'], [30, 20, 40, 40], ['USD 23.08', 'USD 15.39', 'USD 30.77', 'USD 30.76']],
    [[100, 'USD'], [30, 20, 40, 0, 40, 0], ['USD 23.08', 'USD 15.39', 'USD 30.77', 'USD 0.00', 'USD 30.76', 'USD 0.00']],
    [[100, 'CHF', new CashContext(5)], [1, 2, 3, 7], ['CHF 7.70', 'CHF 15.40', 'CHF 23.10', 'CHF 53.80']],
    [['100.123', 'EUR', new AutoContext()], [2, 3, 1, 1], ['EUR 28.607', 'EUR 42.91', 'EUR 14.303', 'EUR 14.303']],
    [['0.02', 'EUR'], [1, 1, 1, 1], ['EUR 0.01', 'EUR 0.01', 'EUR 0.00', 'EUR 0.00']],
    [['0.02', 'EUR'], [1, 1, 3, 1], ['EUR 0.01', 'EUR 0.00', 'EUR 0.01', 'EUR 0.00']],
    [[-100, 'USD'], [30, 20, 40, 40], ['USD -23.08', 'USD -15.39', 'USD -30.77', 'USD -30.76']],
    [['0.03', 'GBP'], [75, 25], ['GBP 0.03', 'GBP 0.00']],
]);
test('allocate empty list', function (): void {
    $money = Money::of(50, 'USD');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot allocate() an empty list of ratios.');

    $money->allocate();
});
test('allocate negative ratios', function (): void {
    $money = Money::of(50, 'USD');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot allocate() negative ratios.');

    $money->allocate(1, 2, -1);
});
test('allocate zero ratios', function (): void {
    $money = Money::of(50, 'USD');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot allocate() to zero ratios only.');

    $money->allocate(0, 0, 0, 0, 0);
});
test('allocate with remainder', function (array $money, array $ratios, array $expected): void {
    $money = Money::of(...$money);
    $monies = $money->allocateWithRemainder(...$ratios);
    self::assertMoniesAre($expected, $monies);
})->with('providerAllocateWithRemainder');
dataset('providerAllocateWithRemainder', fn (): array => [
    [['99.99', 'USD'], [100], ['USD 99.99', 'USD 0.00']],
    [['99.99', 'USD'], [100, 100], ['USD 49.99', 'USD 49.99', 'USD 0.01']],
    [[100, 'USD'], [30, 20, 40], ['USD 33.33', 'USD 22.22', 'USD 44.44', 'USD 0.01']],
    [[100, 'USD'], [30, 20, 40, 40], ['USD 23.07', 'USD 15.38', 'USD 30.76', 'USD 30.76', 'USD 0.03']],
    [[100, 'USD'], [30, 20, 40, 0, 0, 40], ['USD 23.07', 'USD 15.38', 'USD 30.76', 'USD 0.00', 'USD 0.00', 'USD 30.76', 'USD 0.03']],
    [[100, 'CHF', new CashContext(5)], [1, 2, 3, 7], ['CHF 7.65', 'CHF 15.30', 'CHF 22.95', 'CHF 53.55', 'CHF 0.55']],
    [['100.123', 'EUR', new AutoContext()], [2, 3, 1, 1], ['EUR 28.606', 'EUR 42.909', 'EUR 14.303', 'EUR 14.303', 'EUR 0.002']],
    [['0.02', 'EUR'], [1, 1, 1, 1], ['EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.02']],
    [['0.02', 'EUR'], [1, 1, 3, 1], ['EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.02']],
    [[-100, 'USD'], [30, 20, 40, 40], ['USD -23.07', 'USD -15.38', 'USD -30.76', 'USD -30.76', 'USD -0.03']],
    [['0.03', 'GBP'], [75, 25], ['GBP 0.00', 'GBP 0.00', 'GBP 0.03']],
]);
test('allocate with remainder empty list', function (): void {
    $money = Money::of(50, 'USD');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot allocateWithRemainder() an empty list of ratios.');

    $money->allocateWithRemainder();
});
test('allocate with remainder negative ratios', function (): void {
    $money = Money::of(50, 'USD');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot allocateWithRemainder() negative ratios.');

    $money->allocateWithRemainder(1, 2, -1);
});
test('allocate with remainder zero ratios', function (): void {
    $money = Money::of(50, 'USD');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot allocateWithRemainder() to zero ratios only.');

    $money->allocateWithRemainder(0, 0, 0, 0, 0);
});
test('split', function (array $money, int $targets, array $expected): void {
    $money = Money::of(...$money);
    $monies = $money->split($targets);
    self::assertMoniesAre($expected, $monies);
})->with('providerSplit');
dataset('providerSplit', fn (): array => [
    [['99.99', 'USD'], 1, ['USD 99.99']],
    [['99.99', 'USD'], 2, ['USD 50.00', 'USD 49.99']],
    [['99.99', 'USD'], 3, ['USD 33.33', 'USD 33.33', 'USD 33.33']],
    [['99.99', 'USD'], 4, ['USD 25.00', 'USD 25.00', 'USD 25.00', 'USD 24.99']],
    [[100, 'CHF', new CashContext(5)], 3, ['CHF 33.35', 'CHF 33.35', 'CHF 33.30']],
    [[100, 'CHF', new CashContext(5)], 7, ['CHF 14.30', 'CHF 14.30', 'CHF 14.30', 'CHF 14.30', 'CHF 14.30', 'CHF 14.25', 'CHF 14.25']],
    [['100.123', 'EUR', new AutoContext()], 4, ['EUR 25.031', 'EUR 25.031', 'EUR 25.031', 'EUR 25.030']],
    [['0.02', 'EUR'], 4, ['EUR 0.01', 'EUR 0.01', 'EUR 0.00', 'EUR 0.00']],
]);
test('split with remainder', function (array $money, int $targets, array $expected): void {
    $money = Money::of(...$money);
    $monies = $money->splitWithRemainder($targets);
    self::assertMoniesAre($expected, $monies);
})->with('providerSplitWithRemainder');
dataset('providerSplitWithRemainder', fn (): array => [
    [['99.99', 'USD'], 1, ['USD 99.99', 'USD 0.00']],
    [['99.99', 'USD'], 2, ['USD 49.99', 'USD 49.99', 'USD 0.01']],
    [['99.99', 'USD'], 3, ['USD 33.33', 'USD 33.33', 'USD 33.33', 'USD 0.00']],
    [['99.99', 'USD'], 4, ['USD 24.99', 'USD 24.99', 'USD 24.99', 'USD 24.99', 'USD 0.03']],
    [[100, 'CHF', new CashContext(5)], 3, ['CHF 33.30', 'CHF 33.30', 'CHF 33.30', 'CHF 0.10']],
    [[100, 'CHF', new CashContext(5)], 7, ['CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 0.25']],
    [['100.123', 'EUR', new AutoContext()], 4, ['EUR 25.03', 'EUR 25.03', 'EUR 25.03', 'EUR 25.03', 'EUR 0.003']],
    [['0.02', 'EUR'], 4, ['EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.02']],
]);
test('split into less than one part', function (int $parts): void {
    $money = Money::of(50, 'USD');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot split() into less than 1 part.');

    $money->split($parts);
})->with('providerSplitIntoLessThanOnePart');
dataset('providerSplitIntoLessThanOnePart', fn (): array => [
    [-1],
    [0],
]);
test('split with remainder into less than one part', function (int $parts): void {
    $money = Money::of(50, 'USD');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot splitWithRemainder() into less than 1 part.');

    $money->splitWithRemainder($parts);
})->with('providerSplitWithRemainderIntoLessThanOnePart');
dataset('providerSplitWithRemainderIntoLessThanOnePart', fn (): array => [
    [-1],
    [0],
]);
test('abs', function (array $money, string $abs): void {
    self::assertMoneyIs($abs, Money::of(...$money)->abs());
})->with('providerAbs');
dataset('providerAbs', fn (): array => [
    [['-1', 'EUR'], 'EUR 1.00'],
    [['-1', 'EUR', new AutoContext()], 'EUR 1'],
    [['1.2', 'JPY', new AutoContext()], 'JPY 1.2'],
]);
test('negated', function (array $money, string $negated): void {
    self::assertMoneyIs($negated, Money::of(...$money)->negated());
})->with('providerNegated');
dataset('providerNegated', fn (): array => [
    [['1.234', 'EUR', new AutoContext()], 'EUR -1.234'],
    [['-2', 'JPY'], 'JPY 2'],
]);
test('get sign', function (array $money, int $sign): void {
    self::assertSame($sign, Money::of(...$money)->getSign());
})->with('providerSign');
test('is zero', function (array $money, int $sign): void {
    self::assertSame($sign === 0, Money::of(...$money)->isZero());
})->with('providerSign');
test('is positive', function (array $money, int $sign): void {
    self::assertSame($sign > 0, Money::of(...$money)->isPositive());
})->with('providerSign');
test('is positive or zero', function (array $money, int $sign): void {
    self::assertSame($sign >= 0, Money::of(...$money)->isPositiveOrZero());
})->with('providerSign');
test('is negative', function (array $money, int $sign): void {
    self::assertSame($sign < 0, Money::of(...$money)->isNegative());
})->with('providerSign');
test('is negative or zero', function (array $money, int $sign): void {
    self::assertSame($sign <= 0, Money::of(...$money)->isNegativeOrZero());
})->with('providerSign');
dataset('providerSign', fn (): array => [
    [['-0.001', 'USD', new AutoContext()], -1],
    [['-0.01', 'USD'], -1],
    [['-0.1', 'USD', new AutoContext()], -1],
    [['-1', 'USD', new AutoContext()], -1],
    [['-1.0', 'USD', new AutoContext()], -1],
    [['-0', 'USD', new AutoContext()], 0],
    [['-0.0', 'USD', new AutoContext()], 0],
    [['0', 'USD', new AutoContext()], 0],
    [['0.0', 'USD', new AutoContext()], 0],
    [['0.00', 'USD'], 0],
    [['0.000', 'USD', new AutoContext()], 0],
    [['0.001', 'USD', new AutoContext()], 1],
    [['0.01', 'USD'], 1],
    [['0.1', 'USD', new AutoContext()], 1],
    [['1', 'USD', new AutoContext()], 1],
    [['1.0', 'USD', new AutoContext()], 1],
]);
test('compare to', function (array $a, array $b, int $c): void {
    self::assertSame($c, Money::of(...$a)->compareTo(Money::of(...$b)));
})->with('providerCompare');
test('compare to other currency', function (): void {
    $this->expectException(MoneyMismatchException::class);
    Money::of('1.00', 'EUR')->compareTo(Money::of('1.00', 'USD'));
});
test('is equal to', function (array $a, array $b, int $c): void {
    self::assertSame($c === 0, Money::of(...$a)->isEqualTo(Money::of(...$b)));
})->with('providerCompare');
test('is equal to other currency', function (): void {
    $this->expectException(MoneyMismatchException::class);
    Money::of('1.00', 'EUR')->isEqualTo(Money::of('1.00', 'USD'));
});
test('is less than', function (array $a, array $b, int $c): void {
    self::assertSame($c < 0, Money::of(...$a)->isLessThan(Money::of(...$b)));
})->with('providerCompare');
test('is less than other currency', function (): void {
    $this->expectException(MoneyMismatchException::class);
    Money::of('1.00', 'EUR')->isLessThan(Money::of('1.00', 'USD'));
});
test('is less than or equal to', function (array $a, array $b, int $c): void {
    self::assertSame($c <= 0, Money::of(...$a)->isLessThanOrEqualTo(Money::of(...$b)));
})->with('providerCompare');
test('is less than or equal to other currency', function (): void {
    $this->expectException(MoneyMismatchException::class);
    Money::of('1.00', 'EUR')->isLessThanOrEqualTo(Money::of('1.00', 'USD'));
});
test('is greater than', function (array $a, array $b, int $c): void {
    self::assertSame($c > 0, Money::of(...$a)->isGreaterThan(Money::of(...$b)));
})->with('providerCompare');
test('is greater than other currency', function (): void {
    $this->expectException(MoneyMismatchException::class);
    Money::of('1.00', 'EUR')->isGreaterThan(Money::of('1.00', 'USD'));
});
test('is greater than or equal to', function (array $a, array $b, int $c): void {
    self::assertSame($c >= 0, Money::of(...$a)->isGreaterThanOrEqualTo(Money::of(...$b)));
})->with('providerCompare');
test('is greater than or equal to other currency', function (): void {
    $this->expectException(MoneyMismatchException::class);
    Money::of('1.00', 'EUR')->isGreaterThanOrEqualTo(Money::of('1.00', 'USD'));
});
test('is amount and currency equal to', function (array $a, array $b, bool $c): void {
    self::assertSame($c, Money::of(...$a)->isAmountAndCurrencyEqualTo(Money::of(...$b)));
})->with('providerIsAmountAndCurrencyEqualTo');
dataset('providerIsAmountAndCurrencyEqualTo', function () {
    foreach ([
        [['1', 'EUR', new AutoContext()], ['1.00', 'EUR'], 0],
        [['1', 'USD', new AutoContext()], ['0.999999', 'USD', new AutoContext()], 1],
        [['0.999999', 'USD', new AutoContext()], ['1', 'USD', new AutoContext()], -1],
        [['-0.00000001', 'USD', new AutoContext()], ['0', 'USD', new AutoContext()], -1],
        [['-0.00000001', 'USD', new AutoContext()], ['-0.00000002', 'USD', new AutoContext()], 1],
        [['-2', 'JPY'], ['-2.000', 'JPY', new AutoContext()], 0],
        [['-2', 'JPY'], ['2', 'JPY'], -1],
        [['2.0', 'CAD', new AutoContext()], ['-0.01', 'CAD'], 1],
    ] as [$a, $b, $c]) {
        yield [$a, $b, $c === 0];
    }

    yield [[1, 'EUR'], [1, 'USD'], false];
});
dataset('providerCompare', fn (): array => [
    [['1', 'EUR', new AutoContext()], ['1.00', 'EUR'], 0],
    [['1', 'USD', new AutoContext()], ['0.999999', 'USD', new AutoContext()], 1],
    [['0.999999', 'USD', new AutoContext()], ['1', 'USD', new AutoContext()], -1],
    [['-0.00000001', 'USD', new AutoContext()], ['0', 'USD', new AutoContext()], -1],
    [['-0.00000001', 'USD', new AutoContext()], ['-0.00000002', 'USD', new AutoContext()], 1],
    [['-2', 'JPY'], ['-2.000', 'JPY', new AutoContext()], 0],
    [['-2', 'JPY'], ['2', 'JPY'], -1],
    [['2.0', 'CAD', new AutoContext()], ['-0.01', 'CAD'], 1],
]);
test('get minor amount', function (array $money, string $expected): void {
    $actual = Money::of(...$money)->getMinorAmount();

    self::assertInstanceOf(BigDecimal::class, $actual);
    self::assertSame($expected, (string) $actual);
})->with('providerGetMinorAmount');
dataset('providerGetMinorAmount', fn (): array => [
    [[50, 'USD'], '5000'],
    [['1.23', 'USD'], '123'],
    [['1.2345', 'USD', new AutoContext()], '123.45'],
    [[50, 'JPY'], '50'],
    [['1.123', 'JPY', new AutoContext()], '1.123'],
]);
test('get unscaled amount', function (): void {
    $actual = Money::of('123.45', 'USD')->getUnscaledAmount();

    self::assertInstanceOf(BigInteger::class, $actual);
    self::assertSame('12345', (string) $actual);
});
dataset('providerFormatTo', fn (): array => [
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
test('format to locale', function (array $money, string $locale, bool $allowWholeNumber, string $expected): void {
    $actual = Money::of(...$money)->formatToLocale($locale, $allowWholeNumber);
    self::assertSame(
        str_replace("\u{00A0}", ' ', $expected),
        str_replace("\u{00A0}", ' ', $actual),
    );
})->with('providerFormatTo');
test('format exact', function (array $money, string $separator, string $expected): void {
    self::assertSame($expected, Money::of(...$money)->formatExact($separator));
})->with([
    [['1.23', 'USD'], ' ', 'USD 1.23'],
    [['12345678901234567890.12', 'EUR'], ' ', 'EUR 12345678901234567890.12'],
    [['12345678901234567890.123456789', 'EUR', new AutoContext()], ':', 'EUR:12345678901234567890.123456789'],
]);
test('to rational', function (): void {
    $money = Money::of('12.3456', 'EUR', new AutoContext());
    $monies = $money->getMonies();

    self::assertCount(1, $monies);
    self::assertRationalMoneyEquals('EUR 7716/625', $monies[0]);
});
test('min', function (array $monies, string $expectedResult): void {
    $monies = array_map(
        fn (array $money): Money => Money::of(...$money),
        $monies,
    );

    if (self::isExceptionClass($expectedResult)) {
        $this->expectException($expectedResult);
    }

    $actualResult = Money::min(...$monies);

    if (self::isExceptionClass($expectedResult)) {
        return;
    }

    self::assertMoneyIs($expectedResult, $actualResult);
})->with('providerMin');
dataset('providerMin', fn (): array => [
    [[['1.0', 'USD', new AutoContext()], ['3.50', 'USD'], ['4.00', 'USD']], 'USD 1'],
    [[['5.00', 'USD'], ['3.50', 'USD'], ['4.00', 'USD']], 'USD 3.50'],
    [[['5.00', 'USD'], ['3.50', 'USD'], ['3.499', 'USD', new AutoContext()]], 'USD 3.499'],
    [[['1.00', 'USD'], ['1.00', 'EUR']], MoneyMismatchException::class],
]);
test('max', function (array $monies, string $expectedResult): void {
    $monies = array_map(
        fn (array $money): Money => Money::of(...$money),
        $monies,
    );

    if (self::isExceptionClass($expectedResult)) {
        $this->expectException($expectedResult);
    }

    $actualResult = Money::max(...$monies);

    if (self::isExceptionClass($expectedResult)) {
        return;
    }

    self::assertMoneyIs($expectedResult, $actualResult);
})->with('providerMax');
dataset('providerMax', fn (): array => [
    [[['5.50', 'USD'], ['3.50', 'USD'], ['4.90', 'USD']], 'USD 5.50'],
    [[['1.3', 'USD', new AutoContext()], ['3.50', 'USD'], ['4.90', 'USD']], 'USD 4.90'],
    [[['1.3', 'USD', new AutoContext()], ['7.119', 'USD', new AutoContext()], ['4.90', 'USD']], 'USD 7.119'],
    [[['1.00', 'USD'], ['1.00', 'EUR']], MoneyMismatchException::class],
]);
test('total', function (): void {
    $total = Money::total(
        Money::of('5.50', 'USD'),
        Money::of('3.50', 'USD'),
        Money::of('4.90', 'USD'),
    );

    self::assertMoneyEquals('13.90', 'USD', $total);
});
test('total of different currencies throws exception', function (): void {
    $this->expectException(MoneyMismatchException::class);

    Money::total(
        Money::of('1.00', 'EUR'),
        Money::of('1.00', 'USD'),
    );
});
test('json serialize', function (Money $money, array $expected): void {
    self::assertSame($expected, $money->jsonSerialize());
    self::assertSame(json_encode($expected), json_encode($money));
})->with('providerJsonSerialize');
dataset('providerJsonSerialize', fn (): array => [
    [Money::of('3.5', 'EUR'), ['amount' => '3.50', 'currency' => 'EUR', 'context' => ['type' => 'default']]],
    [Money::of('3.888923', 'GBP', new CustomContext(8)), ['amount' => '3.88892300', 'currency' => 'GBP', 'context' => ['type' => 'custom', 'scale' => 8, 'step' => 1]]],
    [Money::of('10', 'CHF', new CashContext(5)), ['amount' => '10.00', 'currency' => 'CHF', 'context' => ['type' => 'cash', 'step' => 5]]],
    [Money::of('1.2300', 'USD', new AutoContext()), ['amount' => '1.23', 'currency' => 'USD', 'context' => ['type' => 'auto']]],
]);
