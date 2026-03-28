<?php

namespace App\Enum;

enum MpdPlaybackCommand: string implements MpdCommandInterface
{
    case Next      = 'next';
    case Pause     = 'pause';
    case Previous  = 'previous';
    case Seek      = 'seekcur';
    case SetVolume = 'setvol';
    case Stop      = 'stop';

    public function getCommand(): string
    {
        return $this->value;
    }
}
