<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Math\BigRational;
use Cline\Math\Exception\DivisionByZeroException;
use Cline\Money\Exception\MoneyMismatchException;
use Cline\Money\Money;

describe('Money', function (): void {
    describe('Happy Paths', function (): void {
        test('ratioOf returns correct ratio', function (): void {
            $ratio = Money::of(25, 'EUR')->ratioOf(Money::of(100, 'EUR'));

            expect($ratio)->toBeInstanceOf(BigRational::class);
            expect((string) $ratio->simplified())->toBe('1/4');
        });

        test('ratioOf with equal amounts', function (): void {
            $ratio = Money::of(100, 'EUR')->ratioOf(Money::of(100, 'EUR'));

            expect((string) $ratio->simplified())->toBe('1');
        });

        test('ratioOf with larger numerator', function (): void {
            $ratio = Money::of(150, 'EUR')->ratioOf(Money::of(100, 'EUR'));

            expect((string) $ratio->simplified())->toBe('3/2');
        });

        test('ratioOf with decimals', function (): void {
            $ratio = Money::of('33.33', 'EUR')->ratioOf(Money::of('100.00', 'EUR'));

            expect($ratio)->toBeInstanceOf(BigRational::class);
            expect((string) $ratio->simplified())->toBe('3333/10000');
        });

        test('ratioOf with negative amounts', function (): void {
            $ratio = Money::of(-50, 'EUR')->ratioOf(Money::of(100, 'EUR'));

            expect((string) $ratio->simplified())->toBe('-1/2');
        });

        test('ratioOf with zero numerator', function (): void {
            $ratio = Money::of(0, 'EUR')->ratioOf(Money::of(100, 'EUR'));

            expect((string) $ratio->simplified())->toBe('0');
        });

        test('percentageOf basic', function (): void {
            $percentage = Money::of(25, 'EUR')->percentageOf(Money::of(100, 'EUR'));

            expect($percentage)->toBeInstanceOf(BigRational::class);
            expect((string) $percentage->simplified())->toBe('25');
        });

        test('percentageOf with decimals', function (): void {
            $percentage = Money::of('33.33', 'EUR')->percentageOf(Money::of('100.00', 'EUR'));

            expect((string) $percentage->simplified())->toBe('3333/100');
        });

        test('percentageOf over 100 percent', function (): void {
            $percentage = Money::of(150, 'EUR')->percentageOf(Money::of(100, 'EUR'));

            expect((string) $percentage->simplified())->toBe('150');
        });

        test('percentageOf with fractional result', function (): void {
            $percentage = Money::of(1, 'EUR')->percentageOf(Money::of(3, 'EUR'));

            expect((string) $percentage->simplified())->toBe('100/3');
        });
    });

    describe('Sad Paths', function (): void {
        test('ratioOf throws on currency mismatch', function (): void {
            Money::of(25, 'EUR')->ratioOf(Money::of(100, 'USD'));
        })->throws(MoneyMismatchException::class);

        test('ratioOf throws on zero divisor', function (): void {
            Money::of(25, 'EUR')->ratioOf(Money::of(0, 'EUR'));
        })->throws(DivisionByZeroException::class);

        test('percentageOf throws on currency mismatch', function (): void {
            Money::of(25, 'EUR')->percentageOf(Money::of(100, 'USD'));
        })->throws(MoneyMismatchException::class);

        test('percentageOf throws on zero divisor', function (): void {
            Money::of(25, 'EUR')->percentageOf(Money::of(0, 'EUR'));
        })->throws(DivisionByZeroException::class);
    });
});
