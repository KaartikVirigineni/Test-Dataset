FROM node:14-alpine

# Install Meteor
RUN apk add --no-cache curl bash python3 make g++ && \
    curl https://install.meteor.com/ | sh

WORKDIR /app

# Copy package files
COPY package.json ./
COPY .meteor ./.meteor

# Copy application code
COPY . .

# Install dependencies
RUN meteor npm install

# Build the application
RUN meteor build --directory /build --server-only

WORKDIR /build/bundle/programs/server
RUN npm install

WORKDIR /build/bundle

ENV PORT=3000
ENV ROOT_URL=http://localhost:3000
ENV MONGO_URL=mongodb://dummy:27017/meteor
ENV DB_PATH=/data/clinic.db

EXPOSE 3000

CMD ["node", "main.js"]