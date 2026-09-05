<?php

namespace App\Exceptions\Onboarding;

use RuntimeException;

class ExpiredActivationTokenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This activation link has expired. Please ask your administrator to resend the invitation.');
    }
}
