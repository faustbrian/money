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
 * Thrown when a money allocation method receives an empty list of ratios.
 * @author Brian Faust <brian@cline.sh>
 */
final class EmptyAllocationRatiosException extends InvalidArgumentException implements MoneyException
{
    /**
     * Creates an exception for the named allocation method that received an empty ratio list.
     *
     * @param string $method The name of the allocation method (e.g. "allocate").
     *
     * @return self The created exception instance.
     */
    public static function forMethod(string $method): self
    {
        return new self(sprintf('Cannot %s an empty list of ratios.', $method));
    }
}
