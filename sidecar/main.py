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
    Search for tracks via the requested source.

    source: "tidal" | "youtube" | "auto"

    "auto" will eventually try Tidal first and fall back to YouTube Music
    if no results are found. For now it delegates to Tidal only, as YouTube
    support is not yet implemented.
    """
    if not q.strip():
        raise HTTPException(status_code=422, detail="Query parameter 'q' must not be empty.")

    if source == "tidal":
        return tidal.search(q)

    if source == "youtube":
        return youtube.search(q)

    # auto — Tidal first, YouTube fallback not yet implemented
    return tidal.search(q)


class TrackRequest(BaseModel):
    track_id: str
    source: str


@app.post("/resolve")
def resolve(request: TrackRequest):
    """
    Resolve a stream URL for a given track, ready to hand to MPD.

    Called by Symfony immediately before passing a URL to MPD. URLs are not
    cached because Tidal stream URLs are signed and expire after a short time.
    Caching a URL would cause MPD to receive an expired URL on playback.
    """
    if request.source == "tidal":
        url = tidal.resolve(request.track_id)
    else:
        raise HTTPException(status_code=422, detail=f"Unsupported source: {request.source}")

    if url is None:
        raise HTTPException(status_code=404, detail="Could not resolve stream URL.")

    return {"url": url}


@app.post("/play")
def play(request: TrackRequest):
    """
    Resolve a playable stream URL for the given track and source,
    ready to be handed to MPD.
    """
    # TODO: resolve stream URL via the appropriate source and return it
    return {"url": None}
