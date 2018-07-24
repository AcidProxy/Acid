<?php

declare(strict_types=1);

namespace proxy\command\sender;

/**
 * Interface Sender
 * @package proxy\command\sender
 */
interface Sender
{

    /**
     * @param string $message
     * @return void
     */
    public function sendMessage(string $message);
}