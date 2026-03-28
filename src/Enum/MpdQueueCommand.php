<?php

namespace App\Enum;

enum MpdQueueCommand: string implements MpdCommandInterface
{
    case Add   = 'add';
    case Clear = 'clear';
    case Play  = 'play';

    public function getCommand(): string
    {
        return $this->value;
    }
}
