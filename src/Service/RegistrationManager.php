<?php

namespace App\Service;

use App\DomainEvent\ParticipantRegistered;
use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\User;
use App\Exception\RegistrationException;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;

final class RegistrationManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RegistrationRepository $registrationRepository,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly LockFactory $lockFactory,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @throws RegistrationException when a business rule is violated
     */
    public function register(Event $event, User $participant): Registration
    {
        // Two participants booking the last seat at the same time must not both succeed
        $lock = $this->lockFactory->createLock('event-registration-'.$event->getId());
        $lock->acquire(true);

        try {
            if (!$event->isPublished() || $event->getStartsAt() <= $this->clock->now()) {
                throw RegistrationException::eventNotOpen();
            }

            if (null !== $this->registrationRepository->findOneByEventAndParticipant($event, $participant)) {
                throw RegistrationException::alreadyRegistered();
            }

            $capacity = $event->getCapacity();
            if (null !== $capacity && $this->registrationRepository->countByEvent($event) >= $capacity) {
                throw RegistrationException::eventFull();
            }

            $registration = new Registration($event, $participant);
            $this->entityManager->persist($registration);
            $this->entityManager->flush();
        } finally {
            $lock->release();
        }

        $this->dispatcher->dispatch(new ParticipantRegistered($registration));

        return $registration;
    }

    /**
     * @throws RegistrationException when the user is not registered to the event
     */
    public function unregister(Event $event, User $participant): void
    {
        $registration = $this->registrationRepository->findOneByEventAndParticipant($event, $participant)
            ?? throw RegistrationException::notRegistered();

        $event->getRegistrations()->removeElement($registration);
        $this->entityManager->remove($registration);
        $this->entityManager->flush();
    }
}
