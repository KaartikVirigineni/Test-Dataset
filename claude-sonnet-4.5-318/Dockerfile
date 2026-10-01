FROM node:14-alpine

# Install Meteor
RUN apk add --no-cache curl bash python3 make g++ && \
    curl https://install.meteor.com/ | sh

WORKDIR /app

# Copy package files
COPY package*.json ./
COPY .meteor ./.meteor

# Copy application files
COPY . .

# Install npm dependencies
RUN meteor npm install

# Build the app
RUN meteor build --directory /build --server-only

WORKDIR /build/bundle/programs/server
RUN npm install

WORKDIR /build/bundle

# Expose port
EXPOSE 3000

ENV PORT=3000
ENV ROOT_URL=http://localhost:3000
ENV MONGO_URL=mongodb://localhost:27017/meteor

CMD ["node", "main.js"]