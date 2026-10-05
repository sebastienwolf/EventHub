<?php

namespace App\Entity;

use App\Repository\RegistrationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RegistrationRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_registration_event_participant', columns: ['event_id', 'participant_id'])]
class Registration extends TimestampableEntity
{
    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['registration:read'])]
    private Event $event;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['registration:read'])]
    private User $participant;

    public function __construct(Event $event, User $participant)
    {
        $this->event = $event;
        $this->participant = $participant;
        $event->getRegistrations()->add($this);
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getParticipant(): User
    {
        return $this->participant;
    }
}
