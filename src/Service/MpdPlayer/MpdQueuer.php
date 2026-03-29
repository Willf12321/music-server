<?php

namespace App\Service\MpdPlayer;

use App\Enum\MpdQueueCommand;

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
        $this->connector->sendCommand(MpdQueueCommand::Play);
    }

    /** Add a URI to the end of the current queue without interrupting playback. */
    public function addToQueue(string $uri): void
    {
        $this->connector->sendCommand(MpdQueueCommand::Add, $uri);
    }

    public function clearQueue(): void
    {
        $this->connector->sendCommand(MpdQueueCommand::Clear);
    }
}
