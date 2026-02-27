<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

use Cline\Money\Currency;

use function sprintf;

/**
 * Thrown when a money operation requires both operands to use the same currency.
 * @author Brian Faust <brian@cline.sh>
 */
final class CurrencyMismatchException extends MoneyMismatchException
{
    /**
     * Creates an exception describing the expected and actual currencies involved in the mismatch.
     *
     * @param Currency $expected The currency that the operation requires.
     * @param Currency $actual   The currency that was actually provided.
     *
     * @return self The created exception instance.
     */
    public static function forCurrencies(Currency $expected, Currency $actual): self
    {
        return new self(sprintf(
            'The monies do not share the same currency: expected %s, got %s.',
            $expected->getCurrencyCode(),
            $actual->getCurrencyCode(),
        ));
    }
}
