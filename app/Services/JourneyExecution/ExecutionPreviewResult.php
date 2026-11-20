<?php

namespace App\Services\JourneyExecution;

/** Internal rollback carrier; never escapes the preview boundary. */
class ExecutionPreviewResult extends \RuntimeException
{
    public function __construct(public array $result)
    {
        parent::__construct('Execution preview');
    }
}
