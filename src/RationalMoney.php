<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

use Cline\Math\BigNumber;
use Cline\Math\BigRational;
use Cline\Math\Exception\MathException;
use Cline\Money\Exception\MoneyMismatchException;
use Override;
use Stringable;

use function is_int;

/**
 * An exact monetary amount, represented as a rational number. This class is immutable.
 *
 * This is used to represent intermediate calculation results, and may not be exactly convertible to a decimal amount
 * with a finite number of digits. The final conversion to a Money may require rounding.
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class RationalMoney extends AbstractMoney implements Stringable
{
    /**
     * @param BigRational $amount   The amount.
     * @param Currency    $currency The currency.
     */
    public function __construct(
        private BigRational $amount,
        private Currency $currency,
    ) {}

    /**
     * Returns a non-localized string representation of this RationalMoney, e.g. "EUR 1/3".
     */
    #[Override()]
    public function __toString(): string
    {
        return $this->currency.' '.$this->amount;
    }

    /**
     * Convenience factory method.
     *
     * @param BigNumber|int|string $amount   The monetary amount.
     * @param Currency|int|string  $currency The Currency instance, ISO currency code or ISO numeric currency code.
     */
    public static function of(BigNumber|int|string $amount, Currency|string|int $currency): self
    {
        $amount = BigRational::of($amount);

        if (!$currency instanceof Currency) {
            $currency = is_int($currency)
                ? Currency::ofNumericCode($currency)
                : Currency::of($currency);
        }

        return new self($amount, $currency);
    }

    /**
     * Returns a RationalMoney with zero value, in the given currency.
     *
     * @param Currency|string $currency The Currency instance or ISO currency code.
     */
    public static function zero(Currency|string $currency): self
    {
        if (!$currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        return new self(BigRational::zero(), $currency);
    }

    /**
     * Returns the monetary amount as a BigRational.
     */
    #[Override()]
    public function getAmount(): BigRational
    {
        return $this->amount;
    }

    /**
     * Returns the currency of this money.
     */
    #[Override()]
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Returns the sum of this RationalMoney and the given amount.
     *
     * @param AbstractMoney|BigNumber|int|string $that The money or amount to add.
     *
     * @throws MathException          If the argument is not a valid number.
     * @throws MoneyMismatchException If the argument is a money in another currency.
     */
    public function plus(AbstractMoney|BigNumber|int|string $that): self
    {
        $that = $this->getAmountOf($that);
        $amount = $this->amount->plus($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the difference of this RationalMoney and the given amount.
     *
     * @param AbstractMoney|BigNumber|int|string $that The money or amount to subtract.
     *
     * @throws MathException          If the argument is not a valid number.
     * @throws MoneyMismatchException If the argument is a money in another currency.
     */
    public function minus(AbstractMoney|BigNumber|int|string $that): self
    {
        $that = $this->getAmountOf($that);
        $amount = $this->amount->minus($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the product of this RationalMoney and the given number.
     *
     * @param BigNumber|int|string $that The multiplier.
     *
     * @throws MathException If the argument is not a valid number.
     */
    public function multipliedBy(BigNumber|int|string $that): self
    {
        $amount = $this->amount->multipliedBy($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the result of the division of this RationalMoney by the given number.
     *
     * @param BigNumber|int|string $that The divisor.
     *
     * @throws MathException If the argument is not a valid number.
     */
    public function dividedBy(BigNumber|int|string $that): self
    {
        $amount = $this->amount->dividedBy($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns a copy of this BigRational, with the amount simplified.
     */
    public function simplified(): self
    {
        return new self($this->amount->simplified(), $this->currency);
    }

    /**
     * Returns this RationalMoney unchanged, as it is already in rational form.
     */
    #[Override()]
    protected function toRational(): self
    {
        return $this;
    }
}
