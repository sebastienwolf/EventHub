<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Registration>
 */
class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registration::class);
    }

    public function findOneByEventAndParticipant(Event $event, User $participant): ?Registration
    {
        return $this->findOneBy(['event' => $event, 'participant' => $participant]);
    }

    public function countByEvent(Event $event): int
    {
        return $this->count(['event' => $event]);
    }

    public function createByEventQueryBuilder(Event $event): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->addSelect('p')
            ->join('r.participant', 'p')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.createdAt', 'ASC');
    }

    public function createByParticipantQueryBuilder(User $participant): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->addSelect('e')
            ->join('r.event', 'e')
            ->andWhere('r.participant = :participant')
            ->setParameter('participant', $participant)
            ->orderBy('e.startsAt', 'ASC');
    }
}
