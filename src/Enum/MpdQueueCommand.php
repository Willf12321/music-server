<?php

namespace App\Enum;

use App\Service\MpdPlayer\MpdCommandInterface;

enum MpdQueueCommand: string implements MpdCommandInterface
{
    case Add   = 'add';
    case Clear = 'clear';

    public function getCommand(): string
    {
        return $this->value;
    }
}
