<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

use RuntimeException;

/**
 * Thrown when a NumberFormatter fails to produce a formatted representation of a money amount.
 * @author Brian Faust <brian@cline.sh>
 */
final class NumberFormattingException extends RuntimeException implements MoneyException
{
    /**
     * Creates an exception indicating the number formatter returned a failure result.
     *
     * @return self The created exception instance.
     */
    public static function formattingFailed(): self
    {
        return new self('Unable to format money with the provided number formatter.');
    }
}
