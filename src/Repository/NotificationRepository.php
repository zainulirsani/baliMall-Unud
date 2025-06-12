<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Notification::class;
        $this->alias = 'n';

        parent::__construct($registry);
    }

    public function getOrderNotification(int $id, string $type, array $parameters = [])
    {
        if (!in_array($type, ['buyer', 'seller'])) {
            return [];
        }

        $query = $this
            ->createQueryBuilder('n')
            ->where('n.id <> :id')
            ->andWhere('n.isAdmin = :is_admin')
            ->setParameter('id', 0)
            ->setParameter('is_admin', false)
        ;

        if ($type === 'buyer') {
            $query
                ->andwhere('n.buyerId = :buyer_id')
                ->andwhere('n.isSentToBuyer = :is_sent')
                ->setParameter('buyer_id', $id)
                ->setParameter('is_sent', false)
            ;
        } else {
            $query
                ->andwhere('n.sellerId = :seller_id')
                ->setParameter('seller_id', $id)
            ;

            if (isset($parameters['read'])) {
                if ($parameters['read'] === 'no') {
                    $query->andWhere($query->expr()->isNull('n.readAt'));
                } elseif ($parameters['read'] === 'yes') {
                    $query->andWhere($query->expr()->isNotNull('n.readAt'));
                }
            } else {
                $query
                    ->andwhere('n.isSentToSeller = :is_sent')
                    ->setParameter('is_sent', false)
                ;
            }
        }

        if (isset($parameters['limit']) && abs($parameters['limit']) > 0) {
            $query->setMaxResults(abs($parameters['limit']));
        }

        if (isset($parameters['sort_by'], $parameters['order_by'])) {
            $query->orderBy($parameters['sort_by'], $parameters['order_by']);
        }

        return $query->getQuery()->getScalarResult();
    }

    public function getAdminNotification(array $parameters = []): array
    {
        $total = 0;
        $query = $this
            ->createQueryBuilder('n')
            ->where('n.id <> :id')
            ->andWhere('n.isAdmin = :is_admin')
            ->setParameter('id', 0)
            ->setParameter('is_admin', true)
        ;

        if (isset($parameters['read']) && !empty($parameters['read'])) {
            if ($parameters['read'] === 'no') {
                $query->andWhere($query->expr()->isNull('n.readAt'));
            } elseif ($parameters['read'] === 'yes') {
                $query->andWhere($query->expr()->isNotNull('n.readAt'));
            }
        }

        $counter = clone $query;
        $counter->select('count(n.id)');

        try {
            $total = $counter->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        if (isset($parameters['limit']) && abs($parameters['limit']) > 0) {
            $query->setMaxResults(abs($parameters['limit']));
        }

        if (isset($parameters['sort_by'], $parameters['order_by'])) {
            $query->orderBy($parameters['sort_by'], $parameters['order_by']);
        }

        $notifications = $query->getQuery()->getScalarResult();

        return [
            'total' => $total,
            'notifications' => $notifications,
        ];
    }
}
