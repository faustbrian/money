<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Math\BigRational;
use Cline\Money\DiscountRate;
use Cline\Money\Exception\ExcessiveDiscountRateException;
use Cline\Money\Exception\NegativeDiscountRateException;

test('of creates discount rate from various inputs', function (mixed $input, string $expectedPercentage): void {
    $rate = DiscountRate::of($input);

    self::assertBigNumberEquals($expectedPercentage, $rate->getPercentage());
})->with('providerOf');

dataset('providerOf', fn (): array => [
    'integer' => [20, '20'],
    'string integer' => ['20', '20'],
    'string decimal' => ['25.5', '25.5'],
    'float' => [25.5, '25.5'],
    'zero' => [0, '0'],
    'hundred' => ['100', '100'],
    'BigRational' => [BigRational::of('51/2'), '51/2'],
]);

test('of rejects negative rate', function (): void {
    $this->expectException(NegativeDiscountRateException::class);

    DiscountRate::of('-5');
});

test('of rejects negative float rate', function (): void {
    $this->expectException(NegativeDiscountRateException::class);

    DiscountRate::of(-0.5);
});

test('of rejects rate exceeding 100', function (): void {
    $this->expectException(ExcessiveDiscountRateException::class);

    DiscountRate::of('100.01');
});

test('of rejects rate of 150', function (): void {
    $this->expectException(ExcessiveDiscountRateException::class);

    DiscountRate::of('150');
});

test('of allows rate of exactly 100', function (): void {
    $rate = DiscountRate::of('100');

    self::assertBigNumberEquals('100', $rate->getPercentage());
});

test('zero creates zero discount rate', function (): void {
    $rate = DiscountRate::zero();

    self::assertTrue($rate->isZero());
    self::assertBigNumberEquals('0', $rate->getPercentage());
});

test('getMultiplier returns percentage divided by 100', function (mixed $input, string $expectedMultiplier): void {
    $rate = DiscountRate::of($input);

    self::assertBigNumberEquals($expectedMultiplier, $rate->getMultiplier());
})->with('providerMultiplier');

dataset('providerMultiplier', fn (): array => [
    ['20', '1/5'],
    ['25.5', '51/200'],
    ['10', '1/10'],
    ['100', '1'],
    [0, '0'],
]);

test('isZero', function (mixed $input, bool $expected): void {
    self::assertSame($expected, DiscountRate::of($input)->isZero());
})->with('providerIsZero');

dataset('providerIsZero', fn (): array => [
    [0, true],
    ['0', true],
    ['0.0', true],
    ['25', false],
    ['0.01', false],
]);

test('isEqualTo', function (): void {
    self::assertTrue(DiscountRate::of('25')->isEqualTo(DiscountRate::of(25)));
    self::assertTrue(DiscountRate::of('25.50')->isEqualTo(DiscountRate::of('25.5')));
    self::assertFalse(DiscountRate::of('25')->isEqualTo(DiscountRate::of('26')));
    self::assertTrue(DiscountRate::zero()->isEqualTo(DiscountRate::of(0)));
});

test('toString', function (): void {
    self::assertSame('25%', (string) DiscountRate::of(25));
    self::assertSame('25.5%', (string) DiscountRate::of('25.5'));
    self::assertSame('0%', (string) DiscountRate::zero());
});

test('jsonSerialize', function (): void {
    self::assertSame('25', DiscountRate::of(25)->jsonSerialize());
    self::assertSame('25.5', DiscountRate::of('25.5')->jsonSerialize());
});
