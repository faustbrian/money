<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

use Cline\Math\BigRational;
use Override;

use function array_values;

/**
 * Container for monies in different currencies.
 *
 * This class is mutable.
 * @author Brian Faust <brian@cline.sh>
 */
final class MoneyBag implements Monetary
{
    /**
     * The monies in this bag, indexed by currency code.
     *
     * @var array<string, RationalMoney>
     */
    private array $monies = [];

    /**
     * Returns the contained amount in the given currency as a RationalMoney.
     *
     * If the bag holds no amount for the given currency, a zero-value RationalMoney
     * in that currency is returned.
     *
     * @param Currency|string $currency The Currency instance, or ISO currency code.
     *
     * @return RationalMoney The accumulated amount for the given currency, or zero if absent.
     */
    public function getMoney(Currency|string $currency): RationalMoney
    {
        if ($currency instanceof Currency) {
            $currencyCode = $currency->getCurrencyCode();

            return $this->monies[$currencyCode] ?? new RationalMoney(BigRational::zero(), $currency);
        }

        return $this->monies[$currency] ?? new RationalMoney(BigRational::zero(), Currency::of($currency));
    }

    /**
     * Returns all rational monetary values held in this bag, one per currency.
     *
     * @return list<RationalMoney>
     */
    #[Override()]
    public function getMonies(): array
    {
        return array_values($this->monies);
    }

    /**
     * Adds money to this bag.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return self This instance.
     */
    public function add(Monetary $money): self
    {
        foreach ($money->getMonies() as $containedMoney) {
            $currency = $containedMoney->getCurrency();
            $currencyCode = $currency->getCurrencyCode();

            $this->monies[$currencyCode] = $this->getMoney($currency)->plus($containedMoney);
        }

        return $this;
    }

    /**
     * Subtracts money from this bag.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return self This instance.
     */
    public function subtract(Monetary $money): self
    {
        foreach ($money->getMonies() as $containedMoney) {
            $currency = $containedMoney->getCurrency();
            $currencyCode = $currency->getCurrencyCode();

            $this->monies[$currencyCode] = $this->getMoney($currency)->minus($containedMoney);
        }

        return $this;
    }
}
