<?php

namespace App\Validator\Constraints;

use App\Entity\BaseEntity;
use App\Repository\BaseEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DuplicateSlugValidator extends ConstraintValidator
{
    /** @var EntityManagerInterface $manager */
    protected $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param BaseEntity $entity
     * @param Constraint $constraint
     */
    public function validate($entity, Constraint $constraint): void
    {
        if ($entity->getSlugCheck()) {
            $value = $entity->getSlug();
            $class = get_class($entity);
            /** @var BaseEntityRepository $repository */
            $repository = $this->manager->getRepository($class);

            while (true) {
                $existing = $repository->checkSlug($value, (int) $entity->getId());

                if ($existing > 0) {
                    $parts = explode('-', $value);
                    $counter = count($parts);
                    $index = $counter - 1;
                    $last = end($parts);

                    if (is_numeric($last) && isset($parts[$index])) {
                        $parts[$index] = $existing + 1;
                        $value = implode('-', $parts);
                    } else {
                        $value .= '-'.$existing;
                    }
                } else {
                    break;
                }
            }

            $entity->setSlug($value);
        }
    }
}
