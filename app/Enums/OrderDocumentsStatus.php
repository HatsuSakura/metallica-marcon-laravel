<?php

namespace App\Enums;

enum OrderDocumentsStatus: string
{
    case NOT_GENERATED = 'not_generated';
    case GENERATING = 'generating';
    case GENERATED = 'generated';
    case FAILED = 'failed';
}
