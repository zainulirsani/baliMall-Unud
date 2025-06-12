<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ReservedNames extends Constraint
{
    public $message = 'global.reserved_names';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
