<?php

namespace App\Service\MpdPlayer;

use App\Enum\MpdStatusCommand;

class MpdInspector
{
    public function __construct(private readonly MpdConnector $connector) {}

    public function getStatus(): array
    {
        $status      = $this->parseResponse($this->connector->sendCommand(MpdStatusCommand::Status));
        $currentSong = $this->parseResponse($this->connector->sendCommand(MpdStatusCommand::CurrentSong));

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

            [$key, $value] = $this->parseLine($line);
            if ($key !== null) {
                $current[strtolower($key)] = $value;
            }
        }

        if (!empty($current)) {
            $queue[] = $current;
        }

        return array_map(static fn(array $entry) => [
            'pos'      => $entry['pos'] ?? null,
            'file'     => $entry['file'] ?? null,
            'title'    => $entry['title'] ?? null,
            'artist'   => $entry['artist'] ?? null,
            'album'    => $entry['album'] ?? null,
            'duration' => isset($entry['duration']) ? (int) $entry['duration'] : null,
        ], $queue);
    }

    private function parseLine(string $line): array
    {
        $pos = strpos($line, ': ');

        if ($pos === false) {
            return [null, null];
        }

        return [substr($line, 0, $pos), substr($line, $pos + 2)];
    }

    private function parseResponse(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            [$key, $value] = $this->parseLine($line);
            if ($key !== null) {
                $result[strtolower($key)] = $value;
            }
        }

        return $result;
    }
}
