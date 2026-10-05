<?php

namespace App\Doctrine\Listener;

use App\Entity\Contract\TimestampableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Clock\ClockInterface;

/**
 * Global Doctrine listener (Laravel "Observer" equivalent): it receives every entity,
 * so it filters on TimestampableInterface.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class TimestampableListener
{
    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $now = $this->clock->now();
        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        // The UnitOfWork recomputes the change set right after preUpdate listeners run
        $entity->setUpdatedAt($this->clock->now());
    }
}
