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
use Cline\Money\DiscountRate;
use Cline\Money\Money;

// --- applyDiscount ---

test('applyDiscount computes discounted and savings correctly', function (string $originalStr, string $currency, string $rateStr, string $expectedDiscounted, string $expectedSavings): void {
    $original = Money::of($originalStr, $currency);
    $rate = DiscountRate::of($rateStr);

    $result = $original->applyDiscount($rate);

    self::assertMoneyIs($expectedDiscounted, $result->getDiscounted());
    self::assertMoneyIs($expectedSavings, $result->getSavings());
    self::assertMoneyIs($currency.' '.$originalStr, $result->getOriginal());
})->with('providerApplyDiscount');

dataset('providerApplyDiscount', fn (): array => [
    'EUR 20% discount' => ['100.00', 'EUR', '20', 'EUR 80.00', 'EUR 20.00'],
    'EUR 25.5% discount' => ['100.00', 'EUR', '25.5', 'EUR 74.50', 'EUR 25.50'],
    'USD 10% discount' => ['49.99', 'USD', '10', 'USD 44.99', 'USD 5.00'],
    'JPY 10% discount' => ['1000', 'JPY', '10', 'JPY 900', 'JPY 100'],
    'EUR 7.5% discount' => ['33.33', 'EUR', '7.5', 'EUR 30.83', 'EUR 2.50'],
    'EUR 50% discount' => ['50.00', 'EUR', '50', 'EUR 25.00', 'EUR 25.00'],
    'EUR 100% discount' => ['50.00', 'EUR', '100', 'EUR 0.00', 'EUR 50.00'],
]);

test('applyDiscount with zero rate returns same amount', function (): void {
    $original = Money::of('100.00', 'EUR');
    $rate = DiscountRate::zero();

    $result = $original->applyDiscount($rate);

    self::assertMoneyIs('EUR 100.00', $result->getOriginal());
    self::assertMoneyIs('EUR 100.00', $result->getDiscounted());
    self::assertMoneyIs('EUR 0.00', $result->getSavings());
});

test('applyDiscount preserves original - savings = discounted invariant', function (): void {
    $original = Money::of('33.33', 'EUR');
    $rate = DiscountRate::of('21');

    $result = $original->applyDiscount($rate);

    $recomputed = $result->getOriginal()->minus($result->getSavings());
    self::assertMoneyIs((string) $result->getDiscounted(), $recomputed);
});

test('applyDiscount with negative amount', function (): void {
    $original = Money::of('-100.00', 'EUR');
    $rate = DiscountRate::of('20');

    $result = $original->applyDiscount($rate);

    self::assertMoneyIs('EUR -80.00', $result->getDiscounted());
    self::assertMoneyIs('EUR -20.00', $result->getSavings());
});

test('applyDiscount with custom rounding mode', function (): void {
    $original = Money::of('10.00', 'EUR');
    $rate = DiscountRate::of('33.333');

    $resultUp = $original->applyDiscount($rate, RoundingMode::Up);
    $resultDown = $original->applyDiscount($rate, RoundingMode::Down);

    self::assertMoneyIs('EUR 6.67', $resultUp->getDiscounted());
    self::assertMoneyIs('EUR 6.66', $resultDown->getDiscounted());
});

// --- discountAmount ---

test('discountAmount returns only savings', function (): void {
    $original = Money::of('100.00', 'EUR');
    $rate = DiscountRate::of('20');

    $savings = $original->discountAmount($rate);

    self::assertMoneyIs('EUR 20.00', $savings);
});

test('discountAmount with zero rate', function (): void {
    $original = Money::of('100.00', 'EUR');

    self::assertMoneyIs('EUR 0.00', $original->discountAmount(DiscountRate::zero()));
});

// --- Context handling ---

test('applyDiscount with CashContext', function (): void {
    $original = Money::of('10.00', 'CHF', new CashContext(5));
    $rate = DiscountRate::of('7.7');

    $result = $original->applyDiscount($rate);

    self::assertMoneyIs('CHF 9.25', $result->getDiscounted());
});

test('applyDiscount with CustomContext', function (): void {
    $original = Money::of('100.000', 'EUR', new CustomContext(3));
    $rate = DiscountRate::of('21');

    $result = $original->applyDiscount($rate);

    self::assertMoneyIs('EUR 79.000', $result->getDiscounted());
});
