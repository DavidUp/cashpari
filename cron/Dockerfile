FROM alpine:3.19

# Install curl
RUN apk add --no-cache curl ca-certificates

# Install supercronic (cron for containers - handles signals properly)
ENV SUPERCRONIC_VERSION=v0.2.29
RUN curl -fsSLo /usr/local/bin/supercronic \
    "https://github.com/aptible/supercronic/releases/download/${SUPERCRONIC_VERSION}/supercronic-linux-amd64" \
    && chmod +x /usr/local/bin/supercronic

# Copy crontab
COPY cron/crontab /app/crontab

# Run supercronic with the crontab
CMD ["supercronic", "/app/crontab"]
