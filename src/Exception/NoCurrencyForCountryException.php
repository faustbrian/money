<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

/**
 * Thrown when a country code resolves to no currency in the currency provider.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see NoSingleCurrencyForCountryException When a country maps to more than one currency.
 */
final class NoCurrencyForCountryException extends UnknownCurrencyException
{
    /**
     * Creates an exception for the given country code that has no associated currency.
     *
     * @param string $countryCode The ISO 3166-1 alpha-2 country code (e.g. "AQ").
     *
     * @return self The created exception instance.
     */
    public static function forCountry(string $countryCode): self
    {
        return new self('No currency found for country '.$countryCode);
    }
}
