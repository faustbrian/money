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
 * An immutable result of a tax calculation, containing net, gross, and tax amounts.
 *
 * The invariant net + tax = gross always holds.
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class TaxResult implements JsonSerializable, Stringable
{
    /**
     * @param Money   $net   The net amount (before tax).
     * @param Money   $gross The gross amount (after tax).
     * @param Money   $tax   The tax amount (gross - net).
     * @param TaxRate $rate  The tax rate used for this calculation.
     */
    private function __construct(
        private Money $net,
        private Money $gross,
        private Money $tax,
        private TaxRate $rate,
    ) {}

    /**
     * Returns a string representation of this tax result.
     *
     * Format: "net=EUR 100.00 gross=EUR 125.50 tax=EUR 25.50 rate=25.5%"
     */
    #[Override()]
    public function __toString(): string
    {
        return sprintf(
            'net=%s gross=%s tax=%s rate=%s',
            $this->net,
            $this->gross,
            $this->tax,
            $this->rate,
        );
    }

    /**
     * Creates a TaxResult from the given components.
     *
     * @param Money   $net   The net amount (before tax).
     * @param Money   $gross The gross amount (after tax).
     * @param Money   $tax   The tax amount (gross - net).
     * @param TaxRate $rate  The tax rate used for this calculation.
     *
     * @return self A new TaxResult containing all four components.
     */
    public static function create(Money $net, Money $gross, Money $tax, TaxRate $rate): self
    {
        return new self($net, $gross, $tax, $rate);
    }

    /**
     * Returns the net amount (before tax).
     */
    public function getNet(): Money
    {
        return $this->net;
    }

    /**
     * Returns the gross amount (after tax).
     */
    public function getGross(): Money
    {
        return $this->gross;
    }

    /**
     * Returns the tax amount.
     */
    public function getTax(): Money
    {
        return $this->tax;
    }

    /**
     * Returns the tax rate used for this calculation.
     */
    public function getRate(): TaxRate
    {
        return $this->rate;
    }

    /**
     * Returns the tax result components for JSON serialization.
     *
     * @return array{net: Money, gross: Money, tax: Money, rate: TaxRate}
     */
    #[Override()]
    public function jsonSerialize(): array
    {
        return [
            'net' => $this->net,
            'gross' => $this->gross,
            'tax' => $this->tax,
            'rate' => $this->rate,
        ];
    }
}
