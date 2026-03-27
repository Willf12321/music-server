<?php

namespace App\Enum;

enum MpdCommand: string
{
    case Add          = 'add';
    case Clear        = 'clear';
    case Next         = 'next';
    case Pause        = 'pause';
    case Play         = 'play';
    case PlaylistInfo = 'playlistinfo';
    case Previous     = 'previous';
    case SetVolume    = 'setvol';
    case Status       = 'status';
    case Stop         = 'stop';
}
