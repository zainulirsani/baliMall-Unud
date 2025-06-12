<?php

namespace App\Validator\Constraints;

use App\Entity\BaseEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ReservedNamesValidator extends ConstraintValidator
{
    /** @var EntityManagerInterface $manager */
    protected $manager;

    /** @var array $names */
    protected $names;

    /** @var string $type */
    protected $type = 'equal'; // ['equal', 'like']

    public function __construct(EntityManagerInterface $manager, array $names = [])
    {
        $this->manager = $manager;
        $this->names = $names;
    }

    /**
     * @param BaseEntity $entity
     * @param Constraint $constraint
     */
    public function validate($entity, Constraint $constraint): void
    {
        if (method_exists($entity, 'getName') && !empty($entity->getName())) {
            $name = strtolower($entity->getName());

            if ($this->type === 'equal') {
                if (in_array($name, $this->names, false)) {
                    $this->context->buildViolation($constraint->message)->addViolation();
                }
            } elseif ($this->type === 'like') {
                foreach ($this->names as $reserved) {
                    if (strpos($name, $reserved) !== false) {
                        $this->context->buildViolation($constraint->message)->addViolation();
                        break;
                    }
                }
            }
        }
    }
}
