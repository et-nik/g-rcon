<?php

namespace Knik\GRcon\Protocols;

class CsgoAdapter extends SourceAdapter
{
    public function getPlayers(): array
    {
        $status = $this->execute('status');

        if (strlen($status) <= 0) {
            return [];
        }

        $count = preg_match_all('/^'
            . '#\s*(?<userid>\d*)\s*'
            . '\d*\s*'
            . '"(?<name>.*?)"\s*'
            . '(?<uniqueid>[a-zA-Z0-9_:]*)\s*'
            . '(?<connected>[0-9:]*)?\s*'
            . '(?<ping>\d*)?\s*'
            . '(?<loss>\d*)?\s*'
            . '(?<state>[a-zA-Z0-9_:]*)\s*'
            . '(?<rate>\d*)\s*'
            . '(?<adr>[0-9.]*:\d*)?$'
            . '/mi',
            $status,
            $matches
        );

        if ($count <= 0) {
            return [];
        }

        $players = [];

        for ($i = 0; $i < $count; $i++) {
            if ($matches['adr'][$i] != 0) {
                $ip = explode(':', $matches['adr'][$i])[0];
            } else {
                $ip = '127.0.0.1';
            }

            $players[] = [
                // Common
                'id'        => $matches['userid'][$i],
                'name'      => $matches['name'][$i],
                'ping'      => $matches['ping'][$i],
                'loss'      => $matches['loss'][$i],
                'ip'        => $ip,

                // Source
                'steamid'   => $matches['uniqueid'][$i],
                'time'      => $matches['connected'][$i],

                'rate'      => $matches['rate'][$i],
                'state'     => $matches['state'][$i],
            ];
        }

        return $players;
    }
}
