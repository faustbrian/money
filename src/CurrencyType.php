<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Money;

/**
 * Classifies a currency as an active ISO 4217 currency, a withdrawn ISO currency, or a custom application currency.
 * @author Brian Faust <brian@cline.sh>
 */
enum CurrencyType
{
    /** An ISO 4217 currency that is currently in official use. */
    case IsoCurrent;

    /** An ISO 4217 currency that has been officially withdrawn from use. */
    case IsoHistorical;

    /** A non-ISO currency defined by the application. */
    case Custom;
}
