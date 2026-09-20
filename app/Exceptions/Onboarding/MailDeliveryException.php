<?php

namespace App\Exceptions\Onboarding;

use RuntimeException;

class MailDeliveryException extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'The account was created, but the activation email could not be sent. Please use the "Resend Invitation" action once connectivity is restored.');
    }
}