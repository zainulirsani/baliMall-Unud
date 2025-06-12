<?php

namespace App\Validator\Constraints;

use App\Entity\BaseEntity;
use App\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProductPriceCheckValidator extends ConstraintValidator
{
    /**
     * @param BaseEntity $entity
     * @param Constraint $constraint
     */
    public function validate($entity, Constraint $constraint): void
    {
        if ($entity instanceof Product && $entity->getBasePrice() > $entity->getPrice()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
