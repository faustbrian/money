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
 * Thrown when a money split method receives a parts count less than 1.
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidSplitPartsException extends InvalidArgumentException implements MoneyException
{
    /**
     * Creates an exception for the named split method that received an invalid parts count.
     *
     * @param string $method The name of the split method (e.g. "split").
     *
     * @return self The created exception instance.
     */
    public static function forMethod(string $method): self
    {
        return new self(sprintf('Cannot %s into less than 1 part.', $method));
    }
}
