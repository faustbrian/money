<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

use Cline\Money\Exception\NegativeFractionDigitsException;
use Cline\Money\Exception\UnknownCurrencyException;
use JsonSerializable;
use Override;
use Stringable;

/**
 * A currency. This class is immutable.
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class Currency implements JsonSerializable, Stringable
{
    /**
     * @param string       $currencyCode          The currency code. For ISO currencies this will be the 3-letter
     *                                            uppercase ISO 4217 currency code. For non-ISO currencies no
     *                                            constraints are defined, but the code must be unique across an
     *                                            application and must not conflict with ISO currency codes.
     * @param int          $numericCode           The numeric currency code. For ISO currencies this will be the
     *                                            ISO 4217 numeric currency code, without leading zeros. For non-ISO
     *                                            currencies no constraints are defined, but the code must be unique
     *                                            across an application and must not conflict with ISO currency codes.
     *                                            Set to zero if the currency does not have a numeric code.
     * @param string       $name                  The currency name. For ISO currencies this will be the official
     *                                            English name of the currency. For non-ISO currencies no constraints
     *                                            are defined.
     * @param int          $defaultFractionDigits The default number of fraction digits (typical scale) used with this
     *                                            currency. For example, the default number of fraction digits for the
     *                                            Euro is 2, while for the Japanese Yen it is 0. This cannot be a
     *                                            negative number.
     * @param CurrencyType $currencyType          The type of the currency. For ISO currencies, this indicates whether
     *                                            the currency is currently in use (IsoCurrent) or has been withdrawn
     *                                            (IsoHistorical). For non-ISO currencies defined by the application,
     *                                            the type is Custom.
     *
     * @throws NegativeFractionDigitsException If $defaultFractionDigits is negative.
     */
    public function __construct(
        private string $currencyCode,
        private int $numericCode,
        private string $name,
        private int $defaultFractionDigits,
        private CurrencyType $currencyType = CurrencyType::Custom,
    ) {
        if ($defaultFractionDigits < 0) {
            throw NegativeFractionDigitsException::create();
        }
    }

    /**
     * Returns the alphabetic currency code (e.g. "USD", "EUR").
     *
     * @return string The currency code.
     */
    #[Override()]
    public function __toString(): string
    {
        return $this->currencyCode;
    }

    /**
     * Returns a Currency instance matching the given ISO currency code.
     *
     * @param string $currencyCode The 3-letter ISO 4217 currency code.
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     */
    public static function of(string $currencyCode): self
    {
        return ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
    }

    /**
     * Returns the current currency for the given ISO country code.
     *
     * Note: This value may change in minor releases, as countries may change their official currency.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @throws UnknownCurrencyException If the country code is unknown, or there is no single currency for the country.
     */
    public static function ofCountry(string $countryCode): self
    {
        return ISOCurrencyProvider::getInstance()->getCurrencyForCountry($countryCode);
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
     * @throws UnknownCurrencyException If an unknown currency code is given.
     */
    public static function ofNumericCode(int $currencyCode): self
    {
        return ISOCurrencyProvider::getInstance()->getCurrencyByNumericCode($currencyCode);
    }

    /**
     * Returns the currency code.
     *
     * For ISO currencies this will be the 3-letter uppercase ISO 4217 currency code.
     * For non ISO currencies no constraints are defined.
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * Returns the numeric currency code.
     *
     * For ISO currencies this will be the ISO 4217 numeric currency code, without leading zeros.
     * For non ISO currencies no constraints are defined.
     */
    public function getNumericCode(): int
    {
        return $this->numericCode;
    }

    /**
     * Returns the name of the currency.
     *
     * For ISO currencies this will be the official English name of the currency.
     * For non ISO currencies no constraints are defined.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the default number of fraction digits (typical scale) used with this currency.
     *
     * For example, the default number of fraction digits for the Euro is 2, while for the Japanese Yen it is 0.
     */
    public function getDefaultFractionDigits(): int
    {
        return $this->defaultFractionDigits;
    }

    /**
     * Returns whether this currency is equal to the given currency.
     *
     * The currencies are considered equal if and only if their alphabetic currency codes are equal.
     * Two currencies with the same numeric code but different alphabetic codes are NOT considered equal,
     * because numeric codes may outlive a particular currency and be reused across currency changes.
     *
     * @param self|string $currency The Currency instance or ISO currency code.
     *
     * @return bool True if both currencies share the same alphabetic code.
     */
    public function isEqualTo(self|string $currency): bool
    {
        $currencyCode = $currency instanceof self ? $currency->getCurrencyCode() : $currency;

        return $currencyCode === $this->currencyCode;
    }

    /**
     * Returns the type of this currency.
     *
     * For ISO currencies, this will be either IsoCurrent (in use) or IsoHistorical (withdrawn).
     * For application-defined currencies, the type is Custom.
     */
    public function getCurrencyType(): CurrencyType
    {
        return $this->currencyType;
    }

    /**
     * Returns the alphabetic currency code for JSON serialization.
     *
     * @return string The ISO 4217 alphabetic currency code (e.g. "USD").
     */
    #[Override()]
    public function jsonSerialize(): string
    {
        return $this->currencyCode;
    }
}
