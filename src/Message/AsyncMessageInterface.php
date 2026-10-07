<?php

namespace App\Message;

/**
 * Marker interface: messages implementing it are routed to the "async" transport
 * (see config/packages/messenger.yaml) and handled by a worker.
 */
interface AsyncMessageInterface
{
}
