<?php

namespace Solvrtech\Logbook\Exception;

use Exception;
use Throwable;

class LogbookSecurityException extends Exception
{
    public function __construct(
        $message = "Token not found",
        $code = 401,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
