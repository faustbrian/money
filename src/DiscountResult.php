<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

use JsonSerializable;
use Override;
use Stringable;

use function sprintf;

/**
 * An immutable result of a discount calculation, containing original, discounted, and savings amounts.
 *
 * The invariant original - savings = discounted always holds.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class DiscountResult implements JsonSerializable, Stringable
{
    /**
     * @param Money        $original   The original amount (before discount).
     * @param Money        $discounted The discounted amount (after discount).
     * @param Money        $savings    The savings amount (original - discounted).
     * @param DiscountRate $rate       The discount rate used for this calculation.
     */
    private function __construct(
        private Money $original,
        private Money $discounted,
        private Money $savings,
        private DiscountRate $rate,
    ) {}

    /**
     * Returns a string representation of this discount result.
     *
     * Format: "original=EUR 100.00 discounted=EUR 80.00 savings=EUR 20.00 rate=20%"
     */
    #[Override()]
    public function __toString(): string
    {
        return sprintf(
            'original=%s discounted=%s savings=%s rate=%s',
            $this->original,
            $this->discounted,
            $this->savings,
            $this->rate,
        );
    }

    /**
     * Creates a DiscountResult from the given components.
     *
     * @param Money        $original   The original amount (before discount).
     * @param Money        $discounted The discounted amount (after discount).
     * @param Money        $savings    The savings amount (original - discounted).
     * @param DiscountRate $rate       The discount rate used for this calculation.
     */
    public static function create(Money $original, Money $discounted, Money $savings, DiscountRate $rate): self
    {
        return new self($original, $discounted, $savings, $rate);
    }

    /**
     * Returns the original amount (before discount).
     */
    public function getOriginal(): Money
    {
        return $this->original;
    }

    /**
     * Returns the discounted amount (after discount).
     */
    public function getDiscounted(): Money
    {
        return $this->discounted;
    }

    /**
     * Returns the savings amount.
     */
    public function getSavings(): Money
    {
        return $this->savings;
    }

    /**
     * Returns the discount rate used for this calculation.
     */
    public function getRate(): DiscountRate
    {
        return $this->rate;
    }

    /**
     * Returns the discount result components for JSON serialization.
     *
     * @return array{original: Money, discounted: Money, savings: Money, rate: DiscountRate}
     */
    #[Override()]
    public function jsonSerialize(): array
    {
        return [
            'original' => $this->original,
            'discounted' => $this->discounted,
            'savings' => $this->savings,
            'rate' => $this->rate,
        ];
    }
}
