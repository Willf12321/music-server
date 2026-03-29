<?php

namespace App\Service\MpdPlayer;

use App\Enum\MpdStatusCommand;

class MpdInspector
{
    public function __construct(private readonly MpdConnector $connector) {}

    public function getStatus(): array
    {
        $status      = $this->connector->parseResponse($this->connector->sendCommand(MpdStatusCommand::Status));
        $currentSong = $this->connector->parseResponse($this->connector->sendCommand(MpdStatusCommand::CurrentSong));

        // Merge song metadata into the status response so the UI has everything
        // it needs in a single request.
        return array_merge($status, $currentSong);
    }

    public function getQueue(): array
    {
        $lines = $this->connector->sendCommand(MpdStatusCommand::PlaylistInfo);
        $queue = [];
        $current = [];

        foreach ($lines as $line) {
            // Each track entry starts with a "file:" line — use it as a boundary.
            if (str_starts_with($line, 'file:')) {
                if (!empty($current)) {
                    $queue[] = $current;
                }
                $current = [];
            }

            [$key, $value] = $this->connector->parseLine($line);
            if ($key !== null) {
                $current[strtolower($key)] = $value;
            }
        }

        if (!empty($current)) {
            $queue[] = $current;
        }

        return array_map(static fn(array $entry) => [
            'pos'      => $entry['pos'] ?? null,
            'title'    => $entry['title'] ?? $entry['file'] ?? 'Unknown',
            'artist'   => $entry['artist'] ?? '',
            'duration' => isset($entry['duration']) ? (int) $entry['duration'] : null,
        ], $queue);
    }
}
