<?php

namespace App\Enum;

enum MpdStatusCommand: string implements MpdCommandInterface
{
    case CurrentSong  = 'currentsong';
    case PlaylistInfo = 'playlistinfo';
    case Status       = 'status';

    public function getCommand(): string
    {
        return $this->value;
    }
}
