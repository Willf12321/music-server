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

    def search_tracks(self, query: str) -> list[dict]:
        if self._session is None:
            logger.warning("Tidal session not ready yet — search skipped.")
            return []

        try:
            results = self._session.search(query, models=[tidalapi.Track], limit=25)
            return [self._format_track(t) for t in results.get('tracks', [])]
        except Exception as e:
            logger.error("Tidal track search failed for query '%s': %s", query, e)
            return []

    def search_albums(self, query: str) -> list[dict]:
        if self._session is None:
            logger.warning("Tidal session not ready yet — album search skipped.")
            return []

        try:
            results = self._session.search(query, models=[tidalapi.Album], limit=10)
            return [self._format_album(a) for a in results.get('albums', [])]
        except Exception as e:
            logger.error("Tidal album search failed for query '%s': %s", query, e)
            return []

    def get_album_tracks(self, album_id: str) -> list[dict]:
        if self._session is None:
            logger.error("Tidal session not ready — cannot fetch album tracks.")
            return []

        try:
            album = self._session.album(int(album_id))
            return [self._format_track(t) for t in album.tracks()]
        except Exception as e:
            logger.error("Failed to fetch tracks for Tidal album %s: %s", album_id, e)
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

    def search_users(self, query: str) -> list[dict]:
        """
        Search for Tidal users by name.

        tidalapi's session.search() only covers tracks/albums/artists/playlists/videos.
        User search requires calling the Tidal API directly — the session's request
        object provides authenticated access to any v1 endpoint.
        """
        if self._session is None:
            logger.warning("Tidal session not ready yet — user search skipped.")
            return []

        try:
            response = self._session.request.basic_request(
                'GET', 'search',
                params={'query': query, 'limit': 10, 'types': 'USERS'},
            )
            response.raise_for_status()
            body = response.json()
            print("Tidal user search raw response:", body, flush=True)
            users = body.get('users', {}).get('items', [])
            return [self._format_user(u) for u in users]
        except Exception as e:
            logger.error("Tidal user search failed for query '%s': %s", query, e)
            return []

    def get_user_playlists(self, user_id: str) -> list[dict] | None:
        """
        Return the public playlists for a Tidal user, or None if the user
        cannot be found or the request fails.

        An empty list means the user exists but has no public playlists.
        None means the lookup itself failed, so the caller can 404.

        Uses the same API path as LoggedInUser.playlists() but for an arbitrary user ID.
        """
        if self._session is None:
            logger.error("Tidal session not ready — cannot fetch user playlists.")
            return None

        try:
            playlist_obj = self._session.playlist()
            playlists = self._session.request.map_request(
                "users/%s/playlists" % user_id,
                parse=playlist_obj.parse_factory,
            )
            return [self._format_playlist(p) for p in playlists if p is not None]
        except Exception as e:
            logger.error("Failed to fetch playlists for Tidal user %s: %s", user_id, e)
            return None

    def get_playlist_tracks(self, playlist_id: str) -> list[dict] | None:
        """
        Return all audio tracks in a playlist, or None if the playlist cannot
        be found or the request fails.

        Video items are filtered out — a playlist that contains only videos
        returns an empty list rather than None. None is reserved for genuine
        lookup failures so the caller can 404 appropriately.
        """
        if self._session is None:
            logger.error("Tidal session not ready — cannot fetch playlist tracks.")
            return None

        try:
            playlist = self._session.playlist(playlist_id)
            items = playlist.items()
            # Playlist items may include videos — only return audio tracks.
            return [self._format_track(t) for t in items if isinstance(t, tidalapi.Track)]
        except Exception as e:
            logger.error("Failed to fetch tracks for Tidal playlist %s: %s", playlist_id, e)
            return None

    def _format_album(self, album: tidalapi.Album) -> dict:
        return {
            "id": str(album.id),
            "title": album.name,
            "artist": album.artist.name if album.artist else "",
            "num_tracks": album.num_tracks,
            "source": "tidal",
        }

    def _format_user(self, user: dict) -> dict:
        first = user.get('firstName') or ''
        last = user.get('lastName') or ''
        name = ' '.join(filter(None, [first, last])) or 'Unknown'
        return {
            "id": str(user['id']),
            "name": name,
        }

    def _format_playlist(self, playlist: tidalapi.Playlist) -> dict:
        return {
            "id": str(playlist.id),
            "name": playlist.name,
            "num_tracks": playlist.num_tracks,
            "description": playlist.description or "",
        }

    def _format_track(self, track: tidalapi.Track) -> dict:
        return {
            "id": str(track.id),
            "title": track.name,
            "artist": track.artist.name if track.artist else "",
            "album": track.album.name if track.album else "",
            "duration_seconds": track.duration,
            "source": "tidal",
        }
