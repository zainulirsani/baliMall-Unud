<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class ChatMessageRepository extends BaseEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->entity = ChatMessage::class;
        $this->alias = 'cm';

        parent::__construct($registry);
    }

    public function getChatMessages(string $room, int $maxResult = 20)
    {
        return $this
            ->createQueryBuilder('cm')
            ->select(['cm', 's.firstName AS s_firstName', 's.lastName AS s_lastName', 'r.firstName AS r_firstName', 'r.lastName AS r_lastName'])
            ->leftJoin(User::class, 's', 'WITH', 'cm.sender = s.id')
            ->leftJoin(User::class, 'r', 'WITH', 'cm.recipient = r.id')
            ->where('cm.room = :room')
            ->setParameter('room', $room)
            ->orderBy('cm.createdAt', 'ASC')
            ->setMaxResults($maxResult)
            ->setFirstResult(0)
            ->getQuery()
            ->getScalarResult()
        ;
    }

    public function fetchReplies(string $room, int $sender, ?string $timestamp = null)
    {
        $query = $this
            ->createQueryBuilder('cm')
            ->select([
                'cm.id AS cm_id',
                'cm.message AS cm_message',
                'cm.createdAt AS cm_createdAt',
                'u.firstName AS u_firstName',
                'u.lastName AS u_lastName'
            ])
            ->leftJoin(User::class, 'u', 'WITH', 'cm.sender = u.id')
            ->where('cm.room = :room')
            ->andWhere('cm.sender = :sender')
            ->setParameter('room', $room)
            ->setParameter('sender', $sender)
            ->orderBy('cm.createdAt', 'ASC')
        ;

        if (!empty($timestamp)) {
            $query
                ->andWhere('cm.createdAt > :timestamp')
                ->setParameter('timestamp', $timestamp)
            ;
        }

        return $query->getQuery()->getArrayResult();
    }
}
