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
 * Base class for exceptions thrown when two money operands are incompatible.
 *
 * Extend this class to represent specific mismatch conditions such as a currency
 * or context discrepancy between two money values involved in an operation.
 * @author Brian Faust <brian@cline.sh>
 */
abstract class MoneyMismatchException extends RuntimeException implements MoneyException {}
