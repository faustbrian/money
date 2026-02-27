<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

use function implode;

/**
 * Thrown when a country code maps to multiple currencies and a single one cannot be determined.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see NoCurrencyForCountryException When a country maps to no currency at all.
 */
final class NoSingleCurrencyForCountryException extends UnknownCurrencyException
{
    /**
     * Creates an exception listing all currencies associated with the ambiguous country code.
     *
     * @param string        $countryCode   The ISO 3166-1 alpha-2 country code (e.g. "US").
     * @param array<string> $currencyCodes All currency codes mapped to the country.
     *
     * @return self The created exception instance.
     */
    public static function forCountry(string $countryCode, array $currencyCodes): self
    {
        return new self('No single currency for country '.$countryCode.': '.implode(', ', $currencyCodes));
    }
}
