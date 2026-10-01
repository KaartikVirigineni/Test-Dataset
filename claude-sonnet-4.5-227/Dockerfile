FROM ruby:3.2-slim

WORKDIR /app

RUN apt-get update -qq && \
    apt-get install -y build-essential libsqlite3-dev && \
    rm -rf /var/lib/apt/lists/*

COPY Gemfile Gemfile.lock ./

RUN bundle install --without development test

COPY . .

RUN mkdir -p /app/data

ENV DATABASE_PATH=/app/data/rentatrack.db
ENV RACK_ENV=production
ENV JWT_SECRET=change_this_secret_in_production_with_env_var

EXPOSE 4567

CMD ["bundle", "exec", "puma", "-C", "puma_config.rb"]