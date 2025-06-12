<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DuplicateSlug extends Constraint
{
    public $message = 'global.slug_taken';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
