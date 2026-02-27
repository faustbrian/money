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
use Cline\Money\Exception\UnsupportedRoundingModeException;
use Override;

/**
 * Automatically adjusts the scale of a number to the strict minimum.
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class AutoContext implements Context
{
    /**
     * Applies this context to a rational amount, stripping trailing zeros from the result.
     *
     * Only {@see RoundingMode::Unnecessary} is accepted; any other rounding mode indicates a
     * caller assumption about scale that is incompatible with this context's variable-scale
     * behaviour.
     *
     * @param BigNumber    $amount       The amount to scale.
     * @param Currency     $currency     The target currency (unused; scale is inferred from the value).
     * @param RoundingMode $roundingMode Must be {@see RoundingMode::Unnecessary}.
     *
     * @throws UnsupportedRoundingModeException If a rounding mode other than
     *                                          {@see RoundingMode::Unnecessary} is provided.
     * @return BigDecimal                       The amount with all trailing fractional zeros removed.
     */
    #[Override()]
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        if ($roundingMode !== RoundingMode::Unnecessary) {
            throw UnsupportedRoundingModeException::forAutoContext();
        }

        return $amount->toBigDecimal()->strippedOfTrailingZeros();
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
     * Always returns false because the scale varies with each value.
     *
     * @return bool Always false.
     */
    #[Override()]
    public function isFixedScale(): bool
    {
        return false;
    }

    /**
     * Returns whether the given context is equivalent to this context.
     *
     * Two {@see AutoContext} instances are always considered equivalent since the context
     * carries no configuration state.
     *
     * @param Context $other The context to compare against.
     *
     * @return bool True if {@see $other} is also an {@see AutoContext}, false otherwise.
     */
    #[Override()]
    public function isEquivalentTo(Context $other): bool
    {
        return $other instanceof self;
    }
}
