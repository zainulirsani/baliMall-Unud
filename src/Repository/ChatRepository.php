<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\ChatMessage;
use App\Entity\User;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ChatRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = Chat::class;
        $this->alias = 'c';

        parent::__construct($registry);
    }

    public function getPaginatedResult(array $parameters = []): QueryBuilder
    {
        $this->builder = $this
            ->createQueryBuilder('c')
            ->select(['c', 'p.firstName AS p_firstName', 'p.lastName AS p_lastName', 'p.photoProfile AS p_photoProfile'])
            ->leftJoin(User::class, 'p', 'WITH', 'c.participant = p.id')
            ->where('c.id <> :id')
            ->setParameter('id', 'id')
        ;

        if (isset($parameters['initiator'])) {
            $this->builder
                ->andWhere('c.initiator = :initiator')
                ->setParameter('initiator', $parameters['initiator'])
            ;
        }

        if (isset($parameters['keywords'])) {
            $this->builder
                ->andWhere($this->builder->expr()->orX(
                    $this->builder->expr()->like('u.firstName', ':keywords'),
                    $this->builder->expr()->like('u.lastName', ':keywords')
                ))
                ->setParameter('keywords', '%'.$parameters['keywords'].'%')
            ;
        }

        if (isset($parameters['type'])) {
            $this->builder
                ->andWhere('c.type = :type')
                ->setParameter('type', $parameters['type'])
            ;
        }

        $this->setLimitAndOffset($parameters);
        $this->setOrderBy($parameters);

        return $this->builder;
    }

    public function getRecentChat(string $room, int $recipient)
    {
        $query = $this
            ->createQueryBuilder('c')
            ->select('cm')
            ->leftJoin(ChatMessage::class, 'cm', 'WITH', 'cm.room = c.room')
            ->where('c.room = :room')
            ->andWhere('cm.recipient = :recipient')
            ->setParameter('room', $room)
            ->setParameter('recipient', $recipient)
            ->setMaxResults(1)
            ->orderBy('cm.createdAt', 'DESC')
        ;

        try {
            return $query->getQuery()->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $e) {
        }

        return [];
    }
}
