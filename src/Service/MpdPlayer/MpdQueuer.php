<?php

namespace App\Service\MpdPlayer;

use App\Enum\MpdPlaybackCommand;
use App\Enum\MpdQueueCommand;
use App\Enum\MpdStatusCommand;

class MpdQueuer
{
    public function __construct(private readonly MpdConnector $connector) {}

    /**
     * Play a URI immediately, replacing the current queue.
     *
     * We clear and replace rather than append because this is a single-room
     * system. Explicit queue management is handled via addToQueue(). Playing
     * a track directly should always mean "play this now".
     */
    public function play(string $uri): void
    {
        $this->connector->sendCommand(MpdQueueCommand::Clear);
        $this->connector->sendCommand(MpdQueueCommand::Add, $uri);
        $this->connector->sendCommand(MpdPlaybackCommand::Play);
    }

    /**
     * Add a URI to the end of the current queue without interrupting playback.
     *
     * If nothing is playing (state is stopped), playback is started automatically
     * so the user does not have to press play after queuing into an empty session.
     * A paused player is intentionally left paused.
     */
    public function addToQueue(string $uri): void
    {
        $this->connector->sendCommand(MpdQueueCommand::Add, $uri);

        if ($this->isStopped()) {
            $this->connector->sendCommand(MpdPlaybackCommand::Play);
        }
    }

    private function isStopped(): bool
    {
        $lines = $this->connector->sendCommand(MpdStatusCommand::Status);

        foreach ($lines as $line) {
            if (str_starts_with($line, 'state: ')) {
                return substr($line, 7) === 'stop';
            }
        }

        return false;
    }

    public function clearQueue(): void
    {
        $this->connector->sendCommand(MpdQueueCommand::Clear);
    }
}
