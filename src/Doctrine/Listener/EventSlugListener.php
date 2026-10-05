<?php

namespace App\Doctrine\Listener;

use App\Entity\Event;
use App\Service\UniqueSlugGenerator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Entity listener: only called for Event (and its Conference/Workshop subclasses,
 * which inherit the listeners of their parent).
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Event::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Event::class)]
final class EventSlugListener
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
    ) {
    }

    public function prePersist(Event $event, PrePersistEventArgs $args): void
    {
        if (null === $event->getSlug()) {
            $event->setSlug($this->slugGenerator->generate((string) $event->getTitle()));
        }
    }

    public function preUpdate(Event $event, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('title')) {
            $event->setSlug($this->slugGenerator->generate((string) $event->getTitle(), $event->getId()));
        }
    }
}
