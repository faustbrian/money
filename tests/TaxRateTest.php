<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Math\BigRational;
use Cline\Money\Exception\NegativeTaxRateException;
use Cline\Money\TaxRate;

test('of creates tax rate from various inputs', function (mixed $input, string $expectedPercentage): void {
    $rate = TaxRate::of($input);

    self::assertBigNumberEquals($expectedPercentage, $rate->getPercentage());
})->with('providerOf');

dataset('providerOf', fn (): array => [
    'integer' => [25, '25'],
    'string integer' => ['25', '25'],
    'string decimal' => ['25.5', '25.5'],
    'float' => [25.5, '25.5'],
    'zero' => [0, '0'],
    'big number' => ['150', '150'],
    'BigRational' => [BigRational::of('51/2'), '51/2'],
]);

test('of rejects negative rate', function (): void {
    $this->expectException(NegativeTaxRateException::class);

    TaxRate::of('-5');
});

test('of rejects negative float rate', function (): void {
    $this->expectException(NegativeTaxRateException::class);

    TaxRate::of(-0.5);
});

test('zero creates zero tax rate', function (): void {
    $rate = TaxRate::zero();

    self::assertTrue($rate->isZero());
    self::assertBigNumberEquals('0', $rate->getPercentage());
});

test('getMultiplier returns percentage divided by 100', function (mixed $input, string $expectedMultiplier): void {
    $rate = TaxRate::of($input);

    self::assertBigNumberEquals($expectedMultiplier, $rate->getMultiplier());
})->with('providerMultiplier');

dataset('providerMultiplier', fn (): array => [
    ['25', '1/4'],
    ['25.5', '51/200'],
    ['10', '1/10'],
    ['100', '1'],
    [0, '0'],
]);

test('isZero', function (mixed $input, bool $expected): void {
    self::assertSame($expected, TaxRate::of($input)->isZero());
})->with('providerIsZero');

dataset('providerIsZero', fn (): array => [
    [0, true],
    ['0', true],
    ['0.0', true],
    ['25', false],
    ['0.01', false],
]);

test('isEqualTo', function (): void {
    self::assertTrue(TaxRate::of('25')->isEqualTo(TaxRate::of(25)));
    self::assertTrue(TaxRate::of('25.50')->isEqualTo(TaxRate::of('25.5')));
    self::assertFalse(TaxRate::of('25')->isEqualTo(TaxRate::of('26')));
    self::assertTrue(TaxRate::zero()->isEqualTo(TaxRate::of(0)));
});

test('toString', function (): void {
    self::assertSame('25%', (string) TaxRate::of(25));
    self::assertSame('25.5%', (string) TaxRate::of('25.5'));
    self::assertSame('0%', (string) TaxRate::zero());
});

test('jsonSerialize', function (): void {
    self::assertSame('25', TaxRate::of(25)->jsonSerialize());
    self::assertSame('25.5', TaxRate::of('25.5')->jsonSerialize());
});
