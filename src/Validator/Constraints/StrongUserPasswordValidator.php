<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class StrongUserPasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        preg_match('/[^a-zA-Z0-9]+/im', $value, $matches1);
        preg_match('/\d+/im', $value, $matches2);

        if (isset($matches2[0][0]) && ($matches2[0][0] !== '') && !empty($matches1[0][0])) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
