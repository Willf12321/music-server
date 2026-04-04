<?php

namespace App\Enum;

use App\Service\MpdPlayer\MpdCommandInterface;

enum MpdQueueCommand: string implements MpdCommandInterface
{
    case Add      = 'add';
    case Clear    = 'clear';
    case Consume  = 'consume';
    case DeleteId = 'deleteid';

    public function getCommand(): string
    {
        return $this->value;
    }
}
