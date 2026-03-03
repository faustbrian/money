<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money\Exception;

use Throwable;

/**
 * Marker interface for all exceptions thrown by this library.
 *
 * Catching this interface is the recommended way to handle any error originating
 * from money operations without binding call sites to specific exception classes.
 * @author Brian Faust <brian@cline.sh>
 */
interface MoneyException extends Throwable {}
