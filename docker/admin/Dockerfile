FROM debian:bookworm-slim

RUN apt-get -q update && apt-get install -q -y \
    firefox-esr \
    python3-pip \
    cron \
    && pip3 install selenium --break-system-packages

COPY emulate.py /app/emulate.py

COPY emulate.cron /etc/cron.d/emulate
RUN chmod 0644 /etc/cron.d/emulate
RUN crontab /etc/cron.d/emulate

CMD ["cron", "-f"]