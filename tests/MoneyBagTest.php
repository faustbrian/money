<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Money\Context\AutoContext;
use Cline\Money\Currency;
use Cline\Money\Money;
use Cline\Money\MoneyBag;
use Cline\Money\RationalMoney;

test('empty money bag', function (): void {
    $moneyBag = new MoneyBag();

    self::assertMoneyBagContains([], $moneyBag);

    foreach (['USD', 'EUR', 'GBP', 'JPY'] as $currencyCode) {
        self::assertTrue($moneyBag->getMoney($currencyCode)->getAmount()->isZero());
    }
});
test('add subtract money', function (): MoneyBag {
    $moneyBag = new MoneyBag();

    $moneyBag->add(Money::of('123', 'EUR'));
    self::assertMoneyBagContains(['EUR' => '123.00'], $moneyBag);

    $moneyBag->add(Money::of('234.99', 'EUR'));
    self::assertMoneyBagContains(['EUR' => '357.99'], $moneyBag);

    $moneyBag->add(Money::of(3, 'JPY'));
    self::assertMoneyBagContains(['EUR' => '357.99', 'JPY' => '3'], $moneyBag);

    $moneyBag->add(Money::of('1.1234', 'JPY', new AutoContext()));
    self::assertMoneyBagContains(['EUR' => '357.99', 'JPY' => '4.1234'], $moneyBag);

    $moneyBag->subtract(Money::of('3.589950', 'EUR', new AutoContext()));
    self::assertMoneyBagContains(['EUR' => '354.400050', 'JPY' => '4.1234'], $moneyBag);

    $moneyBag->add(RationalMoney::of('1/3', 'EUR'));
    self::assertMoneyBagContains(['EUR' => '21284003/60000', 'JPY' => '4.1234'], $moneyBag);

    return $moneyBag;
});
test('add custom currency', function (MoneyBag $moneyBag): void {
    $moneyBag->add(Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)));
    self::assertMoneyBagContains(['EUR' => '21284003/60000', 'JPY' => '4.1234', 'BTC' => '0.1234'], $moneyBag);
})->depends('add subtract money');
