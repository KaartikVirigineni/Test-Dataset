FROM rust:1.75-slim as builder

WORKDIR /app

RUN apt-get update && apt-get install -y \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

COPY Cargo.toml Cargo.lock ./
RUN mkdir src && \
    echo "fn main() {}" > src/main.rs && \
    cargo build --release && \
    rm -rf src

COPY . .
RUN touch src/main.rs && cargo build --release

FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    ca-certificates \
    libssl3 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/realtime-presence /app/realtime-presence
COPY ./static /app/static
COPY ./openapi.yaml /app/openapi.yaml

ENV RUST_LOG=info
ENV DATABASE_URL=sqlite:///app/data/presence.db
ENV JWT_SECRET=change-this-secret-in-production-please-use-a-long-random-string

RUN mkdir -p /app/data

EXPOSE 8000

CMD ["/app/realtime-presence"]