<?php

namespace Knik\GRcon\Interfaces;

interface PlayersManageInterface
{
    /**
     * Get Players list
     * @return array
     *      id:             Player id. Used to manage players (kick, ban, etc)
     *      name:           Player name
     *      steamid:        Player SteamID. Optional
     *      score:          Player score (frags). Optional
     *      ping:           Player ping. Optional
     *      loss:           Player loss. Optional
     *      ip:             Player IP address. Optional
     *      time:           How much time player spent on the server. Optional
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
     * @param int $time
     * @return mixed
     */
    public function ban($playerId, string $reason = '', int $time = 0);
}