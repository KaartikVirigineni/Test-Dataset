FROM rust:1.75-slim as builder

WORKDIR /app

RUN apt-get update && apt-get install -y libsqlite3-dev && rm -rf /var/lib/apt/lists/*

COPY Cargo.toml Cargo.lock ./
COPY src ./src
COPY static ./static
COPY openapi.yaml ./

RUN cargo build --release

FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y libsqlite3-0 ca-certificates && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/loglite /app/loglite
COPY --from=builder /app/static /app/static
COPY --from=builder /app/openapi.yaml /app/openapi.yaml

ENV DATABASE_URL=sqlite:///data/loglite.db
ENV JWT_SECRET=change-this-secret-in-production
ENV RUST_LOG=info

EXPOSE 8000

CMD ["/app/loglite"]