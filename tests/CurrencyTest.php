<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Money\Currency;
use Cline\Money\CurrencyType;
use Cline\Money\Exception\UnknownCurrencyException;

test('of', function (string $currencyCode, int $numericCode, int $fractionDigits, string $name, CurrencyType $currencyType): void {
    $currency = Currency::of($currencyCode);
    self::assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currencyType, $currency);
})->with('providerOf');
dataset('providerOf', fn (): array => [
    ['USD', 840, 2, 'US Dollar', CurrencyType::IsoCurrent],
    ['EUR', 978, 2, 'Euro', CurrencyType::IsoCurrent],
    ['GBP', 826, 2, 'Pound Sterling', CurrencyType::IsoCurrent],
    ['JPY', 392, 0, 'Yen', CurrencyType::IsoCurrent],
    ['DZD', 12, 2, 'Algerian Dinar', CurrencyType::IsoCurrent],
    ['SKK', 703, 2, 'Slovak Koruna', CurrencyType::IsoHistorical],
]);
test('of unknown currency code', function (string $currencyCode): void {
    $this->expectException(UnknownCurrencyException::class);
    Currency::of($currencyCode);
})->with('providerOfUnknownCurrencyCode');
dataset('providerOfUnknownCurrencyCode', fn (): array => [
    ['XXX'],
]);
test('constructor', function (): void {
    $bitCoin = new Currency('BTC', -1, 'BitCoin', 8);
    self::assertCurrencyEquals('BTC', -1, 'BitCoin', 8, CurrencyType::Custom, $bitCoin);
});
test('of returns same instance', function (): void {
    self::assertSame(Currency::of('EUR'), Currency::of('EUR'));
});
test('of country', function (string $countryCode, string $expected): void {
    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = Currency::ofCountry($countryCode);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertInstanceOf(Currency::class, $actual);
    self::assertSame($expected, $actual->getCurrencyCode());
})->with('providerOfCountry');
dataset('providerOfCountry', fn (): array => [
    ['CA', 'CAD'],
    ['CH', 'CHF'],
    ['DE', 'EUR'],
    ['ES', 'EUR'],
    ['FR', 'EUR'],
    ['GB', 'GBP'],
    ['IT', 'EUR'],
    ['US', 'USD'],
    ['AQ', UnknownCurrencyException::class], // no currency
    ['BT', UnknownCurrencyException::class], // 2 currencies
    ['XX', UnknownCurrencyException::class], // unknown
]);
test('of numeric code', function (int $currencyCode, string $expected): void {
    if (self::isExceptionClass($expected)) {
        $this->expectException($expected);
    }

    $actual = Currency::ofNumericCode($currencyCode);

    if (self::isExceptionClass($expected)) {
        return;
    }

    self::assertInstanceOf(Currency::class, $actual);
    self::assertSame($expected, $actual->getCurrencyCode());
})->with('providerOfNumericCode');
dataset('providerOfNumericCode', fn (): array => [
    [203, 'CZK'],
    [840, 'USD'],
    [1, UnknownCurrencyException::class], // unknown currency
]);
test('create with negative fraction digits', function (): void {
    $this->expectException(InvalidArgumentException::class);
    new Currency('BTC', 0, 'BitCoin', -1);
});
test('is equal to', function (): void {
    $currency = Currency::of('EUR');

    // Test with string currency code
    self::assertTrue($currency->isEqualTo('EUR'));
    self::assertFalse($currency->isEqualTo('USD'));

    // Test with Currency instance
    self::assertTrue($currency->isEqualTo(Currency::of('EUR')));
    self::assertFalse($currency->isEqualTo(Currency::of('USD')));

    // Test with cloned Currency
    $clone = clone $currency;
    self::assertNotSame($currency, $clone);
    self::assertTrue($currency->isEqualTo($clone));
    self::assertTrue($clone->isEqualTo($currency));

    // Test with custom currency
    $customCurrency = new Currency('XBT', 0, 'Bitcoin', 8);
    self::assertTrue($customCurrency->isEqualTo('XBT'));
    self::assertFalse($customCurrency->isEqualTo('BTC'));
    self::assertTrue($customCurrency->isEqualTo(
        new Currency('XBT', 999, 'Different Name', 2),
    ));
});
test('json serialize', function (Currency $currency, string $expected): void {
    self::assertSame($expected, $currency->jsonSerialize());
    self::assertSame(json_encode($expected), json_encode($currency));
})->with('providerJsonSerialize');
dataset('providerJsonSerialize', fn (): array => [
    [Currency::of('USD'), 'USD'],
]);
