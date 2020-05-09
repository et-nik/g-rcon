<?php

namespace Knik\GRcon\Interfaces;

interface PlayersManageInterface
{
    public function getPlayers(): array;

    public function kick(string $playerName, string $reason = '');

    public function ban(string $playerName, string $reason = '');

    public function changeName(string $oldName, string $newName);
}