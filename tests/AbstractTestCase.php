<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use Cline\Math\BigDecimal;
use Cline\Math\BigNumber;
use Cline\Math\BigRational;
use Cline\Money\Context;
use Cline\Money\Currency;
use Cline\Money\CurrencyType;
use Cline\Money\Money;
use Cline\Money\MoneyBag;
use Cline\Money\RationalMoney;
use PHPUnit\Framework\TestCase;

use function array_is_list;
use function array_map;
use function is_string;
use function str_ends_with;

/**
 * Base class for money tests.
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
abstract class AbstractTestCase extends TestCase
{
    final protected static function assertBigDecimalIs(string $expected, BigDecimal $actual): void
    {
        self::assertSame($expected, (string) $actual);
    }

    /**
     * @param string $expectedAmount   The expected decimal amount.
     * @param string $expectedCurrency The expected currency code.
     * @param Money  $actual           The money to test.
     */
    final protected static function assertMoneyEquals(string $expectedAmount, string $expectedCurrency, Money $actual): void
    {
        self::assertSame($expectedCurrency, (string) $actual->getCurrency());
        self::assertSame($expectedAmount, (string) $actual->getAmount());
    }

    /**
     * @param string       $expected The expected string representation of the Money.
     * @param Money        $actual   The money to test.
     * @param null|Context $context  An optional context to check against the Money.
     */
    final protected static function assertMoneyIs(string $expected, Money $actual, ?Context $context = null): void
    {
        self::assertSame($expected, (string) $actual);

        if (!$context instanceof Context) {
            return;
        }

        self::assertEquals($context, $actual->getContext());
    }

    /**
     * @param array<string> $expected
     * @param array<Money>  $actual
     */
    final protected static function assertMoniesAre(array $expected, array $actual): void
    {
        $actual = array_map(
            fn (Money $money): string => (string) $money,
            $actual,
        );

        self::assertSame($expected, $actual);
    }

    final protected static function assertBigNumberEquals(string $expected, BigNumber $actual): void
    {
        self::assertTrue($actual->isEqualTo($expected), $actual.' != '.$expected);
    }

    final protected static function assertMoneyBagContains(array $expectedAmounts, MoneyBag $moneyBag): void
    {
        // Test getMoney() on each currency
        foreach ($expectedAmounts as $currencyCode => $expectedAmount) {
            $actualAmount = $moneyBag->getMoney($currencyCode)->getAmount();

            self::assertInstanceOf(BigRational::class, $actualAmount);
            self::assertBigNumberEquals($expectedAmount, $actualAmount);
        }

        // Test getMonies()
        $actualMonies = $moneyBag->getMonies();
        self::assertTrue(array_is_list($actualMonies));

        foreach ($actualMonies as $actualMoney) {
            self::assertInstanceOf(RationalMoney::class, $actualMoney);
            $currencyCode = $actualMoney->getCurrency()->getCurrencyCode();
            self::assertBigNumberEquals($expectedAmounts[$currencyCode], $actualMoney->getAmount());
        }
    }

    final protected static function assertRationalMoneyEquals(string $expected, RationalMoney $actual): void
    {
        self::assertSame($expected, (string) $actual);
    }

    final protected static function assertCurrencyEquals(string $currencyCode, int $numericCode, string $name, int $defaultFractionDigits, CurrencyType $currencyType, Currency $currency): void
    {
        self::assertSame($currencyCode, $currency->getCurrencyCode());
        self::assertSame($numericCode, $currency->getNumericCode());
        self::assertSame($name, $currency->getName());
        self::assertSame($defaultFractionDigits, $currency->getDefaultFractionDigits());
        self::assertSame($currencyType, $currency->getCurrencyType());
    }

    final protected static function isExceptionClass(mixed $value): bool
    {
        return is_string($value) && str_ends_with($value, 'Exception');
    }
}
