<?php

namespace App\Service\MpdPlayer;

use App\Enum\MpdPlaybackCommand;

class MpdDriver
{
    public function __construct(private readonly MpdConnector $connector) {}

    public function pause(): void
    {
        $this->connector->sendCommand(MpdPlaybackCommand::Pause);
    }

    public function stop(): void
    {
        $this->connector->sendCommand(MpdPlaybackCommand::Stop);
    }

    public function next(): void
    {
        $this->connector->sendCommand(MpdPlaybackCommand::Next);
    }

    public function previous(): void
    {
        $this->connector->sendCommand(MpdPlaybackCommand::Previous);
    }

    public function seek(int $seconds): void
    {
        $this->connector->sendCommand(MpdPlaybackCommand::Seek, (string) $seconds);
    }

    /**
     * MPD returns an ACK error if volume is outside 0–100, so we clamp here
     * rather than letting the command fail and forcing the caller to handle it.
     */
    public function setVolume(int $volume): void
    {
        $volume = max(0, min(100, $volume));
        $this->connector->sendCommand(MpdPlaybackCommand::SetVolume, (string) $volume);
    }
}
