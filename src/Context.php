<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

use Cline\Math\BigDecimal;
use Cline\Math\BigNumber;
use Cline\Math\Exception\RoundingNecessaryException;
use Cline\Math\RoundingMode;

/**
 * Adjusts a rational number to a decimal amount.
 * @author Brian Faust <brian@cline.sh>
 */
interface Context
{
    /**
     * Applies this context to a rational amount, and returns a decimal number.
     *
     * The given rounding mode MUST be respected; no default rounding mode must be applied.
     * In case the rounding mode is irrelevant, for example in AutoContext, this method MUST throw an exception if a
     * rounding mode other than RoundingMode::Unnecessary is used.
     *
     * @param BigNumber    $amount       The amount.
     * @param Currency     $currency     The target currency.
     * @param RoundingMode $roundingMode The rounding mode.
     *
     * @throws RoundingNecessaryException If the result cannot be represented at the required scale without rounding.
     * @return BigDecimal                 The amount scaled and rounded according to this context.
     */
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal;

    /**
     * Returns the step used by this context.
     *
     * If no cash rounding is involved, this must return 1.
     * This value is used by money allocation methods that do not go through the applyTo() method.
     *
     * @return int The cash rounding step, or 1 if no cash rounding is applied.
     */
    public function getStep(): int;

    /**
     * Returns whether this context uses a fixed scale and step.
     *
     * When the scale and step are fixed, it is considered safe to add or subtract monies amounts directly —as long as
     * they are in the same context— without going through the applyTo() method, allowing for an optimization.
     *
     * @return bool True if the scale and step are fixed; false for variable-scale contexts such as AutoContext.
     */
    public function isFixedScale(): bool;

    /**
     * Returns whether this context is equivalent to another context.
     *
     * Two contexts are equivalent when they produce identical results for any given amount and currency,
     * meaning operations on monies sharing these contexts can be freely mixed.
     *
     * @param self $other The context to compare against.
     */
    public function isEquivalentTo(self $other): bool;
}
