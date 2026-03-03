<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Math\RoundingMode;
use Cline\Money\Context\CashContext;
use Cline\Money\Context\CustomContext;
use Cline\Money\Money;
use Cline\Money\TaxRate;

// --- includingTax ---

test('includingTax computes gross and tax correctly', function (string $netStr, string $currency, string $rateStr, string $expectedGross, string $expectedTax): void {
    $net = Money::of($netStr, $currency);
    $rate = TaxRate::of($rateStr);

    $result = $net->includingTax($rate);

    self::assertMoneyIs($expectedGross, $result->getGross());
    self::assertMoneyIs($expectedTax, $result->getTax());
    self::assertMoneyIs($currency.' '.$netStr, $result->getNet());
})->with('providerIncludingTax');

dataset('providerIncludingTax', fn (): array => [
    'EUR 25.5% tax' => ['100.00', 'EUR', '25.5', 'EUR 125.50', 'EUR 25.50'],
    'EUR 20% tax' => ['100.00', 'EUR', '20', 'EUR 120.00', 'EUR 20.00'],
    'USD 10% tax' => ['49.99', 'USD', '10', 'USD 54.99', 'USD 5.00'],
    'JPY 10% tax' => ['1000', 'JPY', '10', 'JPY 1100', 'JPY 100'],
    'EUR 7.5% tax' => ['33.33', 'EUR', '7.5', 'EUR 35.83', 'EUR 2.50'],
    'EUR 100% tax' => ['50.00', 'EUR', '100', 'EUR 100.00', 'EUR 50.00'],
    'EUR 150% tax' => ['50.00', 'EUR', '150', 'EUR 125.00', 'EUR 75.00'],
]);

test('includingTax with zero rate returns same amount', function (): void {
    $net = Money::of('100.00', 'EUR');
    $rate = TaxRate::zero();

    $result = $net->includingTax($rate);

    self::assertMoneyIs('EUR 100.00', $result->getNet());
    self::assertMoneyIs('EUR 100.00', $result->getGross());
    self::assertMoneyIs('EUR 0.00', $result->getTax());
});

test('includingTax preserves net + tax = gross invariant', function (): void {
    $net = Money::of('33.33', 'EUR');
    $rate = TaxRate::of('21');

    $result = $net->includingTax($rate);

    $recomputed = $result->getNet()->plus($result->getTax());
    self::assertMoneyIs((string) $result->getGross(), $recomputed);
});

test('includingTax with negative amount', function (): void {
    $net = Money::of('-100.00', 'EUR');
    $rate = TaxRate::of('20');

    $result = $net->includingTax($rate);

    self::assertMoneyIs('EUR -120.00', $result->getGross());
    self::assertMoneyIs('EUR -20.00', $result->getTax());
});

test('includingTax with custom rounding mode', function (): void {
    $net = Money::of('10.00', 'EUR');
    $rate = TaxRate::of('33.333');

    $resultUp = $net->includingTax($rate, RoundingMode::Up);
    $resultDown = $net->includingTax($rate, RoundingMode::Down);

    self::assertMoneyIs('EUR 13.34', $resultUp->getGross());
    self::assertMoneyIs('EUR 13.33', $resultDown->getGross());
});

// --- excludingTax ---

test('excludingTax computes net and tax correctly', function (string $grossStr, string $currency, string $rateStr, string $expectedNet, string $expectedTax): void {
    $gross = Money::of($grossStr, $currency);
    $rate = TaxRate::of($rateStr);

    $result = $gross->excludingTax($rate);

    self::assertMoneyIs($expectedNet, $result->getNet());
    self::assertMoneyIs($expectedTax, $result->getTax());
    self::assertMoneyIs($currency.' '.$grossStr, $result->getGross());
})->with('providerExcludingTax');

dataset('providerExcludingTax', fn (): array => [
    'EUR 25.5% tax' => ['125.50', 'EUR', '25.5', 'EUR 100.00', 'EUR 25.50'],
    'EUR 20% tax' => ['120.00', 'EUR', '20', 'EUR 100.00', 'EUR 20.00'],
    'USD 10% tax' => ['54.99', 'USD', '10', 'USD 49.99', 'USD 5.00'],
    'JPY 10% tax' => ['1100', 'JPY', '10', 'JPY 1000', 'JPY 100'],
]);

test('excludingTax with zero rate returns same amount', function (): void {
    $gross = Money::of('120.00', 'EUR');
    $rate = TaxRate::zero();

    $result = $gross->excludingTax($rate);

    self::assertMoneyIs('EUR 120.00', $result->getNet());
    self::assertMoneyIs('EUR 120.00', $result->getGross());
    self::assertMoneyIs('EUR 0.00', $result->getTax());
});

test('excludingTax preserves net + tax = gross invariant', function (): void {
    $gross = Money::of('40.33', 'EUR');
    $rate = TaxRate::of('21');

    $result = $gross->excludingTax($rate);

    $recomputed = $result->getNet()->plus($result->getTax());
    self::assertMoneyIs((string) $result->getGross(), $recomputed);
});

test('excludingTax with negative amount', function (): void {
    $gross = Money::of('-120.00', 'EUR');
    $rate = TaxRate::of('20');

    $result = $gross->excludingTax($rate);

    self::assertMoneyIs('EUR -100.00', $result->getNet());
    self::assertMoneyIs('EUR -20.00', $result->getTax());
});

// --- taxAmount ---

test('taxAmount returns only tax', function (): void {
    $net = Money::of('100.00', 'EUR');
    $rate = TaxRate::of('20');

    $tax = $net->taxAmount($rate);

    self::assertMoneyIs('EUR 20.00', $tax);
});

test('taxAmount with zero rate', function (): void {
    $net = Money::of('100.00', 'EUR');

    self::assertMoneyIs('EUR 0.00', $net->taxAmount(TaxRate::zero()));
});

// --- Context handling ---

test('includingTax with CashContext', function (): void {
    $net = Money::of('10.00', 'CHF', new CashContext(5));
    $rate = TaxRate::of('7.7');

    $result = $net->includingTax($rate);

    self::assertMoneyIs('CHF 10.75', $result->getGross());
});

test('includingTax with CustomContext', function (): void {
    $net = Money::of('100.000', 'EUR', new CustomContext(3));
    $rate = TaxRate::of('21');

    $result = $net->includingTax($rate);

    self::assertMoneyIs('EUR 121.000', $result->getGross());
});
