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
use Cline\Money\ISOCurrencyProvider;

/**
 * Resets the singleton instance before running the tests.
 *
 * This is necessary for code coverage to "see" the actual instantiation happen, as it may happen indirectly from
 * another class internally resolving an ISO currency code using ISOCurrencyProvider, and this can originate from
 * code outside test methods (for example in data providers).
 */
beforeAll(function (): void {
    $reflection = new ReflectionClass(ISOCurrencyProvider::class);
    $reflection->setStaticPropertyValue('instance', null);
});
test('get currency', function (string $currencyCode, int $numericCode, string $name, int $defaultFractionDigits, CurrencyType $currencyType): void {
    $provider = ISOCurrencyProvider::getInstance();

    $currency = $provider->getCurrency($currencyCode);
    self::assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currencyType, $currency);

    // Library does not support numeric currency codes of historical currencies
    if ($currencyType !== CurrencyType::IsoCurrent) {
        return;
    }

    $currency = $provider->getCurrencyByNumericCode($numericCode);
    self::assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currencyType, $currency);
})->with('providerGetCurrency');
dataset('providerGetCurrency', fn (): array => [
    ['EUR', 978, 'Euro', 2, CurrencyType::IsoCurrent],
    ['GBP', 826, 'Pound Sterling', 2, CurrencyType::IsoCurrent],
    ['USD', 840, 'US Dollar', 2, CurrencyType::IsoCurrent],
    ['CAD', 124, 'Canadian Dollar', 2, CurrencyType::IsoCurrent],
    ['AUD', 36, 'Australian Dollar', 2, CurrencyType::IsoCurrent],
    ['NZD', 554, 'New Zealand Dollar', 2, CurrencyType::IsoCurrent],
    ['JPY', 392, 'Yen', 0, CurrencyType::IsoCurrent],
    ['TND', 788, 'Tunisian Dinar', 3, CurrencyType::IsoCurrent],
    ['DZD', 12, 'Algerian Dinar', 2, CurrencyType::IsoCurrent],
    ['ALL', 8, 'Lek', 2, CurrencyType::IsoCurrent],
    ['ITL', 380, 'Italian Lira', 0, CurrencyType::IsoHistorical],
    ['BGN', 975, 'Bulgarian Lev', 2, CurrencyType::IsoHistorical],
]);
test('get unknown currency', function (string $currencyCode): void {
    $this->expectException(UnknownCurrencyException::class);
    ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
})->with('providerUnknownCurrency');
dataset('providerUnknownCurrency', fn (): array => [
    ['XXX'],
    ['XFO'],
    ['XEU'],
]);
test('get available currencies', function (): void {
    $provider = ISOCurrencyProvider::getInstance();

    $eur = $provider->getCurrency('EUR');
    $gbp = $provider->getCurrency('GBP');
    $usd = $provider->getCurrency('USD');

    $availableCurrencies = $provider->getAvailableCurrencies();

    self::assertGreaterThan(100, count($availableCurrencies));

    self::assertContainsOnlyInstancesOf(Currency::class, $availableCurrencies);

    self::assertSame($eur, $availableCurrencies['EUR']);
    self::assertSame($gbp, $availableCurrencies['GBP']);
    self::assertSame($usd, $availableCurrencies['USD']);
});
test('get historical currencies', function (string $countryCode, array $currencyCodes): void {
    $provider = ISOCurrencyProvider::getInstance();

    $currencies = $provider->getHistoricalCurrenciesForCountry($countryCode);

    self::assertSameSize($currencyCodes, $currencies);

    $retrievedCurrencyCodes = [];

    foreach ($currencies as $currency) {
        self::assertInstanceOf(Currency::class, $currency);
        $retrievedCurrencyCodes[] = $currency->getCurrencyCode();
    }

    self::assertEquals($currencyCodes, $retrievedCurrencyCodes);
})->with('providerHistoricalCurrencies');
dataset('providerHistoricalCurrencies', fn (): array => [
    ['AD', ['ADP', 'ESP', 'FRF']],
    ['IT', ['ITL']],
]);
test('no historical currency present in current currencies', function (): void {
    $provider = ISOCurrencyProvider::getInstance();

    $currencies = $provider->getCurrenciesForCountry('PA');
    self::assertCount(2, $currencies);

    $retrievedCurrencies = [];

    foreach ($currencies as $currency) {
        $retrievedCurrencies[] = $currency->getCurrencyCode();
    }

    self::assertEquals(['PAB', 'USD'], $retrievedCurrencies);
});
