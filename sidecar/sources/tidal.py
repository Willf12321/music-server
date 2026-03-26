class TidalSource:
    """
    Tidal integration via python-tidal (tidalapi).

    Primary audio source. Provides lossless FLAC streams when available.
    Should always be tried before falling back to YouTubeSource.
    """

    def search(self, query: str) -> list:
        # TODO: implement Tidal search via tidalapi
        return []

    def resolve(self, track_id: str) -> str | None:
        # TODO: implement stream URL resolution via tidalapi
        return None
