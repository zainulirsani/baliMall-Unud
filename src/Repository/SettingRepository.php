<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Persistence\ManagerRegistry;

class SettingRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Setting::class;
        $this->alias = 's';

        parent::__construct($registry);
    }

    public function getSettingsToBeCached(): array
    {
        $builder = $this->createQueryBuilder('s');
        $builder
            ->select('s.slug', 's.defaultValue')
            ->where('s.id > :id')
            //->andWhere($builder->expr()->isNotNull('s.slug'))
            ->setParameter('id', 0)
        ;

        $results = $builder->getQuery()->getArrayResult();
        $settings = [];

        foreach ($results as $result) {
            $settings[$result['slug']] = $result['defaultValue'];
        }

        return $settings;
    }
}
