<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

/**
 * Thrown when a currency code does not match any known currency in the currency provider.
 * @author Brian Faust <brian@cline.sh>
 */
final class UnknownCurrencyCodeException extends UnknownCurrencyException
{
    /**
     * Creates an exception identifying the unrecognised currency code.
     *
     * @param int|string $currencyCode The currency code that could not be resolved (e.g. "XYZ" or 999).
     *
     * @return self The created exception instance.
     */
    public static function forCode(string|int $currencyCode): self
    {
        return new self('Unknown currency code: '.$currencyCode);
    }
}
