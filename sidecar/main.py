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
def search(q: str = "", source: str = "auto"):
    """
    Search for tracks and albums.

    Tidal is always tried first — it provides lossless FLAC. YouTube Music is
    used as a fallback only when Tidal returns no tracks, so a user will never
    see a 256kbps YouTube result when a lossless Tidal result exists.

    source: "tidal" | "auto"
    """
    if not q.strip():
        raise HTTPException(status_code=422, detail="Query parameter 'q' must not be empty.")

    tracks = tidal.search_tracks(q)
    albums = tidal.search_albums(q)

    if source == "auto" and not tracks:
        tracks = youtube.search_tracks(q)

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
