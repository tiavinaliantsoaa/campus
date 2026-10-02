<?php

namespace App\Support;

class Money
{
    public static function format(null|string|float|int $amount): string
    {
        $value = (float) $amount;
        $decimals = abs($value - round($value)) < 0.001 ? 0 : 2;

        return number_format($value, $decimals, ',', ' ').' '.config('campus.currency');
    }

    public static function decimal(null|string|float|int $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
