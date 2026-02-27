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
 * Thrown when a rounding mode is supplied that a given context does not support.
 * @author Brian Faust <brian@cline.sh>
 */
final class UnsupportedRoundingModeException extends InvalidArgumentException implements MoneyException
{
    /**
     * Creates an exception for use when AutoContext receives a rounding mode other than Unnecessary.
     *
     * @return self The created exception instance.
     */
    public static function forAutoContext(): self
    {
        return new self('AutoContext only supports RoundingMode::Unnecessary');
    }
}
