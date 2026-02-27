<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Formatter;

use Cline\Money\Exception\NumberFormattingException;
use Cline\Money\Money;
use Cline\Money\MoneyFormatter;
use NumberFormatter;
use Override;

/**
 * Formats money by delegating to a caller-supplied {@see NumberFormatter} instance.
 *
 * This formatter is a thin adapter that applies an externally configured NumberFormatter
 * to a Money value, giving callers full control over locale, style, pattern, and any
 * other NumberFormatter attributes before passing the formatter in.
 *
 * Note: NumberFormatter converts amounts to floating-point internally, so rounding
 * discrepancies may appear with very large monetary values.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class MoneyNumberFormatter implements MoneyFormatter
{
    /**
     * @param NumberFormatter $numberFormatter A pre-configured ICU NumberFormatter instance.
     *                                         The formatter must be configured with the desired
     *                                         locale, style (e.g. CURRENCY), and any custom
     *                                         attributes before being passed to this constructor.
     */
    public function __construct(
        private NumberFormatter $numberFormatter,
    ) {}

    /**
     * Formats the given money using the injected NumberFormatter.
     *
     * @param Money $money The money to format.
     *
     * @throws NumberFormattingException When the NumberFormatter fails
     *                                   to produce a formatted string.
     * @return string                    The formatted value as produced by the configured NumberFormatter.
     */
    #[Override()]
    public function format(Money $money): string
    {
        $formatted = $this->numberFormatter->formatCurrency(
            $money->getAmount()->toFloat(),
            $money->getCurrency()->getCurrencyCode(),
        );

        if ($formatted === false) {
            throw NumberFormattingException::formattingFailed();
        }

        return $formatted;
    }
}
