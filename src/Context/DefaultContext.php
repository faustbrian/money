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
use Override;

/**
 * Adjusts a number to the default scale for the currency.
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class DefaultContext implements Context
{
    /**
     * Applies this context to a rational amount, scaling to the currency's default fraction digits.
     *
     * @param BigNumber    $amount       The amount to scale.
     * @param Currency     $currency     The target currency, used to determine the scale.
     * @param RoundingMode $roundingMode The rounding mode to apply during scaling.
     *
     * @return BigDecimal The amount scaled to the currency's default fraction digits.
     */
    #[Override()]
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        $scale = $currency->getDefaultFractionDigits();
        /** @var int<0, max> $scale */

        return $amount->toScale($scale, $roundingMode);
    }

    /**
     * Returns the step used by this context.
     *
     * Always returns 1 because no cash rounding is applied.
     *
     * @return int Always 1.
     */
    #[Override()]
    public function getStep(): int
    {
        return 1;
    }

    /**
     * Returns whether this context uses a fixed scale.
     *
     * Always returns true because the scale is determined by the currency's default fraction digits,
     * which are fixed per currency.
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
     * Two {@see DefaultContext} instances are always considered equivalent since the context
     * carries no configuration state.
     *
     * @param Context $other The context to compare against.
     *
     * @return bool True if {@see $other} is also a {@see DefaultContext}, false otherwise.
     */
    #[Override()]
    public function isEquivalentTo(Context $other): bool
    {
        return $other instanceof self;
    }
}
