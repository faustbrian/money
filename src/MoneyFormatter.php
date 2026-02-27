<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

/**
 * Formats a Money value as a string.
 *
 * Implementations determine the output style, such as locale-aware formatting
 * (e.g. "$1,234.56") or plain exact formatting (e.g. "USD 1234.56").
 * @author Brian Faust <brian@cline.sh>
 */
interface MoneyFormatter
{
    /**
     * Formats the given money as a string.
     *
     * @param Money $money The money to format.
     *
     * @return string The formatted monetary value.
     */
    public function format(Money $money): string;
}
