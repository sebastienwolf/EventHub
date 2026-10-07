<?php

namespace App\Security\Voter;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Laravel Policy equivalent for events.
 *
 * Usage: #[IsGranted(EventVoter::EDIT, subject: 'event')] or $this->isGranted(EventVoter::EDIT, $event).
 *
 * @extends Voter<string, Event>
 */
final class EventVoter extends Voter
{
    public const VIEW = 'EVENT_VIEW';
    public const EDIT = 'EVENT_EDIT';
    public const DELETE = 'EVENT_DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Event;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Event $event */
        $event = $subject;

        // Anyone, even anonymous, can see a published event
        if (self::VIEW === $attribute && $event->isPublished()) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            $vote?->addReason('The user is not authenticated.');

            return false;
        }

        // Admins can do everything (the role hierarchy is resolved by the decision manager)
        if ($this->accessDecisionManager->decide($token, [User::ROLE_ADMIN])) {
            return true;
        }

        if ($event->isOrganizedBy($user)) {
            return true;
        }

        $vote?->addReason('Only the organizer of the event or an admin can do this.');

        return false;
    }
}
