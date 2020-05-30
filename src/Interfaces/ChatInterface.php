<?php

namespace Knik\GRcon\Interfaces;

interface ChatInterface
{
    /**
     * Send message to server
     * @param string $message
     * @return string
     */
    public function globalMessage(string $message): string;
}