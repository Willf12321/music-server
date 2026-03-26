class YouTubeSource:
    """
    YouTube Music fallback integration via yt-dlp.

    Used only when a track cannot be found on Tidal.
    Maximum quality is approximately 256kbps AAC — not lossless.
    """

    def search(self, query: str) -> list:
        # TODO: implement YouTube Music search via yt-dlp
        return []

    def resolve(self, track_id: str) -> str | None:
        # TODO: implement stream URL resolution via yt-dlp
        return None
