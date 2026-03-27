# Music Server

A self-hosted home audio server running on a local network NUC, controlled via a browser UI.
Audio plays server-side through the NUC's audio output into a mixing desk. The browser is a
remote control only — no audio streams to the browser.

## Architecture Overview

```
Browser UI (remote control only)
    │  HTTP
    ▼
Symfony (PHP)          — HTTP layer, orchestration, MPD control
    │                  │
    │ TCP (MPD protocol)│ HTTP (internal REST)
    ▼                  ▼
MPD                 Python Sidecar (FastAPI)
(audio + queue)         │
                        ├── Tidal (python-tidal)     priority 1 — lossless FLAC
                        └── YouTube Music (yt-dlp)   priority 2 — fallback

    Redis — search result cache + transient playback state
```

## Components

### Symfony (PHP) — Main Application
Handles all HTTP requests from the browser. Orchestrates search and playback by delegating
to the Python sidecar and MPD. Does not handle audio directly and has no knowledge of
where audio comes from — that is the sidecar's responsibility.

### Python Sidecar (FastAPI) — Internal Audio Source Service
A small internal REST service. Resolves track searches and returns streamable URLs back to
Symfony. Abstracts source differences so Symfony always receives the same response shape
regardless of which source was used.

Supported sources, in priority order:
1. **Tidal** (`python-tidal`) — primary source, used whenever available, provides lossless FLAC
2. **YouTube Music** (`yt-dlp`) — fallback, used when a track cannot be found on Tidal

### MPD (Music Player Daemon)
Runs on the NUC. Owns the ALSA audio device directly (no PulseAudio). Symfony drives it
over the MPD TCP protocol. MPD is responsible for the queue, playback state, and all
audio output to the mixing desk.

### Redis
Caches search results from the sidecar to avoid repeated calls to external APIs.
Also holds transient playback state (e.g. current track, queue snapshot).

## Stack

- **PHP 8.3** / **Symfony 7.4** — main application
- **Python / FastAPI** — audio source sidecar
- **MPD** — audio playback daemon
- **Redis** — caching and transient state
- **Nginx** (Alpine) — serves the browser UI, proxies API requests
- **Docker Compose** — local dev environment (port 8080)

## Code Standards

### No God Classes
Every class has a single, well-defined responsibility. If a class is growing to handle
multiple concerns, split it.

### Doc Blocks — Why, Not What
Doc blocks should explain intent and reasoning, not restate what the code already says.

```php
// Bad — restates the code
/** Sets the volume. @param int $level The volume level. */

// Good — explains why
/**
 * Clamps volume between 0–100 before passing to MPD.
 * MPD does not validate range itself and will error on out-of-bounds values.
 */
```

### Early Returns
Avoid nesting by returning or throwing early on guard conditions.

```php
// Bad
public function play(Track $track): void
{
    if ($this->isReady()) {
        if (!$track->isMissing()) {
            // actual logic buried here
        }
    }
}

// Good
public function play(Track $track): void
{
    if (!$this->isReady()) {
        return;
    }

    if ($track->isMissing()) {
        throw new TrackNotFoundException($track);
    }

    // actual logic here
}
```

### Line Length
Maximum **120 characters** per line.

### Human Readability
Write code for the next person reading it. Prefer clarity over brevity.
Name things after what they mean in the domain, not what they do technically.

### Naming Conventions
All services should end in 'er' and not include 'Service'.
