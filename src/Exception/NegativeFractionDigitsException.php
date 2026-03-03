<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

use InvalidArgumentException;

/**
 * Thrown when a currency is configured with a negative number of fraction digits.
 * @author Brian Faust <brian@cline.sh>
 */
final class NegativeFractionDigitsException extends InvalidArgumentException implements MoneyException
{
    /**
     * Creates an exception indicating the fraction digits value is below zero.
     *
     * @return self The created exception instance.
     */
    public static function create(): self
    {
        return new self('The default fraction digits cannot be less than zero.');
    }
}
