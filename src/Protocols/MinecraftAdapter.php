<?php

namespace Knik\GRcon\Protocols;

use Knik\GRcon\Interfaces\ChatInterface;
use Knik\GRcon\Interfaces\PlayersManageInterface;

class MinecraftAdapter extends SourceAdapter implements ChatInterface
{
    /**
     * @return array
     */
    public function getPlayers(): array
    {
        $players = $this->execute('list uuids');

        if (strlen($players) <= 0) {
            return [];
        }

        $count = preg_match_all('/(?<name>[a-z0-9_\-]*)\s\((?<uuid>[a-f0-9\-]*)\)/mi', $players, $matches);

        if ($count <= 0) {
            return [];
        }

        $players = [];

        for ($i = 0; $i < $count; $i++) {
            $players[] = [
                // Common
                'id'        => $matches['name'][$i],
                'name'      => $matches['name'][$i],

                // Minecraft
                'uuid'      => $matches['uuid'][$i],
            ];
        }

        return $players;
    }

    /**
     * @param $playerId
     * @param string $reason
     * @return mixed|void
     */
    public function kick($playerId, string $reason = '')
    {
        return $this->execute("kick \"{$playerId}\" \"{$reason}\"");
    }

    /**
     * @param $playerId
     * @param string $reason
     * @param int $time
     * @return mixed|void
     */
    public function ban($playerId, string $reason = '', int $time = 0)
    {
        return $this->execute("ban \"{$playerId}\" \"{$reason}\"");
    }

    /**
     * @param string $message
     * @return string
     */
    public function globalMessage(string $message): string
    {
        return $this->execute("say {$message}");
    }
}
