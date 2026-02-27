<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Math\BigRational;
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
use Cline\Money\RationalMoney;

test('zero', function (string $currencyCode, string $expected): void {
    self::assertRationalMoneyEquals($expected, RationalMoney::zero($currencyCode));
})->with('providerZero');
dataset('providerZero', fn (): array => [
    ['USD', 'USD 0'],
    ['EUR', 'EUR 0'],
]);
test('getters', function (): void {
    $amount = BigRational::of('123/456');
    $currency = Currency::of('EUR');

    $money = new RationalMoney($amount, $currency);

    self::assertSame($amount, $money->getAmount());
    self::assertSame($currency, $money->getCurrency());
});
test('plus', function (array $rationalMoney, mixed $amount, string $expected): void {
    $rationalMoney = RationalMoney::of(...$rationalMoney);

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $rationalMoney->plus($amount);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertRationalMoneyEquals($expected, $actual);
})->with('providerPlus');
dataset('providerPlus', fn (): array => [
    [['1.1234', 'USD'], '987.65', 'USD 4943867/5000'],
    [['123/456', 'GBP'], '14.99', 'GBP 57987/3800'],
    [['123/456', 'GBP'], '567/890', 'GBP 61337/67640'],
    [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 1223/1000'],
    [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
    [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 3219/250'],
    [['9.876', 'CAD'], Money::of(3, 'USD'), MoneyMismatchException::class],
]);
test('minus', function (array $rationalMoney, mixed $amount, string $expected): void {
    $rationalMoney = RationalMoney::of(...$rationalMoney);

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $rationalMoney->minus($amount);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertRationalMoneyEquals($expected, $actual);
})->with('providerMinus');
dataset('providerMinus', fn (): array => [
    [['987.65', 'USD'], '1.1234', 'USD 4932633/5000'],
    [['123/456', 'GBP'], '14.99', 'GBP -55937/3800'],
    [['123/456', 'GBP'], '567/890', 'GBP -24847/67640'],
    [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 1023/1000'],
    [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
    [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 1719/250'],
    [['9.876', 'CAD'], Money::of(3, 'USD'), MoneyMismatchException::class],
]);
test('multiplied by', function (array $rationalMoney, mixed $operand, string $expected): void {
    $rationalMoney = RationalMoney::of(...$rationalMoney);

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $rationalMoney->multipliedBy($operand);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertRationalMoneyEquals($expected, $actual);
})->with('providerMultipliedBy');
dataset('providerMultipliedBy', fn (): array => [
    [['987.65', 'USD'], '1.123456', 'USD 173372081/156250'],
    [['123/456', 'GBP'], '14.99', 'GBP 61459/15200'],
    [['123/456', 'GBP'], '567/890', 'GBP 23247/135280'],
]);
test('divided by', function (array $rationalMoney, mixed $operand, string $expected): void {
    $rationalMoney = RationalMoney::of(...$rationalMoney);

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $rationalMoney->dividedBy($operand);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertRationalMoneyEquals($expected, $actual);
})->with('providerDividedBy');
dataset('providerDividedBy', fn (): array => [
    [['987.65', 'USD'], '1.123456', 'USD 61728125/70216'],
    [['987.65', 'USD'], '5', 'USD 19753/100'],
    [['123/456', 'GBP'], '14.99', 'GBP 1025/56962'],
    [['123/456', 'GBP'], '567/890', 'GBP 18245/43092'],
]);
test('simplified', function (array $rationalMoney, string $expected): void {
    $rationalMoney = RationalMoney::of(...$rationalMoney);

    $actual = $rationalMoney->simplified();
    self::assertRationalMoneyEquals($expected, $actual);
})->with('providerSimplified');
dataset('providerSimplified', fn (): array => [
    [['123456/10000', 'USD'], 'USD 7716/625'],
    [['695844/45600', 'CAD'], 'CAD 57987/3800'],
    [['368022/405840', 'EUR'], 'EUR 61337/67640'],
    [['-671244/45600', 'GBP'], 'GBP -55937/3800'],
]);
test('to', function (array $rationalMoney, Context $context, RoundingMode $roundingMode, string $expected): void {
    $rationalMoney = RationalMoney::of(...$rationalMoney);

    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = $rationalMoney->to($context, $roundingMode);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertMoneyIs($expected, $actual);
})->with('providerTo');
dataset('providerTo', fn (): array => [
    [['987.65', 'USD'], new DefaultContext(), RoundingMode::Unnecessary, 'USD 987.65'],
    [['246/200', 'USD'], new DefaultContext(), RoundingMode::Unnecessary, 'USD 1.23'],
    [['987.65', 'CZK'], new CashContext(100), RoundingMode::Up, 'CZK 988.00'],
    [['123/456', 'GBP'], new CustomContext(4), RoundingMode::Up, 'GBP 0.2698'],
    [['123/456', 'GBP'], new AutoContext(), RoundingMode::Unnecessary, RoundingNecessaryException::class],
    [['123456789/256', 'CHF'], new AutoContext(), RoundingMode::Unnecessary, 'CHF 482253.08203125'],
]);
test('json serialize', function (RationalMoney $money, array $expected): void {
    self::assertSame($expected, $money->jsonSerialize());
    self::assertSame(json_encode($expected), json_encode($money));
})->with('providerJsonSerialize');
dataset('providerJsonSerialize', fn (): array => [
    [RationalMoney::of('3.5', 'EUR'), ['amount' => '7/2', 'currency' => 'EUR']],
    [RationalMoney::of('3.888923', 'GBP'), ['amount' => '3888923/1000000', 'currency' => 'GBP']],
]);
