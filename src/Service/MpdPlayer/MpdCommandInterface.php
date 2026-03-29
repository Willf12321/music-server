<?php

namespace App\Service\MpdPlayer;

interface MpdCommandInterface
{
    public function getCommand(): string;
}
