<?php

namespace App\Service;

use App\Enum\MpdCommand;
use App\Exception\MpdException;
use Psr\Log\LoggerInterface;

class MpdPlayer
{
    /** @var resource|null */
    private $socket = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $host = 'mpd',
        private readonly int $port = 6600,
    ) {}

    /**
     * Play a URI immediately, replacing the current queue.
     *
     * We clear and replace rather than append because this is a single-room
     * system. Explicit queue management is handled via addToQueue(). Playing
     * a track directly should always mean "play this now".
     */
    public function play(string $uri): void
    {
        $this->sendCommand(MpdCommand::Clear);
        $this->sendCommand(MpdCommand::Add, $uri);
        $this->sendCommand(MpdCommand::Play);
    }

    /**
     * Add a URI to the end of the current queue without interrupting playback.
     */
    public function addToQueue(string $uri): void
    {
        $this->sendCommand(MpdCommand::Add, $uri);
    }

    public function getStatus(): array
    {
        $lines = $this->sendCommand(MpdCommand::Status);

        return $this->parseResponse($lines);
    }

    public function getQueue(): array
    {
        $lines = $this->sendCommand(MpdCommand::PlaylistInfo);
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
            'title'    => $entry['title'] ?? $entry['file'] ?? 'Unknown',
            'artist'   => $entry['artist'] ?? '',
            'duration' => isset($entry['duration']) ? (int) $entry['duration'] : null,
        ], $queue);
    }

    /**
     * Set playback volume.
     *
     * MPD returns an ACK error if volume is outside 0–100, so we clamp here
     * rather than letting the command fail and forcing the caller to handle it.
     */
    public function setVolume(int $volume): void
    {
        $volume = max(0, min(100, $volume));
        $this->sendCommand(MpdCommand::SetVolume, (string) $volume);
    }

    /**
     * Open a TCP socket to MPD and read the greeting line.
     *
     * We connect per-command rather than holding a persistent socket because
     * PHP-FPM workers are stateless — a socket opened in one request would be
     * silently closed (or left dangling) by the time the next request arrives
     * in the same worker. Reconnecting is cheap on a local network.
     */
    private function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);

        if ($this->socket === false) {
            $this->socket = null;
            $this->logger->error('MPD connection failed.', [
                'host'  => $this->host,
                'port'  => $this->port,
                'error' => $errstr,
            ]);

            return false;
        }

        // Read and discard the MPD greeting line (e.g. "OK MPD 0.23.5").
        fgets($this->socket);

        return true;
    }

    private function disconnect(): void
    {
        if ($this->socket === null) {
            return;
        }

        fwrite($this->socket, "close\n");
        fclose($this->socket);
        $this->socket = null;
    }

    /**
     * Send a command to MPD and return the response lines.
     *
     * Connects, writes the command, reads until OK or ACK, then disconnects.
     * Throws MpdException on ACK so callers get a typed, readable error.
     */
    public function sendCommand(MpdCommand $command, string $argument = ''): array
    {
        if (!$this->connect()) {
            throw MpdException::connectionFailed($this->host, $this->port);
        }

        $wire = $argument !== '' ? "{$command->value} {$argument}" : $command->value;

        try {
            fwrite($this->socket, $wire . "\n");

            $lines = [];

            while (!feof($this->socket)) {
                $line = rtrim(fgets($this->socket), "\r\n");

                if ($line === 'OK') {
                    break;
                }

                if (str_starts_with($line, 'ACK')) {
                    throw MpdException::commandFailed($wire, $line);
                }

                $lines[] = $line;
            }

            return $lines;
        } finally {
            $this->disconnect();
        }
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

    private function parseLine(string $line): array
    {
        $pos = strpos($line, ': ');

        if ($pos === false) {
            return [null, null];
        }

        return [substr($line, 0, $pos), substr($line, $pos + 2)];
    }
}
