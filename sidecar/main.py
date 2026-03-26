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


class PlayRequest(BaseModel):
    track_id: str
    source: str


@app.post("/play")
def play(request: PlayRequest):
    """
    Resolve a playable stream URL for the given track and source,
    ready to be handed to MPD.
    """
    # TODO: resolve stream URL via the appropriate source and return it
    return {"url": None}
