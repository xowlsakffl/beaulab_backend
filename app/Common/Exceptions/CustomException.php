<?php

namespace App\Common\Exceptions;

use RuntimeException;

class CustomException extends RuntimeException
{
    public function __construct(
        public readonly ApiErrorCode $errorCode,
        string                       $message = '',
        public readonly mixed        $details = null
    ) {
        parent::__construct($message);
    }
}
