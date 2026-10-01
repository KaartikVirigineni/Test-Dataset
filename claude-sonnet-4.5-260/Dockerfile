FROM rust:1.75-slim as builder

WORKDIR /app

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

COPY Cargo.toml Cargo.lock ./
RUN mkdir src && echo "fn main() {}" > src/main.rs
RUN cargo build --release && rm -rf src

COPY . .
RUN touch src/main.rs
RUN cargo build --release

FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    libsqlite3-0 \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/docuvault /app/docuvault
COPY openapi.yaml /app/openapi.yaml
COPY swagger-ui /app/swagger-ui

ENV RUST_LOG=info
ENV DATABASE_URL=sqlite:///app/data/docuvault.db
ENV JWT_SECRET=change-this-secret-in-production-please-use-a-long-random-string
ENV HOST=0.0.0.0
ENV PORT=8000

RUN mkdir -p /app/data

EXPOSE 8000

CMD ["/app/docuvault"]