<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Money\Money;
use Cline\Money\TaxRate;
use Cline\Money\TaxResult;

test('accessors return correct values', function (): void {
    $net = Money::of(100, 'EUR');
    $gross = Money::of('125.50', 'EUR');
    $tax = Money::of('25.50', 'EUR');
    $rate = TaxRate::of('25.5');

    $result = TaxResult::create($net, $gross, $tax, $rate);

    self::assertMoneyIs('EUR 100.00', $result->getNet());
    self::assertMoneyIs('EUR 125.50', $result->getGross());
    self::assertMoneyIs('EUR 25.50', $result->getTax());
    self::assertTrue($rate->isEqualTo($result->getRate()));
});

test('toString', function (): void {
    $net = Money::of(100, 'EUR');
    $gross = Money::of('125.50', 'EUR');
    $tax = Money::of('25.50', 'EUR');
    $rate = TaxRate::of('25.5');

    $result = TaxResult::create($net, $gross, $tax, $rate);

    self::assertSame('net=EUR 100.00 gross=EUR 125.50 tax=EUR 25.50 rate=25.5%', (string) $result);
});

test('jsonSerialize returns array with all components', function (): void {
    $net = Money::of(100, 'EUR');
    $gross = Money::of('125.50', 'EUR');
    $tax = Money::of('25.50', 'EUR');
    $rate = TaxRate::of('25.5');

    $result = TaxResult::create($net, $gross, $tax, $rate);
    $json = $result->jsonSerialize();

    self::assertArrayHasKey('net', $json);
    self::assertArrayHasKey('gross', $json);
    self::assertArrayHasKey('tax', $json);
    self::assertArrayHasKey('rate', $json);
    self::assertSame($net, $json['net']);
    self::assertSame($gross, $json['gross']);
    self::assertSame($tax, $json['tax']);
    self::assertSame($rate, $json['rate']);
});
