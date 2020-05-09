<?php

namespace Knik\GRcon\Interfaces;

interface ChatInterface
{
    /**
     * Send message to server
     * @param string $message
     */
    public function sendMessage(string $message): void;
}