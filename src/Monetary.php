<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

/**
 * Common interface for Money, RationalMoney and MoneyBag.
 *
 * Provides a uniform way to iterate over the rational monetary values contained
 * in any monetary object, enabling MoneyBag to accept any Monetary implementation
 * without needing to know its concrete type.
 * @author Brian Faust <brian@cline.sh>
 */
interface Monetary
{
    /**
     * Returns all rational monetary values contained in this object.
     *
     * For Money and RationalMoney this returns a single-element list. For MoneyBag
     * this returns one RationalMoney per currency held in the bag.
     *
     * @return list<RationalMoney>
     */
    public function getMonies(): array;
}
