<?php

namespace App\Support;

final class MoneyLimits
{
    /** Existing product-form business limit, in the product's base currency. */
    public const PRODUCT_PRICE_MAX = 1_000_000;

    /** Maximum value storable in the current DECIMAL(10, 2) money columns. */
    public const DECIMAL_10_2_MAX = '99999999.99';

    public const DECIMAL_10_2_MAX_CENTS = 9_999_999_999;
}
