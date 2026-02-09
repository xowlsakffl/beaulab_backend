<?php

namespace App\Common\Exceptions;

use RuntimeException;

class CustomException extends RuntimeException
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        string                    $message = '',
        public readonly mixed     $details = null
    ) {
        parent::__construct($message);
    }
}
