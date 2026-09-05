<?php

namespace App\Exceptions\Onboarding;

use RuntimeException;

class DuplicateAccountException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $field = 'phone',
    ) {
        parent::__construct($message);
    }

    public static function phone(): self
    {
        return new self('This phone number is already registered in the system.', 'phone');
    }

    public static function email(): self
    {
        return new self('This email address is already registered in the system. Please use a different email.', 'email');
    }
}
