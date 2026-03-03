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
 * Thrown when a discount rate exceeding 100% is provided.
 * @author Brian Faust <brian@cline.sh>
 */
final class ExcessiveDiscountRateException extends InvalidArgumentException implements MoneyException
{
    /**
     * Creates an exception for the given excessive rate.
     *
     * @param string $rate The excessive rate that was provided.
     *
     * @return self The created exception instance.
     */
    public static function forRate(string $rate): self
    {
        return new self(sprintf('Discount rate must not exceed 100, got %s.', $rate));
    }
}
