FROM node:18-alpine

RUN apk add --no-cache curl python3 make g++

RUN curl https://install.meteor.com/ | sh

WORKDIR /app

COPY package*.json ./
COPY .meteor ./.meteor

RUN meteor npm install

COPY . .

EXPOSE 3000

CMD ["meteor", "run", "--production", "--port", "3000"]