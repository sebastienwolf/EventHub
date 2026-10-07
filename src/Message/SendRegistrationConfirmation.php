<?php

namespace App\Message;

/**
 * Laravel queued Job equivalent. Only the id is stored in the queue:
 * the handler reloads a fresh entity from the database.
 */
final class SendRegistrationConfirmation implements AsyncMessageInterface
{
    public function __construct(
        public readonly int $registrationId,
    ) {
    }
}
