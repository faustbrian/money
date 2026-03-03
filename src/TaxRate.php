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
use Cline\Money\Exception\NegativeTaxRateException;
use JsonSerializable;
use Override;
use Stringable;

use function is_float;

/**
 * An immutable tax rate represented as a percentage.
 *
 * The rate is stored as a BigRational for arbitrary precision.
 * Negative rates are rejected; rates above 100% are allowed (luxury taxes).
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class TaxRate implements JsonSerializable, Stringable
{
    /**
     * @param BigRational $percentage The tax rate as a percentage (e.g. 25.5 for 25.5%).
     */
    private function __construct(
        private BigRational $percentage,
    ) {}

    /**
     * Returns a string representation of this tax rate, e.g. "25.5%".
     */
    #[Override()]
    public function __toString(): string
    {
        return $this->percentage->toBigDecimal().'%';
    }

    /**
     * Creates a TaxRate from the given percentage value.
     *
     * @param BigNumber|float|int|string $percentage The tax rate as a percentage (e.g. 25.5 for 25.5%).
     *
     * @throws NegativeTaxRateException If the percentage is negative.
     * @return self                     A new TaxRate instance for the given percentage.
     */
    public static function of(BigNumber|int|float|string $percentage): self
    {
        $value = is_float($percentage)
            ? BigRational::of((string) $percentage)
            : BigRational::of($percentage);

        if ($value->isNegative()) {
            throw NegativeTaxRateException::forRate((string) $value);
        }

        return new self($value);
    }

    /**
     * Returns a zero tax rate.
     *
     * @return self A TaxRate representing 0%.
     */
    public static function zero(): self
    {
        return new self(BigRational::of(0));
    }

    /**
     * Returns the tax rate as a percentage.
     *
     * For a 25.5% rate, this returns a BigRational representing 25.5.
     *
     * @return BigRational The percentage value (e.g. 25.5 for a 25.5% rate).
     */
    public function getPercentage(): BigRational
    {
        return $this->percentage;
    }

    /**
     * Returns the tax rate as a multiplier.
     *
     * For a 25.5% rate, this returns a BigRational representing 0.255.
     *
     * @return BigRational The multiplier value (e.g. 0.255 for a 25.5% rate).
     */
    public function getMultiplier(): BigRational
    {
        return $this->percentage->dividedBy(100);
    }

    /**
     * Returns whether this tax rate is zero.
     *
     * @return bool True if the rate is 0%; false otherwise.
     */
    public function isZero(): bool
    {
        return $this->percentage->isZero();
    }

    /**
     * Returns whether this tax rate is equal to the given tax rate.
     *
     * @param self $that The tax rate to compare against.
     *
     * @return bool True if both rates represent the same percentage value.
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
