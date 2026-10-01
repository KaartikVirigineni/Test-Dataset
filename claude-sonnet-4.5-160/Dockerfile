FROM rust:1.75-slim as builder

WORKDIR /app

RUN apt-get update && apt-get install -y \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

COPY Cargo.toml Cargo.lock ./
COPY src ./src
COPY static ./static

RUN cargo build --release

FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/streamlite /app/streamlite
COPY static ./static

ENV RUST_LOG=info
ENV DATABASE_URL=sqlite:///app/data/streamlite.db
ENV JWT_SECRET=change-this-secret-in-production

RUN mkdir -p /app/data

EXPOSE 8000

CMD ["/app/streamlite"]