FROM geoffreybooth/meteor-base:2.13.3

WORKDIR /app

COPY .meteor .meteor
COPY package*.json ./

RUN meteor npm install

COPY . .

RUN meteor build --directory /build --server-only

WORKDIR /build/bundle/programs/server
RUN npm install

WORKDIR /build/bundle

ENV PORT=3000
ENV ROOT_URL=http://localhost:3000
ENV MONGO_URL=mongodb://localhost:27017/meteormart

EXPOSE 3000

CMD ["node", "main.js"]