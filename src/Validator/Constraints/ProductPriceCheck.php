<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ProductPriceCheck extends Constraint
{
    public $message = 'product.price_check';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
