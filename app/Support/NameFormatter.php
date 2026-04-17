<?php

namespace App\Support;

use Illuminate\Support\Str;

class NameFormatter
{
    public static function standardize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', trim($value));

        if ($value === null || $value === '') {
            return null;
        }

        return Str::title(Str::lower($value));
    }
}
