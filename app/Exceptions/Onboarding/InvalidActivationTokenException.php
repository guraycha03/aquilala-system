<?php

namespace App\Exceptions\Onboarding;

use RuntimeException;

class InvalidActivationTokenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This activation link is invalid or has already been used. Please contact your administrator for a new invitation.');
    }
}
