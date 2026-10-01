FROM node:14-alpine

RUN apk add --no-cache python3 make g++ bash curl

RUN curl https://install.meteor.com/ | sh

WORKDIR /app

COPY package*.json ./
COPY .meteor ./.meteor

RUN meteor npm install

COPY . .

RUN meteor build --directory /build --server-only

WORKDIR /build/bundle/programs/server

RUN npm install

WORKDIR /build/bundle

EXPOSE 3000

ENV ROOT_URL=http://localhost:3000
ENV MONGO_URL=mongodb://localhost:27017/meteor
ENV PORT=3000

CMD ["node", "main.js"]