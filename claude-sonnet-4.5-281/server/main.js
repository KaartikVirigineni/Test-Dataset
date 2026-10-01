import { Meteor } from 'meteor/meteor';
import { WebApp } from 'meteor/webapp';
import express from 'express';
import bodyParser from 'body-parser';
import { initDatabase } from './db';
import authRoutes from './routes/auth';
import podcastRoutes from './routes/podcasts';
import episodeRoutes from './routes/episodes';
import { serveSwagger, serveSwaggerUI } from './routes/swagger';

Meteor.startup(() => {
  initDatabase();

  const app = express();
  
  app.use(bodyParser.json());
  app.use(bodyParser.urlencoded({ extended: true }));

  app.get('/health', (req, res) => {
    res.json({ status: 'ok' });
  });

  app.use('/api/auth', authRoutes);
  app.use('/api/podcasts', podcastRoutes);
  app.use('/api/episodes', episodeRoutes);
  
  app.get('/swagger', serveSwagger);
  app.get('/swagger-ui', serveSwaggerUI);
  app.get('/docs', serveSwaggerUI);

  WebApp.connectHandlers.use(app);

  console.log('PodcastHub API server started on port 3000');
  console.log('Swagger documentation available at: http://localhost:3000/swagger-ui');
});