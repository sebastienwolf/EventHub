<?php

namespace App\Message;

final class SendEventReminder implements AsyncMessageInterface
{
    public function __construct(
        public readonly int $registrationId,
    ) {
    }
}
