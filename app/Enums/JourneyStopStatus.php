<?php

namespace App\Enums;

enum JourneyStopState: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';
}
