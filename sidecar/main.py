from concurrent.futures import ThreadPoolExecutor
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

from sources.tidal import TidalSource
from sources.youtube import YouTubeSource

app = FastAPI()

# Instantiated once at startup — Tidal login happens in the constructor.
tidal = TidalSource()
youtube = YouTubeSource()


@app.get("/health")
def health():
    return {"status": "ok"}


@app.get("/search")
def search(q: str = ""):
    """
    Search for tracks and albums across all sources simultaneously.

    Tidal and YouTube are queried in parallel. Results from both appear in the
    response, each tagged with their source. Tidal tracks are listed first.
    Albums come from Tidal only (YouTube has no album concept).
    """
    if not q.strip():
        raise HTTPException(status_code=422, detail="Query parameter 'q' must not be empty.")

    with ThreadPoolExecutor() as executor:
        f_tidal_tracks  = executor.submit(tidal.search_tracks, q)
        f_tidal_albums  = executor.submit(tidal.search_albums, q)
        f_youtube_tracks = executor.submit(youtube.search_tracks, q)

    tracks = f_tidal_tracks.result() + f_youtube_tracks.result()
    albums = f_tidal_albums.result()

    return {"tracks": tracks, "albums": albums}


@app.get("/album/{album_id}/tracks")
def album_tracks(album_id: str, source: str = "tidal"):
    """
    Return the full track listing for an album.

    Called when the user expands an album in the search results.
    """
    if source != "tidal":
        raise HTTPException(status_code=422, detail=f"Unsupported source: {source}")

    tracks = tidal.get_album_tracks(album_id)

    if not tracks:
        raise HTTPException(status_code=404, detail="Album not found or has no tracks.")

    return tracks


@app.get("/me/playlists")
def my_playlists():
    """
    Return the playlists owned by the authenticated Tidal user.
    """
    playlists = tidal.get_my_playlists()

    if playlists is None:
        raise HTTPException(status_code=503, detail="Tidal session not ready.")

    return playlists


@app.get("/playlists/{playlist_id}/tracks")
def playlist_tracks(playlist_id: str):
    """
    Return the full track listing for a playlist.

    Returns an empty list when the playlist exists but contains only videos.
    404 when the playlist cannot be found or the request fails.
    Called when the user expands a playlist in the UI.
    """
    tracks = tidal.get_playlist_tracks(playlist_id)

    if tracks is None:
        raise HTTPException(status_code=404, detail="Playlist not found.")

    return tracks


class TrackRequest(BaseModel):
    track_id: str
    source: str


@app.post("/resolve")
def resolve(request: TrackRequest):
    """
    Resolve a stream URL for a given track, ready to hand to MPD.

    Called by Symfony immediately before passing a URL to MPD. URLs are not
    cached because stream URLs are signed and expire after a short time.
    """
    if request.source == "tidal":
        url = tidal.resolve(request.track_id)
    elif request.source == "youtube":
        url = youtube.resolve(request.track_id)
    else:
        raise HTTPException(status_code=422, detail=f"Unsupported source: {request.source}")

    if url is None:
        raise HTTPException(status_code=404, detail="Could not resolve stream URL.")

    return {"url": url}
