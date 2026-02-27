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
 * Thrown when formatting a money value for a specific locale fails.
 *
 * This typically occurs when the requested locale is unsupported by the underlying
 * ICU {@see \NumberFormatter} or when the formatter cannot be constructed.
 * @author Brian Faust <brian@cline.sh>
 */
final class LocaleFormattingException extends RuntimeException implements MoneyException
{
    /**
     * Creates an exception indicating that locale-based formatting was unsuccessful.
     *
     * @return self The created exception instance.
     */
    public static function formattingFailed(): self
    {
        return new self('Unable to format money for the requested locale.');
    }
}
