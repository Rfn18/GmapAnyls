<?php

namespace App\Enums;

enum DatePrecision: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
}