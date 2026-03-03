<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

use RuntimeException;

use function sprintf;

/**
 * Thrown when a context is constructed with a cash rounding step that violates the allowed constraints.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see \Cline\Money\Context\CashContext
 * @see \Cline\Money\Context\CustomContext
 */
final class InvalidStepException extends RuntimeException implements MoneyException
{
    /**
     * Creates an exception for the given invalid step value.
     *
     * @param int $step The step value that failed validation.
     *
     * @return self The created exception instance.
     */
    public static function forStep(int $step): self
    {
        return new self(sprintf('Invalid step: %d.', $step));
    }
}
