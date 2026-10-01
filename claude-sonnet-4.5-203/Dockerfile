FROM rust:1.75-slim as builder

WORKDIR /app

RUN apt-get update && apt-get install -y \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

COPY Cargo.toml Cargo.lock ./
RUN mkdir src && echo "fn main() {}" > src/main.rs
RUN cargo build --release
RUN rm -rf src

COPY . .
RUN touch src/main.rs
RUN cargo build --release

FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/portfolio-tracker /app/
COPY openapi.yaml /app/
COPY swagger-ui /app/swagger-ui

RUN mkdir -p /app/data

ENV RUST_LOG=info
ENV DATABASE_URL=sqlite:///app/data/portfolio.db
ENV JWT_SECRET=change-this-secret-in-production
ENV HOST=0.0.0.0
ENV PORT=8000

EXPOSE 8000

CMD ["./portfolio-tracker"]