FROM rust:1.75-slim as builder

WORKDIR /app

RUN apt-get update && apt-get install -y \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

COPY Cargo.toml Cargo.lock ./
COPY src ./src
COPY openapi.yaml ./
COPY swagger-ui ./swagger-ui

RUN cargo build --release

FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/pos-master /app/pos-master
COPY --from=builder /app/openapi.yaml /app/openapi.yaml
COPY --from=builder /app/swagger-ui /app/swagger-ui

ENV DATABASE_URL=sqlite:///app/data/pos.db
ENV JWT_SECRET=change_this_secret_in_production
ENV RUST_LOG=info

RUN mkdir -p /app/data

EXPOSE 8000

CMD ["/app/pos-master"]