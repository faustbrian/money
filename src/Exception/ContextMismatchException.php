<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

use function sprintf;

/**
 * Thrown when a money operation requires both operands to share the same context.
 *
 * When mixing monies with different contexts is intentional, callers should convert to a rational
 * representation first using {@see \Cline\Money\Money::toRational()} before performing the operation.
 * @author Brian Faust <brian@cline.sh>
 */
final class ContextMismatchException extends MoneyMismatchException
{
    /**
     * Creates an exception for the named method that received a money with a mismatched context.
     *
     * The message includes the method name so callers can identify which operation triggered the
     * mismatch and the recommended workaround.
     *
     * @param string $method The fully-qualified method name (e.g. "Money::plus").
     *
     * @return self The created exception instance.
     */
    public static function forMethod(string $method): self
    {
        return new self(sprintf(
            'The monies do not share the same context. '.
            'If this is intended, use %s($money->toRational()) instead of %s($money).',
            $method,
            $method,
        ));
    }
}
