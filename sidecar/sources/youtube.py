import logging

import yt_dlp

logger = logging.getLogger(__name__)

_SEARCH_OPTS = {
    'quiet': True,
    'no_warnings': True,
    'extract_flat': True,
}

_RESOLVE_OPTS = {
    'quiet': True,
    'no_warnings': True,
    'format': 'bestaudio/best',
}


class YouTubeSource:
    """
    YouTube Music fallback via yt-dlp.

    Used only when Tidal returns no results for a query. Maximum quality is
    approximately 256kbps AAC — not lossless. Always prefer TidalSource.

    Track IDs are YouTube video IDs so that /resolve can construct the watch
    URL without any additional state.
    """

    def search_tracks(self, query: str) -> list[dict]:
        try:
            with yt_dlp.YoutubeDL(_SEARCH_OPTS) as ydl:
                results = ydl.extract_info(f"ytsearch10:{query}", download=False)
                entries = results.get('entries', []) if results else []
                return [self._format_track(e) for e in entries if e and e.get('id')]
        except Exception as e:
            logger.error("YouTube Music search failed for query '%s': %s", query, e)
            return []

    def search_albums(self, query: str) -> list[dict]:
        # YouTube Music has no equivalent album concept that maps cleanly to the
        # same data shape as Tidal albums. Tracks-only fallback is sufficient.
        return []

    def resolve(self, track_id: str) -> str | None:
        """
        Return a direct audio stream URL for the given YouTube video ID.

        yt-dlp selects the best available audio-only format. The URL is a
        signed CDN link that MPD can stream directly.
        """
        try:
            with yt_dlp.YoutubeDL(_RESOLVE_OPTS) as ydl:
                info = ydl.extract_info(
                    f"https://www.youtube.com/watch?v={track_id}",
                    download=False,
                )
                return info.get('url') if info else None
        except Exception as e:
            logger.error("YouTube Music resolve failed for track '%s': %s", track_id, e)
            return None

    def _format_track(self, entry: dict) -> dict:
        return {
            'id': entry['id'],
            'title': entry.get('title', ''),
            'artist': entry.get('uploader') or entry.get('channel', ''),
            'album': '',
            'duration_seconds': int(entry.get('duration') or 0),
            'source': 'youtube',
        }
