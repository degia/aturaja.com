<?php

namespace App\Support;

use Illuminate\Support\Number;

class Money
{
    public static function format(string|float|int|null $value, int $maxPrecision = 0): string
    {
        return 'Rp ' . Number::format((float) ($value ?? 0), $maxPrecision);
    }
}