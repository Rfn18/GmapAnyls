<?php

namespace App\Enums;

enum ScrapeJobStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';
}