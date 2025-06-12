<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidDobDate extends Constraint
{
    public $message = 'user.dob_not_valid';
}
