<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Formatter;

use Cline\Money\Exception\LocaleFormattingException;
use Cline\Money\Money;
use Cline\Money\MoneyFormatter;
use NumberFormatter;
use Override;

/**
 * Formats money using the ICU {@see NumberFormatter} for locale-aware currency output.
 *
 * Produces locale-sensitive strings such as "$1,234.56" for en_US or "1 234,56 €" for fr_FR,
 * applying the correct currency symbol, grouping separators, and decimal notation for the
 * requested locale. The optional whole-number mode suppresses fractional digits when the
 * amount carries no non-zero fractional part (e.g. "100" instead of "100.00").
 *
 * Note: NumberFormatter converts amounts to floating-point internally, so rounding
 * discrepancies may appear with very large monetary values.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class MoneyLocaleFormatter implements MoneyFormatter
{
    /** @var NumberFormatter The ICU currency formatter configured for the requested locale. */
    private NumberFormatter $numberFormatter;

    /**
     * @param string $locale           The ICU locale identifier used to format the output,
     *                                 for example 'en_US' or 'fr_FR'.
     * @param bool   $allowWholeNumber When true, amounts with no non-zero fractional part are
     *                                 formatted without decimal digits (e.g. "$100" instead of
     *                                 "$100.00"). When false, the full scale of the amount is
     *                                 always rendered.
     */
    public function __construct(
        string $locale,
        private bool $allowWholeNumber,
    ) {
        $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    }

    /**
     * Formats the given money as a locale-aware currency string.
     *
     * @param Money $money The money to format.
     *
     * @throws LocaleFormattingException When the underlying NumberFormatter
     *                                   fails to produce a formatted string.
     * @return string                    The formatted value (e.g. "$1,234.56" for en_US).
     */
    #[Override()]
    public function format(Money $money): string
    {
        if ($this->allowWholeNumber && !$money->getAmount()->hasNonZeroFractionalPart()) {
            $scale = 0;
        } else {
            $scale = $money->getAmount()->getScale();
        }

        // Synchronise the formatter's fraction-digit bounds to match the amount's scale.
        $this->numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $scale);
        $this->numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $scale);

        $formatted = $this->numberFormatter->formatCurrency(
            $money->getAmount()->toFloat(),
            $money->getCurrency()->getCurrencyCode(),
        );

        if ($formatted === false) {
            throw LocaleFormattingException::formattingFailed();
        }

        return $formatted;
    }
}
