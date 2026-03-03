<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Money\DiscountRate;
use Cline\Money\DiscountResult;
use Cline\Money\Money;

test('accessors return correct values', function (): void {
    $original = Money::of(100, 'EUR');
    $discounted = Money::of('80.00', 'EUR');
    $savings = Money::of('20.00', 'EUR');
    $rate = DiscountRate::of('20');

    $result = DiscountResult::create($original, $discounted, $savings, $rate);

    self::assertMoneyIs('EUR 100.00', $result->getOriginal());
    self::assertMoneyIs('EUR 80.00', $result->getDiscounted());
    self::assertMoneyIs('EUR 20.00', $result->getSavings());
    self::assertTrue($rate->isEqualTo($result->getRate()));
});

test('toString', function (): void {
    $original = Money::of(100, 'EUR');
    $discounted = Money::of('80.00', 'EUR');
    $savings = Money::of('20.00', 'EUR');
    $rate = DiscountRate::of('20');

    $result = DiscountResult::create($original, $discounted, $savings, $rate);

    self::assertSame('original=EUR 100.00 discounted=EUR 80.00 savings=EUR 20.00 rate=20%', (string) $result);
});

test('jsonSerialize returns array with all components', function (): void {
    $original = Money::of(100, 'EUR');
    $discounted = Money::of('80.00', 'EUR');
    $savings = Money::of('20.00', 'EUR');
    $rate = DiscountRate::of('20');

    $result = DiscountResult::create($original, $discounted, $savings, $rate);
    $json = $result->jsonSerialize();

    self::assertArrayHasKey('original', $json);
    self::assertArrayHasKey('discounted', $json);
    self::assertArrayHasKey('savings', $json);
    self::assertArrayHasKey('rate', $json);
    self::assertSame($original, $json['original']);
    self::assertSame($discounted, $json['discounted']);
    self::assertSame($savings, $json['savings']);
    self::assertSame($rate, $json['rate']);
});
