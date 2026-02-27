<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

use Cline\Math\BigNumber;
use Cline\Math\Exception\MathException;
use Cline\Math\Exception\RoundingNecessaryException;
use Cline\Math\RoundingMode;
use Cline\Money\Context\AutoContext;
use Cline\Money\Context\CashContext;
use Cline\Money\Context\CustomContext;
use Cline\Money\Context\DefaultContext;
use Cline\Money\Exception\CurrencyMismatchException;
use Cline\Money\Exception\MoneyMismatchException;
use JsonSerializable;
use Override;
use Stringable;

/**
 * Base class for Money and RationalMoney.
 *
 * Please consider this class sealed: extending this class yourself is not supported, and breaking changes (such as
 * adding new abstract methods) can happen at any time, even in a minor version.
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
abstract readonly class AbstractMoney implements JsonSerializable, Monetary, Stringable
{
    /**
     * Converts this money to a Money in the given Context.
     *
     * @param Context      $context      The context.
     * @param RoundingMode $roundingMode The rounding mode, if necessary.
     *
     * @throws RoundingNecessaryException If RoundingMode::Unnecessary is used but rounding is necessary.
     */
    final public function to(Context $context, RoundingMode $roundingMode = RoundingMode::Unnecessary): Money
    {
        return Money::create($this->getAmount(), $this->getCurrency(), $context, $roundingMode);
    }

    /**
     * Returns this money as a single-element list of RationalMoney instances.
     *
     * Satisfies the Monetary interface so that Money and RationalMoney instances
     * can be passed directly to MoneyBag::add() and MoneyBag::subtract() alongside
     * full MoneyBag instances. Not intended for direct use.
     *
     * @return list<RationalMoney>
     */
    #[Override()]
    final public function getMonies(): array
    {
        return [
            $this->toRational(),
        ];
    }

    /**
     * Returns the sign of this money.
     *
     * @return int -1 if the number is negative, 0 if zero, 1 if positive.
     */
    final public function getSign(): int
    {
        return $this->getAmount()->getSign();
    }

    /**
     * Returns whether this money has zero value.
     */
    final public function isZero(): bool
    {
        return $this->getAmount()->isZero();
    }

    /**
     * Returns whether this money has a negative value.
     */
    final public function isNegative(): bool
    {
        return $this->getAmount()->isNegative();
    }

    /**
     * Returns whether this money has a negative or zero value.
     */
    final public function isNegativeOrZero(): bool
    {
        return $this->getAmount()->isNegativeOrZero();
    }

    /**
     * Returns whether this money has a positive value.
     */
    final public function isPositive(): bool
    {
        return $this->getAmount()->isPositive();
    }

    /**
     * Returns whether this money has a positive or zero value.
     */
    final public function isPositiveOrZero(): bool
    {
        return $this->getAmount()->isPositiveOrZero();
    }

    /**
     * Compares this money to the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     * @return int                    [-1, 0, 1] if `$this` is less than, equal to, or greater than `$that`.
     */
    final public function compareTo(self|BigNumber|int|string $that): int
    {
        return $this->getAmount()->compareTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is equal to the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isEqualTo(self|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isEqualTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is less than the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isLessThan(self|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isLessThan($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is less than or equal to the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isLessThanOrEqualTo(self|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isLessThanOrEqualTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is greater than the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isGreaterThan(self|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isGreaterThan($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is greater than or equal to the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isGreaterThanOrEqualTo(self|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isGreaterThanOrEqualTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money's amount and currency are equal to those of the given money.
     *
     * Unlike isEqualTo(), this method only accepts a money, and returns false if the given money is in another
     * currency, instead of throwing a MoneyMismatchException.
     */
    final public function isAmountAndCurrencyEqualTo(self $that): bool
    {
        return $this->getAmount()->isEqualTo($that->getAmount())
            && $this->getCurrency()->isEqualTo($that->getCurrency());
    }

    /**
     * Serializes this money to a JSON-encodable array.
     *
     * For Money instances the serialized form includes an additional `context` key
     * describing the context type and its parameters (e.g. scale, step).
     *
     * @return array<string, mixed>
     */
    #[Override()]
    final public function jsonSerialize(): array
    {
        $serialized = [
            'amount' => (string) $this->getAmount(),
            'currency' => $this->getCurrency()->jsonSerialize(),
        ];

        if ($this instanceof Money) {
            $serialized['context'] = $this->serializeContext($this->getContext());
        }

        return $serialized;
    }

    /**
     * Returns a JSON-encodable representation of the given Context.
     *
     * The returned array always contains a `type` key identifying the context class.
     * CashContext adds `step`, CustomContext adds `scale` and `step`.
     * Unrecognised context implementations fall back to using their fully-qualified class name.
     *
     * @param Context $context The context to serialize.
     *
     * @return array<string, int|string>
     */
    public function serializeContext(Context $context): array
    {
        return match (true) {
            $context instanceof DefaultContext => ['type' => 'default'],
            $context instanceof CashContext => [
                'type' => 'cash',
                'step' => $context->getStep(),
            ],
            $context instanceof CustomContext => [
                'type' => 'custom',
                'scale' => $context->getScale(),
                'step' => $context->getStep(),
            ],
            $context instanceof AutoContext => ['type' => 'auto'],
            default => ['type' => $context::class],
        };
    }

    /**
     * Returns the monetary amount.
     */
    abstract public function getAmount(): BigNumber;

    /**
     * Returns the currency of this money.
     */
    abstract public function getCurrency(): Currency;

    /**
     * Returns the amount of the given parameter.
     *
     * If the parameter is a money, its currency is checked against this money's currency.
     *
     * @param BigNumber|int|self|string $that A money or amount.
     *
     * @throws MoneyMismatchException If currencies don't match.
     */
    final protected function getAmountOf(self|BigNumber|int|string $that): BigNumber|int|string
    {
        if ($that instanceof self) {
            if (!$that->getCurrency()->isEqualTo($this->getCurrency())) {
                throw CurrencyMismatchException::forCurrencies($this->getCurrency(), $that->getCurrency());
            }

            return $that->getAmount();
        }

        return $that;
    }

    /**
     * Converts this money object to a RationalMoney.
     */
    abstract protected function toRational(): RationalMoney;
}
