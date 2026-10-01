FROM rust:1.75-slim as builder

WORKDIR /app

RUN apt-get update && apt-get install -y \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

COPY Cargo.toml Cargo.lock ./
COPY src ./src
COPY static ./static
COPY openapi.yaml ./

RUN cargo build --release

FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    ca-certificates \
    libssl3 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/campaign-manager /app/
COPY --from=builder /app/openapi.yaml /app/
COPY --from=builder /app/static /app/static

ENV RUST_LOG=info
ENV DATABASE_URL=sqlite:///app/data/campaigns.db
ENV JWT_SECRET=change-this-secret-in-production
ENV HOST=0.0.0.0
ENV PORT=8000

RUN mkdir -p /app/data

EXPOSE 8000

CMD ["./campaign-manager"]