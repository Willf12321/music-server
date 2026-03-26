<?php

namespace App\Exception;

/**
 * Typed exception for MPD communication failures.
 *
 * Using a dedicated exception type means controllers can catch MPD errors
 * distinctly from general runtime errors, and respond with appropriate HTTP
 * status codes and messages without leaking internal details.
 */
class MpdException extends \RuntimeException
{
    public static function connectionFailed(string $host, int $port): self
    {
        return new self("Could not connect to MPD at {$host}:{$port}.");
    }

    public static function commandFailed(string $command, string $reason): self
    {
        return new self("MPD command '{$command}' failed: {$reason}");
    }
}
