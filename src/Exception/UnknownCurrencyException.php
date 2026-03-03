<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

use RuntimeException;

/**
 * Base class for exceptions thrown when a currency cannot be resolved from a given input.
 *
 * Extend this class to represent specific lookup failures such as an unrecognised
 * currency code or an ambiguous country-to-currency mapping.
 * @author Brian Faust <brian@cline.sh>
 */
abstract class UnknownCurrencyException extends RuntimeException implements MoneyException {}
