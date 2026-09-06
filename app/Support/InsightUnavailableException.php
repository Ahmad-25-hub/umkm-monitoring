<?php

namespace App\Support;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

class InsightUnavailableException extends RuntimeException implements ShouldntReport
{
    public function __construct(string $message, public readonly int $status = 503)
    {
        parent::__construct($message);
    }
}
