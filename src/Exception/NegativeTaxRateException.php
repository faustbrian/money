<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

use InvalidArgumentException;

use function sprintf;

/**
 * Thrown when a negative tax rate is provided.
 * @author Brian Faust <brian@cline.sh>
 */
final class NegativeTaxRateException extends InvalidArgumentException implements MoneyException
{
    /**
     * Creates an exception for the given negative rate.
     *
     * @param string $rate The negative rate that was provided.
     *
     * @return self The created exception instance.
     */
    public static function forRate(string $rate): self
    {
        return new self(sprintf('Tax rate must not be negative, got %s.', $rate));
    }
}
