<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Formatter;

use Cline\Money\Money;
use Cline\Money\MoneyFormatter;
use Override;

/**
 * Formats money without converting the amount to floating point.
 *
 * Produces output in the form "ISO_CODE{separator}AMOUNT" (e.g. "USD 1234.56"),
 * preserving the exact decimal representation of the amount without any
 * floating-point conversion. Use this formatter when precision must be
 * guaranteed and locale-aware symbols are not required.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class MoneyExactFormatter implements MoneyFormatter
{
    /**
     * @param string $separator The string placed between the currency code and the amount.
     *                          Defaults to a single space, producing output such as "USD 1234.56".
     *                          Pass an empty string to produce "USD1234.56".
     */
    public function __construct(
        private string $separator = ' ',
    ) {}

    /**
     * Formats the given money as a currency-code-prefixed decimal string.
     *
     * @param Money $money The money to format.
     *
     * @return string The formatted value (e.g. "USD 1234.56").
     */
    #[Override()]
    public function format(Money $money): string
    {
        return $money->getCurrency()->getCurrencyCode().
            $this->separator.
            $money->getAmount();
    }
}
