<?php

namespace Knik\GRcon\Interfaces;

interface PlayersManageInterface
{
    /**
     * Get Players list
     * @return array
     */
    public function getPlayers(): array;

    /**
     * Kick player by ID (game server internal unique id, steamid, name, etc.)
     *
     * @param $playerId
     * @param string $reason
     * @return mixed
     */
    public function kick($playerId, string $reason = '');

    /**
     * Ban Player by ID (game server internal unique id, steamid, name, etc.)
     * @param $playerId
     * @param string $reason
     * @return mixed
     */
    public function ban($playerId, string $reason = '');
}