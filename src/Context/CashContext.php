<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Context;

use Cline\Math\BigDecimal;
use Cline\Math\BigNumber;
use Cline\Math\RoundingMode;
use Cline\Money\Context;
use Cline\Money\Currency;
use Cline\Money\Exception\InvalidStepException;
use Override;

/**
 * Adjusts a number to the default scale for the currency, respecting a cash rounding.
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class CashContext implements Context
{
    /**
     * Creates a new cash rounding context.
     *
     * @param int $step The cash rounding step, in minor units. Must be a multiple of 2 and/or 5.
     *                  For example, step 5 on CHF would allow CHF 0.00, CHF 0.05, CHF 0.10, etc.
     *
     * @throws InvalidStepException If the step is less than 1 or not composed solely of factors of 2 and 5.
     */
    public function __construct(
        private int $step,
    ) {
        if (!$this->isValidStep($step)) {
            throw InvalidStepException::forStep($step);
        }
    }

    /**
     * Applies this context to a rational amount, scaling to the currency's default fraction digits
     * and rounding to the nearest cash step.
     *
     * When the step is greater than 1, the amount is divided by the step, scaled, then
     * multiplied back, effectively snapping the result to the nearest step boundary.
     *
     * @param BigNumber    $amount       The amount to scale and round.
     * @param Currency     $currency     The target currency, used to determine the scale.
     * @param RoundingMode $roundingMode The rounding mode to apply during scaling.
     *
     * @return BigDecimal The amount scaled to the currency's default fraction digits and rounded to the step.
     */
    #[Override()]
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        $scale = $currency->getDefaultFractionDigits();

        /** @var int<0, max> $scale */
        if ($this->step === 1) {
            return $amount->toScale($scale, $roundingMode);
        }

        return $amount
            ->toBigRational()
            ->dividedBy($this->step)
            ->toScale($scale, $roundingMode)
            ->multipliedBy($this->step);
    }

    /**
     * Returns the cash rounding step used by this context.
     *
     * @return int The step in minor units.
     */
    #[Override()]
    public function getStep(): int
    {
        return $this->step;
    }

    /**
     * Returns whether this context uses a fixed scale.
     *
     * Always returns true because the scale is determined by the currency's default fraction digits.
     *
     * @return bool Always true.
     */
    #[Override()]
    public function isFixedScale(): bool
    {
        return true;
    }

    /**
     * Returns whether the given context is equivalent to this context.
     *
     * Two {@see CashContext} instances are equivalent when they share the same step value.
     *
     * @param Context $other The context to compare against.
     *
     * @return bool True if {@see $other} is a {@see CashContext} with the same step, false otherwise.
     */
    #[Override()]
    public function isEquivalentTo(Context $other): bool
    {
        return $other instanceof self
            && $other->getStep() === $this->step;
    }

    /**
     * Returns whether the given step is valid for cash rounding.
     *
     * A valid step must be at least 1 and composed solely of factors of 2 and 5 (e.g. 1, 2, 4, 5, 10, 20, 25, 50).
     * This ensures the step aligns with standard denominations used in physical currency rounding.
     *
     * @param int $step The step to validate.
     *
     * @return bool True if the step is valid, false otherwise.
     */
    private function isValidStep(int $step): bool
    {
        if ($step < 1) {
            return false;
        }

        while ($step % 2 === 0) {
            $step /= 2;
        }

        while ($step % 5 === 0) {
            $step /= 5;
        }

        return $step === 1;
    }
}
