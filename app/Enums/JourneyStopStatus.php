<?php

namespace App\Enums;

enum JourneyStopStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';
}

