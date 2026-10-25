<?php

namespace App\Support\Threads;

use RuntimeException;

/**
 * Threads refused a call, or could not be reached. The message is the one
 * Meta wrote (error_user_msg when it gave one), so it can be shown to the
 * admin as it is.
 */
class ThreadsApiException extends RuntimeException
{
    /**
     * Whether trying the same thing again later could work: Threads was down
     * or busy, rather than saying the post itself is wrong.
     */
    public function __construct(string $message, public readonly bool $temporary = false)
    {
        parent::__construct($message);
    }
}
