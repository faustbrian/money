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
 * Adjusts a number to a custom scale and optionally step.
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class CustomContext implements Context
{
    /**
     * Creates a new custom-scale context.
     *
     * @param int $scale The number of decimal places to use for monies in this context.
     * @param int $step  An optional cash rounding step. Must either divide 10^scale or be a multiple of 10^scale.
     *                   For example, scale=2 and step=5 allows 0.00, 0.05, 0.10, etc.
     *                   And scale=2 and step=1000 allows 0.00, 10.00, 20.00, etc.
     *                   Defaults to 1, meaning no cash rounding is applied.
     *
     * @throws InvalidStepException If the step is less than 1 or does not divide evenly into or from 10^scale.
     */
    public function __construct(
        private int $scale,
        private int $step = 1,
    ) {
        if (!$this->isValidStep($scale, $step)) {
            throw InvalidStepException::forStep($step);
        }
    }

    /**
     * Applies this context to a rational amount, scaling to the configured number of decimal places
     * and optionally rounding to the nearest step.
     *
     * When the step is greater than 1, the amount is divided by the step, scaled, then
     * multiplied back, snapping the result to the nearest step boundary.
     *
     * @param BigNumber    $amount       The amount to scale and round.
     * @param Currency     $currency     The target currency (unused; scale is determined by this context).
     * @param RoundingMode $roundingMode The rounding mode to apply during scaling.
     *
     * @return BigDecimal The amount scaled to the configured number of decimal places.
     */
    #[Override()]
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        $scale = $this->scale;

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
     * Returns 1 when no cash rounding is configured.
     *
     * @return int The step value.
     */
    #[Override()]
    public function getStep(): int
    {
        return $this->step;
    }

    /**
     * Returns whether this context uses a fixed scale.
     *
     * Always returns true because the scale is explicitly configured at construction time.
     *
     * @return bool Always true.
     */
    #[Override()]
    public function isFixedScale(): bool
    {
        return true;
    }

    /**
     * Returns the number of decimal places used by this context.
     *
     * @return int The scale.
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * Returns whether the given context is equivalent to this context.
     *
     * Two {@see CustomContext} instances are equivalent when they share the same scale and step values.
     *
     * @param Context $other The context to compare against.
     *
     * @return bool True if {@see $other} is a {@see CustomContext} with matching scale and step, false otherwise.
     */
    #[Override()]
    public function isEquivalentTo(Context $other): bool
    {
        return $other instanceof self
            && $other->getScale() === $this->scale
            && $other->getStep() === $this->step;
    }

    /**
     * Returns whether the given step is valid for the given scale.
     *
     * A step is valid when it is at least 1 and either divides evenly into 10^scale (sub-unit
     * rounding) or 10^scale divides evenly into the step (super-unit rounding).
     *
     * @param int $scale The number of decimal places.
     * @param int $step  The step to validate.
     *
     * @return bool True if the step is valid for the given scale, false otherwise.
     */
    private function isValidStep(int $scale, int $step): bool
    {
        if ($step < 1) {
            return false;
        }

        $power = 10 ** $scale;

        return $power % $step === 0 || $step % $power === 0;
    }
}
