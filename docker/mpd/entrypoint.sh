#!/bin/sh
# Ensure the MPD database file exists — the named volume is initially empty
# so the file must be created at runtime, not just in the image build.
touch /var/lib/mpd/database

# Substitute environment variables into the MPD config at runtime.
# This allows MPD_AUDIO_DEVICE to differ between dev and production.
sed -i "s|\$MPD_AUDIO_DEVICE|${MPD_AUDIO_DEVICE}|g" /etc/mpd.conf
sed -i "s|\$MPD_MIXER_TYPE|${MPD_MIXER_TYPE}|g" /etc/mpd.conf

exec "$@"
