<?php

namespace App\Enums;

enum ScrapeJobType: string
{
    case Backfill = 'backfill';
    case Incremental = 'incremental';
}