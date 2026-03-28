#!/bin/sh
# Ensure the MPD database file exists — the named volume is initially empty
# so the file must be created at runtime, not just in the image build.
touch /var/lib/mpd/database

exec "$@"
