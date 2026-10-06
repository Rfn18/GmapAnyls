<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Failed = 'failed';
}