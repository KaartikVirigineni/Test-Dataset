FROM ruby:3.2-slim

WORKDIR /app

RUN apt-get update && apt-get install -y \
    build-essential \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

COPY Gemfile Gemfile.lock ./

RUN bundle install --without development test

COPY . .

RUN mkdir -p /app/data

EXPOSE 4567

CMD ["bundle", "exec", "puma", "-C", "puma.rb"]