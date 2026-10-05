<?php

namespace App\Repository;

use App\Entity\Conference;
use App\Entity\Event;
use App\Entity\User;
use App\Entity\Workshop;
use App\Enum\EventStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public const TYPES = [
        Conference::TYPE => Conference::class,
        Workshop::TYPE => Workshop::class,
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Public catalog: published events that have not started yet.
     */
    public function createUpcomingPublishedQueryBuilder(\DateTimeImmutable $now, ?string $type = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->addSelect('o')
            ->join('e.organizer', 'o')
            ->andWhere('e.status = :status')
            ->andWhere('e.startsAt > :now')
            ->setParameter('status', EventStatus::Published)
            ->setParameter('now', $now)
            ->orderBy('e.startsAt', 'ASC');

        if (null !== $type && isset(self::TYPES[$type])) {
            // INSTANCE OF filters on the discriminator column (Single Table Inheritance)
            $qb->andWhere(\sprintf('e INSTANCE OF %s', self::TYPES[$type]));
        }

        return $qb;
    }

    public function createByOrganizerQueryBuilder(User $organizer): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('e.startsAt', 'DESC');
    }

    /**
     * Published events starting within the given window whose reminder was not sent yet.
     *
     * @return list<Event>
     */
    public function findEventsNeedingReminder(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.reminderSentAt IS NULL')
            ->andWhere('e.startsAt > :from')
            ->andWhere('e.startsAt <= :to')
            ->setParameter('status', EventStatus::Published)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.slug = :slug')
            ->setParameter('slug', $slug);

        if (null !== $excludeId) {
            $qb->andWhere('e.id != :id')->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
