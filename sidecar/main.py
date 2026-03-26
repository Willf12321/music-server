from fastapi import FastAPI
from pydantic import BaseModel

from sources.tidal import TidalSource
from sources.youtube import YouTubeSource

app = FastAPI()

tidal = TidalSource()
youtube = YouTubeSource()


@app.get("/health")
def health():
    return {"status": "ok"}


@app.get("/search")
def search(q: str, source: str = "auto"):
    """
    Resolve a search query against the requested source.

    source: "tidal" | "youtube" | "auto"
    "auto" tries Tidal first and falls back to YouTube if no results.
    """
    # TODO: implement source routing and delegate to TidalSource / YouTubeSource
    return {"results": []}


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
