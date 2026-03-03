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
 * Thrown when a money allocation method receives a ratio list consisting entirely of zeros.
 *
 * A valid allocation must contain at least one non-zero ratio so that the total
 * weight is calculable and each allocated part has a defined proportion.
 * @author Brian Faust <brian@cline.sh>
 */
final class ZeroAllocationRatiosException extends InvalidArgumentException implements MoneyException
{
    /**
     * Creates an exception for the named allocation method that received an all-zero ratio list.
     *
     * @param string $method The name of the allocation method (e.g. "allocate").
     *
     * @return self The created exception instance.
     */
    public static function forMethod(string $method): self
    {
        return new self(sprintf('Cannot %s to zero ratios only.', $method));
    }
}
