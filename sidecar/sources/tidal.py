import logging
import os
import threading
from pathlib import Path

import tidalapi

logger = logging.getLogger(__name__)

# Persisted outside the container via a volume mount so login survives restarts.
SESSION_FILE = Path('/app/tidal_session.json')


class TidalSource:
    """
    Tidal integration via python-tidal (tidalapi).

    Primary audio source. Provides lossless FLAC streams when available.
    Should always be tried before falling back to YouTubeSource.

    Authentication uses Tidal's OAuth2 device flow. On first run the sidecar
    will print a URL to its logs — visit it to authorise the app. The session
    is then saved to SESSION_FILE and reused on every subsequent startup.

    Auth runs in a background thread so it does not block the HTTP server from
    starting. Search requests return an empty list until auth completes.
    """

    def __init__(self):
        self._session = None
        config = tidalapi.Config(quality=tidalapi.Quality.high_lossless)
        session = tidalapi.Session(config)

        # Run in a thread so uvicorn is not blocked waiting for OAuth approval.
        thread = threading.Thread(target=self._initialise, args=(session,), daemon=True)
        thread.start()

    def _initialise(self, session: tidalapi.Session) -> None:
        if SESSION_FILE.exists() and SESSION_FILE.stat().st_size > 0:
            try:
                if session.login_session_file(SESSION_FILE):
                    self._session = session
                    logger.info("Tidal session loaded from saved file.")
                    return
                logger.warning("Saved Tidal session is expired — re-authenticating.")
            except Exception as e:
                logger.warning("Could not load saved Tidal session: %s", e)

        self._oauth_login(session)

    def _oauth_login(self, session: tidalapi.Session) -> None:
        """
        Runs the OAuth2 device authorisation flow in the background thread.

        Blocks the thread (not uvicorn) until the user visits the printed URL
        and approves the request. Saves the session afterwards so this only
        needs to happen once.
        """
        try:
            login, future = session.login_oauth()
            url = f"https://{login.verification_uri_complete}"
            print(f"\n{'=' * 60}\nTIDAL AUTHORISATION REQUIRED\nVisit: {url}\n{'=' * 60}\n", flush=True)
            logger.info("Waiting for Tidal OAuth approval. Visit: %s", url)
            future.result()
            session.save_session_to_file(SESSION_FILE)
            self._session = session
            logger.info("Tidal login successful. Session saved.")
        except Exception as e:
            logger.error("Tidal OAuth login failed: %s", e)

    def search(self, query: str) -> list[dict]:
        if self._session is None:
            logger.warning("Tidal session not ready yet — search skipped.")
            return []

        try:
            results = self._session.search(query, models=[tidalapi.Track], limit=25)
            tracks = results.get('tracks', [])
            return [self._format_track(t) for t in tracks]
        except Exception as e:
            logger.error("Tidal search failed for query '%s': %s", query, e)
            return []

    def resolve(self, track_id: str) -> str | None:
        """
        Return the best available stream URL for a track.

        Quality fallback chain: high_lossless → high_lossless (HIGH) → low_320k
        We always want the best available rather than failing hard — a lower
        quality stream is better than no stream. high_lossless is tried first
        because lossless FLAC is the primary reason to use Tidal over YouTube.
        get_url() is used rather than get_stream() because MPD needs a direct
        URL, not a manifest.
        """
        if self._session is None:
            logger.error("Tidal session not ready — cannot resolve track.")
            return None

        try:
            track = self._session.track(int(track_id))

            for quality in [
                tidalapi.Quality.high_lossless,
                tidalapi.Quality.low_320k,
                tidalapi.Quality.low_96k,
            ]:
                try:
                    self._session.audio_quality = quality
                    url = track.get_url()
                    if url:
                        return url
                except Exception:
                    continue
        except Exception as e:
            logger.error("Failed to resolve Tidal track %s: %s", track_id, e)

        return None

    def _format_track(self, track: tidalapi.Track) -> dict:
        return {
            "id": str(track.id),
            "title": track.name,
            "artist": track.artist.name if track.artist else "",
            "album": track.album.name if track.album else "",
            "duration_seconds": track.duration,
            "source": "tidal",
        }
