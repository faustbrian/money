<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

use Cline\Math\BigDecimal;
use Cline\Math\BigInteger;
use Cline\Math\BigNumber;
use Cline\Math\BigRational;
use Cline\Math\Exception\MathException;
use Cline\Math\Exception\NumberFormatException;
use Cline\Math\Exception\RoundingNecessaryException;
use Cline\Math\RoundingMode;
use Cline\Money\Context\DefaultContext;
use Cline\Money\Exception\ContextMismatchException;
use Cline\Money\Exception\EmptyAllocationRatiosException;
use Cline\Money\Exception\InvalidSplitPartsException;
use Cline\Money\Exception\MoneyMismatchException;
use Cline\Money\Exception\NegativeAllocationRatioException;
use Cline\Money\Exception\UnknownCurrencyException;
use Cline\Money\Exception\ZeroAllocationRatiosException;
use Cline\Money\Formatter\MoneyExactFormatter;
use Cline\Money\Formatter\MoneyLocaleFormatter;
use Override;
use Stringable;

use function array_fill;
use function array_map;
use function array_sum;
use function array_values;
use function intdiv;
use function is_float;
use function is_int;

/**
 * A monetary value in a given currency. This class is immutable.
 *
 * Money has an amount, a currency, and a context. The context defines the scale of the amount, and an optional cash
 * rounding step, for monies that do not have coins or notes for their smallest units.
 *
 * All operations on a Money return another Money with the same context. The available contexts are:
 *
 * - DefaultContext handles monies with the default scale for the currency.
 * - CashContext is similar to DefaultContext, but supports a cash rounding step.
 * - CustomContext handles monies with a custom scale and optionally step.
 * - AutoContext automatically adjusts the scale of the money to the minimum required.
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class Money extends AbstractMoney implements Stringable
{
    /**
     * @param BigDecimal $amount   The amount.
     * @param Currency   $currency The currency.
     * @param Context    $context  The context that defines the capability of this Money.
     */
    private function __construct(
        private BigDecimal $amount,
        private Currency $currency,
        private Context $context,
    ) {}

    /**
     * Returns a non-localized string representation of this Money, e.g. "EUR 23.00".
     */
    #[Override()]
    public function __toString(): string
    {
        return $this->currency.' '.$this->amount;
    }

    /**
     * Returns the minimum of the given monies.
     *
     * If several monies are equal to the minimum value, the first one is returned.
     *
     * @param self $money     The first money.
     * @param self ...$monies The subsequent monies.
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency.
     */
    public static function min(self $money, self ...$monies): self
    {
        $min = $money;

        foreach ($monies as $money) {
            if (!$money->isLessThan($min)) {
                continue;
            }

            $min = $money;
        }

        return $min;
    }

    /**
     * Returns the maximum of the given monies.
     *
     * If several monies are equal to the maximum value, the first one is returned.
     *
     * @param self $money     The first money.
     * @param self ...$monies The subsequent monies.
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency.
     */
    public static function max(self $money, self ...$monies): self
    {
        $max = $money;

        foreach ($monies as $money) {
            if (!$money->isGreaterThan($max)) {
                continue;
            }

            $max = $money;
        }

        return $max;
    }

    /**
     * Returns the total of the given monies.
     *
     * The monies must share the same currency and context.
     *
     * @param self $money     The first money.
     * @param self ...$monies The subsequent monies.
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency and context.
     */
    public static function total(self $money, self ...$monies): self
    {
        $total = $money;

        foreach ($monies as $money) {
            $total = $total->plus($money);
        }

        return $total;
    }

    /**
     * Creates a Money from a rational amount, a currency, and a context.
     *
     * @param BigNumber    $amount       The amount.
     * @param Currency     $currency     The currency.
     * @param Context      $context      The context.
     * @param RoundingMode $roundingMode An optional rounding mode if the amount does not fit the context.
     *
     * @throws RoundingNecessaryException If RoundingMode::Unnecessary is used but rounding is necessary.
     */
    public static function create(BigNumber $amount, Currency $currency, Context $context, RoundingMode $roundingMode = RoundingMode::Unnecessary): self
    {
        $amount = $context->applyTo($amount, $currency, $roundingMode);

        return new self($amount, $currency, $context);
    }

    /**
     * Returns a Money of the given amount and currency.
     *
     * By default, the money is created with a DefaultContext. This means that the amount is scaled to match the
     * currency's default fraction digits. For example, `Money::of('2.5', 'USD')` will yield `USD 2.50`.
     * If the amount cannot be safely converted to this scale, an exception is thrown.
     *
     * To override this behaviour, a Context instance can be provided.
     * Operations on this Money return a Money with the same context.
     *
     * @param BigNumber|float|int|string $amount       The monetary amount.
     * @param Currency|int|string        $currency     The Currency instance, ISO currency code or ISO numeric currency code.
     * @param null|Context               $context      An optional Context.
     * @param RoundingMode               $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     */
    public static function of(
        BigNumber|int|float|string $amount,
        Currency|string|int $currency,
        ?Context $context = null,
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): self {
        if (!$currency instanceof Currency) {
            $currency = is_int($currency)
                ? Currency::ofNumericCode($currency)
                : Currency::of($currency);
        }

        if (!$context instanceof Context) {
            $context = new DefaultContext();
        }

        $amount = BigNumber::of(self::normalizeFactoryNumber($amount));

        return self::create($amount, $currency, $context, $roundingMode);
    }

    /**
     * Returns a Money from a number of minor units.
     *
     * By default, the money is created with a DefaultContext. This means that the amount is scaled to match the
     * currency's default fraction digits. For example, `Money::ofMinor(1234, 'USD')` will yield `USD 12.34`.
     * If the amount cannot be safely converted to this scale, an exception is thrown.
     *
     * @param BigNumber|float|int|string $minorAmount  The amount, in minor currency units.
     * @param Currency|int|string        $currency     The Currency instance, ISO currency code or ISO numeric currency code.
     * @param null|Context               $context      An optional Context.
     * @param RoundingMode               $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     */
    public static function ofMinor(
        BigNumber|int|float|string $minorAmount,
        Currency|string|int $currency,
        ?Context $context = null,
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): self {
        if (!$currency instanceof Currency) {
            $currency = is_int($currency)
                ? Currency::ofNumericCode($currency)
                : Currency::of($currency);
        }

        if (!$context instanceof Context) {
            $context = new DefaultContext();
        }

        $amount = BigRational::of(self::normalizeFactoryNumber($minorAmount))
            ->dividedBy(10 ** $currency->getDefaultFractionDigits());

        return self::create($amount, $currency, $context, $roundingMode);
    }

    /**
     * Returns a Money with zero value, in the given currency.
     *
     * By default, the money is created with a DefaultContext: it has the default scale for the currency.
     * A Context instance can be provided to override the default.
     *
     * @param Currency|int|string $currency The Currency instance, ISO currency code or ISO numeric currency code.
     * @param null|Context        $context  An optional context.
     */
    public static function zero(Currency|string|int $currency, ?Context $context = null): self
    {
        if (!$currency instanceof Currency) {
            $currency = is_int($currency)
                ? Currency::ofNumericCode($currency)
                : Currency::of($currency);
        }

        if (!$context instanceof Context) {
            $context = new DefaultContext();
        }

        $amount = BigDecimal::zero();

        return self::create($amount, $currency, $context);
    }

    /**
     * Returns the amount of this Money, as a BigDecimal.
     */
    #[Override()]
    public function getAmount(): BigDecimal
    {
        return $this->amount;
    }

    /**
     * Returns the amount of this Money in minor units (cents) for the currency.
     *
     * The value is returned as a BigDecimal. If this Money has a scale greater than that of the currency, the result
     * will have a non-zero scale.
     *
     * For example, `USD 1.23` will return a BigDecimal of `123`, while `USD 1.2345` will return `123.45`.
     */
    public function getMinorAmount(): BigDecimal
    {
        return $this->amount->withPointMovedRight($this->currency->getDefaultFractionDigits());
    }

    /**
     * Returns a BigInteger containing the unscaled value (all digits) of this money.
     *
     * For example, `123.4567 USD` will return a BigInteger of `1234567`.
     */
    public function getUnscaledAmount(): BigInteger
    {
        return $this->amount->getUnscaledValue();
    }

    /**
     * Returns the Currency of this Money.
     */
    #[Override()]
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Returns the Context of this Money.
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * Returns the sum of this Money and the given amount.
     *
     * If the operand is a Money, it must have the same context as this Money, or an exception is thrown.
     * This is by design, to ensure that contexts are not mixed accidentally.
     * If you do need to add a Money in a different context, you can use `plus($money->toRational())`.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param AbstractMoney|BigNumber|int|string $that         The money or amount to add.
     * @param RoundingMode                       $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException          If the argument is an invalid number or rounding is necessary.
     * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
     */
    public function plus(AbstractMoney|BigNumber|int|string $that, RoundingMode $roundingMode = RoundingMode::Unnecessary): self
    {
        $amount = $this->getAmountOf($that);

        if ($that instanceof self) {
            $this->checkContext($that->getContext(), __FUNCTION__);

            if ($this->context->isFixedScale()) {
                return new self($this->amount->plus($that->amount), $this->currency, $this->context);
            }
        }

        $amount = $this->amount->toBigRational()->plus($amount);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the difference of this Money and the given amount.
     *
     * If the operand is a Money, it must have the same context as this Money, or an exception is thrown.
     * This is by design, to ensure that contexts are not mixed accidentally.
     * If you do need to subtract a Money in a different context, you can use `minus($money->toRational())`.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param AbstractMoney|BigNumber|int|string $that         The money or amount to subtract.
     * @param RoundingMode                       $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException          If the argument is an invalid number or rounding is necessary.
     * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
     */
    public function minus(AbstractMoney|BigNumber|int|string $that, RoundingMode $roundingMode = RoundingMode::Unnecessary): self
    {
        $amount = $this->getAmountOf($that);

        if ($that instanceof self) {
            $this->checkContext($that->getContext(), __FUNCTION__);

            if ($this->context->isFixedScale()) {
                return new self($this->amount->minus($that->amount), $this->currency, $this->context);
            }
        }

        $amount = $this->amount->toBigRational()->minus($amount);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the product of this Money and the given number.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param BigNumber|int|string $that         The multiplier.
     * @param RoundingMode         $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException If the argument is an invalid number or rounding is necessary.
     */
    public function multipliedBy(BigNumber|int|string $that, RoundingMode $roundingMode = RoundingMode::Unnecessary): self
    {
        $amount = $this->amount->toBigRational()->multipliedBy($that);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the result of the division of this Money by the given number.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param BigNumber|int|string $that         The divisor.
     * @param RoundingMode         $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException If the argument is an invalid number or is zero, or rounding is necessary.
     */
    public function dividedBy(BigNumber|int|string $that, RoundingMode $roundingMode = RoundingMode::Unnecessary): self
    {
        $amount = $this->amount->toBigRational()->dividedBy($that);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the quotient of the division of this Money by the given number.
     *
     * The given number must be a integer value. The resulting Money has the same context as this Money.
     * This method can serve as a basis for a money allocation algorithm.
     *
     * @param BigNumber|int|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @throws MathException If the divisor cannot be converted to a BigInteger.
     */
    public function quotient(BigNumber|int|string $that): self
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale = $this->amount->getScale();
        $amount = $this->amount->withPointMovedRight($scale);
        $amount = $amount->dividedBy($step, $amount->getScale(), RoundingMode::Unnecessary);

        $q = $amount->quotient($that);
        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);

        return new self($q, $this->currency, $this->context);
    }

    /**
     * Returns the quotient and the remainder of the division of this Money by the given number.
     *
     * The given number must be an integer value. The resulting monies have the same context as this Money.
     * This method can serve as a basis for a money allocation algorithm.
     *
     * @param BigNumber|int|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @throws MathException     If the divisor cannot be converted to a BigInteger.
     * @return array{self, self} The quotient and the remainder.
     */
    public function quotientAndRemainder(BigNumber|int|string $that): array
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale = $this->amount->getScale();
        $amount = $this->amount->withPointMovedRight($scale);
        $amount = $amount->dividedBy($step, $amount->getScale(), RoundingMode::Unnecessary);

        [$q, $r] = $amount->quotientAndRemainder($that);

        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);
        $r = $r->multipliedBy($step)->withPointMovedLeft($scale);

        $quotient = new self($q, $this->currency, $this->context);
        $remainder = new self($r, $this->currency, $this->context);

        return [$quotient, $remainder];
    }

    /**
     * Allocates this Money according to a list of ratios.
     *
     * If the allocation yields a remainder, its amount is split over the first monies in the list,
     * so that the total of the resulting monies is always equal to this Money.
     *
     * For example, given a `USD 49.99` money in the default context,
     * `allocate(1, 2, 3, 4)` returns [`USD 5.00`, `USD 10.00`, `USD 15.00`, `USD 19.99`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param int ...$ratios The ratios.
     *
     * @throws EmptyAllocationRatiosException   If no ratios are provided.
     * @throws NegativeAllocationRatioException If any ratio is negative.
     * @throws ZeroAllocationRatiosException    If all ratios sum to zero.
     * @return array<self>
     */
    public function allocate(int ...$ratios): array
    {
        if ($ratios === []) {
            throw EmptyAllocationRatiosException::forMethod('allocate()');
        }

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw NegativeAllocationRatioException::forMethod('allocate()');
            }
        }

        $total = array_sum($ratios);

        if ($total === 0) {
            throw ZeroAllocationRatiosException::forMethod('allocate()');
        }

        $step = $this->context->getStep();

        $monies = [];

        $unit = BigDecimal::ofUnscaledValue($step, $this->amount->getScale());
        $unit = new self($unit, $this->currency, $this->context);

        if ($this->isNegative()) {
            $unit = $unit->negated();
        }

        $remainder = $this;

        foreach ($ratios as $ratio) {
            $money = $this->multipliedBy($ratio)->quotient($total);
            $remainder = $remainder->minus($money);
            $monies[] = $money;
        }

        foreach ($monies as $key => $money) {
            if ($remainder->isZero()) {
                break;
            }

            $monies[$key] = $money->plus($unit);
            $remainder = $remainder->minus($unit);
        }

        return $monies;
    }

    /**
     * Allocates this Money according to a list of ratios.
     *
     * The remainder is also present, appended at the end of the list.
     *
     * For example, given a `USD 49.99` money in the default context,
     * `allocateWithRemainder(1, 2, 3, 4)` returns [`USD 4.99`, `USD 9.98`, `USD 14.97`, `USD 19.96`, `USD 0.09`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param int ...$ratios The ratios.
     *
     * @throws EmptyAllocationRatiosException   If no ratios are provided.
     * @throws NegativeAllocationRatioException If any ratio is negative.
     * @throws ZeroAllocationRatiosException    If all ratios sum to zero.
     * @return array<self>
     */
    public function allocateWithRemainder(int ...$ratios): array
    {
        if ($ratios === []) {
            throw EmptyAllocationRatiosException::forMethod('allocateWithRemainder()');
        }

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw NegativeAllocationRatioException::forMethod('allocateWithRemainder()');
            }
        }

        $total = array_sum($ratios);

        if ($total === 0) {
            throw ZeroAllocationRatiosException::forMethod('allocateWithRemainder()');
        }

        $ratios = $this->simplifyRatios(array_values($ratios));
        $total = array_sum($ratios);

        [, $remainder] = $this->quotientAndRemainder($total);

        $toAllocate = $this->minus($remainder);

        $monies = array_map(
            fn (int $ratio): Money => $toAllocate->multipliedBy($ratio)->dividedBy($total),
            $ratios,
        );

        $monies[] = $remainder;

        return $monies;
    }

    /**
     * Splits this Money into a number of parts.
     *
     * If the division of this Money by the number of parts yields a remainder, its amount is split over the first
     * monies in the list, so that the total of the resulting monies is always equal to this Money.
     *
     * For example, given a `USD 100.00` money in the default context,
     * `split(3)` returns [`USD 33.34`, `USD 33.33`, `USD 33.33`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param int $parts The number of parts. Must be greater than or equal to 1.
     *
     * @throws InvalidSplitPartsException If $parts is less than 1.
     * @return array<self>
     */
    public function split(int $parts): array
    {
        if ($parts < 1) {
            throw InvalidSplitPartsException::forMethod('split()');
        }

        return $this->allocate(...array_fill(0, $parts, 1));
    }

    /**
     * Splits this Money into a number of parts and a remainder.
     *
     * For example, given a `USD 100.00` money in the default context,
     * `splitWithRemainder(3)` returns [`USD 33.33`, `USD 33.33`, `USD 33.33`, `USD 0.01`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param int $parts The number of parts. Must be greater than or equal to 1.
     *
     * @throws InvalidSplitPartsException If $parts is less than 1.
     * @return array<self>
     */
    public function splitWithRemainder(int $parts): array
    {
        if ($parts < 1) {
            throw InvalidSplitPartsException::forMethod('splitWithRemainder()');
        }

        return $this->allocateWithRemainder(...array_fill(0, $parts, 1));
    }

    /**
     * Returns a Money whose value is the absolute value of this Money.
     *
     * The resulting Money has the same context as this Money.
     */
    public function abs(): self
    {
        return new self($this->amount->abs(), $this->currency, $this->context);
    }

    /**
     * Returns a Money whose value is the negated value of this Money.
     *
     * The resulting Money has the same context as this Money.
     */
    public function negated(): self
    {
        return new self($this->amount->negated(), $this->currency, $this->context);
    }

    /**
     * Formats this Money to the given locale.
     *
     * Note that this method uses MoneyLocaleFormatter, which in turn internally uses NumberFormatter, which represents values using floating
     * point arithmetic, so discrepancies can appear when formatting very large monetary values.
     *
     * @param string $locale           The locale to format to, for example 'fr_FR' or 'en_US'.
     * @param bool   $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     */
    public function formatToLocale(string $locale, bool $allowWholeNumber = false): string
    {
        return new MoneyLocaleFormatter($locale, $allowWholeNumber)->format($this);
    }

    /**
     * Formats this Money without converting its amount to float.
     *
     * This preserves exact decimal digits for very large values.
     *
     * @param string $separator The separator between currency code and amount.
     */
    public function formatExact(string $separator = ' '): string
    {
        return new MoneyExactFormatter($separator)->format($this);
    }

    /**
     * Converts this Money to a RationalMoney with the same currency.
     *
     * Used internally by AbstractMoney to satisfy the Monetary interface and
     * to allow mixing Money operands with different contexts via MoneyBag.
     */
    #[Override()]
    protected function toRational(): RationalMoney
    {
        return new RationalMoney($this->amount->toBigRational(), $this->currency);
    }

    /**
     * Normalizes factory inputs to types accepted by cline/math.
     */
    private static function normalizeFactoryNumber(BigNumber|int|float|string $value): BigNumber|int|string
    {
        return is_float($value) ? (string) $value : $value;
    }

    /**
     * Asserts that the given context is equivalent to this money's context.
     *
     * @param Context $context The context to check against this money's context.
     * @param string  $method  The name of the calling method, included in the exception message.
     *
     * @throws ContextMismatchException If the given context is not equivalent to this money's context.
     */
    private function checkContext(Context $context, string $method): void
    {
        if (!$this->context->isEquivalentTo($context)) {
            throw ContextMismatchException::forMethod($method);
        }
    }

    /**
     * Reduces a list of ratios by dividing each by their greatest common divisor.
     *
     * Simplifying ratios before allocation avoids intermediate values that are larger than necessary,
     * which keeps the arithmetic efficient and the remainder as small as possible.
     *
     * @param non-empty-list<int> $ratios
     *
     * @return non-empty-list<int>
     */
    private function simplifyRatios(array $ratios): array
    {
        $gcd = $this->gcdOfMultipleInt($ratios);

        return array_map(fn (int $ratio): int => intdiv($ratio, $gcd), $ratios);
    }

    /**
     * Returns the greatest common divisor of a list of integers.
     *
     * @param non-empty-list<int> $values
     */
    private function gcdOfMultipleInt(array $values): int
    {
        $values = array_map(BigInteger::of(...), $values);

        return BigInteger::gcdAll(...$values)->toInt();
    }
}
