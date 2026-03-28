FROM alpine:3.19

RUN apk add --no-cache mpd

COPY mpd.conf /etc/mpd.conf

RUN mkdir -p /music /var/lib/mpd /var/log/mpd /var/run/mpd

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 6600

ENTRYPOINT ["/entrypoint.sh"]
CMD ["mpd", "--no-daemon", "/etc/mpd.conf"]
