FROM rust:1.75-slim as builder

WORKDIR /app

RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
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

COPY --from=builder /app/target/release/ticketdesk /app/
COPY --from=builder /app/openapi.yaml /app/
COPY --from=builder /app/static /app/static

ENV DATABASE_URL=sqlite:///app/data/tickets.db
ENV JWT_SECRET=change_this_secret_in_production_please_use_random_string
ENV RUST_LOG=info

RUN mkdir -p /app/data

EXPOSE 8000

CMD ["/app/ticketdesk"]