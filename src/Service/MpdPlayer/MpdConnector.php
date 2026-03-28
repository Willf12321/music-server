<?php

namespace App\Service\MpdPlayer;

use App\Enum\MpdCommandInterface;
use App\Exception\MpdException;
use Psr\Log\LoggerInterface;

/**
 * Manages the TCP socket connection to MPD and executes raw commands.
 *
 * Connects per-command rather than holding a persistent socket because
 * PHP-FPM workers are stateless — a socket opened in one request would be
 * silently closed (or left dangling) by the time the next request arrives
 * in the same worker. Reconnecting is cheap on a local network.
 */
class MpdConnector
{
    /** @var resource|null */
    private $socket = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $host = 'mpd',
        private readonly int $port = 6600,
    ) {}

    /**
     * Send a command to MPD and return the response lines.
     *
     * Connects, writes the command, reads until OK or ACK, then disconnects.
     * Throws MpdException on ACK so callers get a typed, readable error.
     */
    public function sendCommand(MpdCommandInterface $command, string $argument = ''): array
    {
        if (!$this->connect()) {
            throw MpdException::connectionFailed($this->host, $this->port);
        }

        $wire = $argument !== '' ? "{$command->getCommand()} {$argument}" : $command->getCommand();

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

    public function parseLine(string $line): array
    {
        $pos = strpos($line, ': ');

        if ($pos === false) {
            return [null, null];
        }

        return [substr($line, 0, $pos), substr($line, $pos + 2)];
    }

    public function parseResponse(array $lines): array
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
}
