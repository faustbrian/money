<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

use Cline\Money\Exception\NoCurrencyForCountryException;
use Cline\Money\Exception\NoSingleCurrencyForCountryException;
use Cline\Money\Exception\UnknownCurrencyCodeException;
use Cline\Money\Exception\UnknownCurrencyException;

use function count;
use function ksort;

/**
 * Provides ISO 4217 currencies.
 * @author Brian Faust <brian@cline.sh>
 */
final class ISOCurrencyProvider
{
    private static ?ISOCurrencyProvider $instance = null;

    /**
     * An associative array of currency numeric code to currency code.
     *
     * This property is set on-demand, as soon as required.
     *
     * @var null|array<int, string>
     */
    private ?array $numericToCurrency = null;

    /**
     * An associative array of country code to current currency codes.
     *
     * This property is set on-demand, as soon as required.
     *
     * @var null|array<string, list<string>>
     */
    private ?array $countryToCurrencyCurrent = null;

    /**
     * An associative array of country code to currency codes.
     * Contains only historical currencies. The countries may no longer exist.
     *
     * This property is set on-demand, as soon as required.
     *
     * @var null|array<string, list<string>>
     */
    private ?array $countryToCurrencyHistorical = null;

    /**
     * The Currency instances.
     *
     * The instances are created on-demand, as soon as they are requested.
     *
     * @var array<string, Currency>
     */
    private array $currencies = [];

    /**
     * Whether the provider is in a partial state.
     *
     * This is true as long as all the currencies have not been instantiated yet.
     */
    private bool $isPartial = true;

    /**
     * The raw currency data, indexed by currency code.
     *
     * @var array<string, array{string, int, string, int, CurrencyType}>
     */
    private readonly array $currencyData;

    /**
     * Private constructor. Use `getInstance()` to obtain the singleton instance.
     */
    private function __construct()
    {
        /** @var array<string, array{string, int, string, int, CurrencyType}> $currencyData */
        $currencyData = require __DIR__.'/../data/iso-currencies.php';

        $this->currencyData = $currencyData;
    }

    /**
     * Returns the singleton instance of ISOCurrencyProvider.
     */
    public static function getInstance(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Returns the currency matching the given currency code.
     *
     * @param string $currencyCode The 3-letter ISO 4217 currency code.
     *
     * @throws UnknownCurrencyException If the currency code is not known.
     * @return Currency                 The currency.
     */
    public function getCurrency(string $currencyCode): Currency
    {
        if (isset($this->currencies[$currencyCode])) {
            return $this->currencies[$currencyCode];
        }

        if (!isset($this->currencyData[$currencyCode])) {
            throw UnknownCurrencyCodeException::forCode($currencyCode);
        }

        $currency = new Currency(...$this->currencyData[$currencyCode]);

        return $this->currencies[$currencyCode] = $currency;
    }

    /**
     * Returns a Currency instance matching the given ISO currency code.
     *
     * Note: Numeric codes often mirror the ISO 3166-1 numeric code of the issuing
     * country/territory, so they may outlive a particular currency and be kept/reused
     * across currency changes. The resolved Currency therefore depends on the ISO 4217
     * dataset version and may change after an update in a minor version.
     *
     * @param int $currencyCode The numeric ISO 4217 currency code.
     *
     * @throws UnknownCurrencyException If the currency code is not known.
     * @return Currency                 The currency.
     */
    public function getCurrencyByNumericCode(int $currencyCode): Currency
    {
        if ($this->numericToCurrency === null) {
            /** @var array<int, string> $numericToCurrency */
            $numericToCurrency = require __DIR__.'/../data/numeric-to-currency.php';

            $this->numericToCurrency = $numericToCurrency;
        }

        if (isset($this->numericToCurrency[$currencyCode])) {
            return $this->getCurrency($this->numericToCurrency[$currencyCode]);
        }

        throw UnknownCurrencyCodeException::forCode($currencyCode);
    }

    /**
     * Returns all the available currencies.
     *
     * @return array<string, Currency> The currencies, indexed by currency code.
     */
    public function getAvailableCurrencies(): array
    {
        if ($this->isPartial) {
            foreach ($this->currencyData as $currencyCode => $data) {
                if (isset($this->currencies[$currencyCode])) {
                    continue;
                }

                $this->currencies[$currencyCode] = new Currency(...$data);
            }

            ksort($this->currencies);

            $this->isPartial = false;
        }

        return $this->currencies;
    }

    /**
     * Returns the current currency for the given ISO country code.
     *
     * Note: This value may change in minor releases, as countries may change their official currency.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @throws UnknownCurrencyException If the country code is not known, or the country has no single currency.
     * @return Currency                 The single active currency for the given country.
     */
    public function getCurrencyForCountry(string $countryCode): Currency
    {
        $currencies = $this->getCurrenciesForCountry($countryCode);

        $count = count($currencies);

        if ($count === 1) {
            return $currencies[0];
        }

        if ($count === 0) {
            throw NoCurrencyForCountryException::forCountry($countryCode);
        }

        $currencyCodes = [];

        foreach ($currencies as $currency) {
            $currencyCodes[] = $currency->getCurrencyCode();
        }

        throw NoSingleCurrencyForCountryException::forCountry($countryCode, $currencyCodes);
    }

    /**
     * Returns the current currencies for the given ISO country code.
     *
     * If the country code is not known, or if the country has no official currency, an empty array is returned.
     *
     * Note: This value may change in minor releases, as countries may change their official currencies.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @return list<Currency>
     */
    public function getCurrenciesForCountry(string $countryCode): array
    {
        if ($this->countryToCurrencyCurrent === null) {
            /** @var array<string, list<string>> $countryToCurrencyCurrent */
            $countryToCurrencyCurrent = require __DIR__.'/../data/country-to-currency.php';

            $this->countryToCurrencyCurrent = $countryToCurrencyCurrent;
        }

        $result = [];

        if (isset($this->countryToCurrencyCurrent[$countryCode])) {
            foreach ($this->countryToCurrencyCurrent[$countryCode] as $currencyCode) {
                $result[] = $this->getCurrency($currencyCode);
            }
        }

        return $result;
    }

    /**
     * Returns the historical currencies for the given ISO country code.
     *
     * If the country code is not known, or if the country has no official currency, an empty array is returned.
     *
     * Note: This value may change in minor releases, as additional currencies can be withdrawn from countries.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @return list<Currency>
     */
    public function getHistoricalCurrenciesForCountry(string $countryCode): array
    {
        if ($this->countryToCurrencyHistorical === null) {
            /** @var array<string, list<string>> $countryToCurrencyHistorical */
            $countryToCurrencyHistorical = require __DIR__.'/../data/country-to-currency-historical.php';

            $this->countryToCurrencyHistorical = $countryToCurrencyHistorical;
        }

        $result = [];

        if (isset($this->countryToCurrencyHistorical[$countryCode])) {
            foreach ($this->countryToCurrencyHistorical[$countryCode] as $currencyCode) {
                $result[] = $this->getCurrency($currencyCode);
            }
        }

        return $result;
    }
}
