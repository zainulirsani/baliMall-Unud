<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class StrongUserPassword extends Constraint
{
    public $message = 'user.password_weak';
}
