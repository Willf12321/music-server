from typing import Protocol


class AudioSource(Protocol):
    """
    Contract that all audio sources must satisfy.

    Symfony always receives the same response shape regardless of source.
    The 'source' key in every returned dict is the routing key used by
    /resolve to dispatch back to the correct source at playback time.
    """

    def search_tracks(self, query: str) -> list[dict]: ...

    def search_albums(self, query: str) -> list[dict]: ...

    def resolve(self, track_id: str) -> str | None: ...
