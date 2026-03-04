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
use Cline\Money\Exception\ExcessiveDiscountRateException;
use Cline\Money\Exception\NegativeDiscountRateException;
use JsonSerializable;
use Override;
use Stringable;

use function is_float;

/**
 * An immutable discount rate represented as a percentage.
 *
 * The rate is stored as a BigRational for arbitrary precision.
 * Negative rates are rejected; rates above 100% are also rejected.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class DiscountRate implements JsonSerializable, Stringable
{
    /**
     * @param BigRational $percentage The discount rate as a percentage (e.g. 20 for 20%).
     */
    private function __construct(
        private BigRational $percentage,
    ) {}

    /**
     * Returns a string representation of this discount rate, e.g. "20%".
     */
    #[Override()]
    public function __toString(): string
    {
        return $this->percentage->toBigDecimal().'%';
    }

    /**
     * Creates a DiscountRate from the given percentage value.
     *
     * @param BigNumber|float|int|string $percentage The discount rate as a percentage (e.g. 20 for 20%).
     *
     * @throws ExcessiveDiscountRateException If the percentage exceeds 100.
     * @throws NegativeDiscountRateException  If the percentage is negative.
     */
    public static function of(BigNumber|int|float|string $percentage): self
    {
        $value = is_float($percentage)
            ? BigRational::of((string) $percentage)
            : BigRational::of($percentage);

        if ($value->isNegative()) {
            throw NegativeDiscountRateException::forRate((string) $value);
        }

        if ($value->isGreaterThan(100)) {
            throw ExcessiveDiscountRateException::forRate((string) $value);
        }

        return new self($value);
    }

    /**
     * Returns a zero discount rate.
     */
    public static function zero(): self
    {
        return new self(BigRational::of(0));
    }

    /**
     * Returns the discount rate as a percentage.
     *
     * For a 20% rate, this returns a BigRational representing 20.
     */
    public function getPercentage(): BigRational
    {
        return $this->percentage;
    }

    /**
     * Returns the discount rate as a multiplier.
     *
     * For a 20% rate, this returns a BigRational representing 0.2.
     */
    public function getMultiplier(): BigRational
    {
        return $this->percentage->dividedBy(100);
    }

    /**
     * Returns whether this discount rate is zero.
     */
    public function isZero(): bool
    {
        return $this->percentage->isZero();
    }

    /**
     * Returns whether this discount rate is equal to the given discount rate.
     */
    public function isEqualTo(self $that): bool
    {
        return $this->percentage->isEqualTo($that->percentage);
    }

    /**
     * Returns the percentage value for JSON serialization.
     *
     * @return string The percentage as a string.
     */
    #[Override()]
    public function jsonSerialize(): string
    {
        return (string) $this->percentage->toBigDecimal();
    }
}
