<?php

namespace App\Enum;

use App\Service\MpdPlayer\MpdCommandInterface;

enum MpdPlaybackCommand: string implements MpdCommandInterface
{
    case Next      = 'next';
    case Pause     = 'pause';
    case Play      = 'play';
    case Previous  = 'previous';
    case Seek      = 'seekcur';
    case SetVolume = 'setvol';
    case Stop      = 'stop';

    public function getCommand(): string
    {
        return $this->value;
    }
}
