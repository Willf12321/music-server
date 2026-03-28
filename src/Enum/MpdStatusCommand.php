<?php

namespace App\Enum;

enum MpdStatusCommand: string implements MpdCommandInterface
{
    case PlaylistInfo = 'playlistinfo';
    case Status       = 'status';

    public function getCommand(): string
    {
        return $this->value;
    }
}
