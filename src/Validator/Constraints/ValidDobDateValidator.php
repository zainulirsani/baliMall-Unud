<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidDobDateValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!empty($value)) {
            $dob = explode('-', $value->format('Y-m-d'));

            if (isset($dob[0], $dob[1], $dob[2]) && checkdate((int) $dob[1], (int) $dob[2], (int) $dob[0])) {
                return;
            }

            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
