import { Meteor } from 'meteor/meteor';
import { WebApp } from 'meteor/webapp';
import express from 'express';
import bodyParser from 'body-parser';
import { initDatabase } from './db';
import { setupRoutes } from './routes';
import { swaggerHandler, swaggerUIHandler } from './swagger';

Meteor.startup(() => {
  console.log('Starting Classifieds Hub...');
  
  // Initialize database
  initDatabase();
  
  // Create Express app
  const app = express();
  
  // Middleware
  app.use(bodyParser.json());
  app.use(bodyParser.urlencoded({ extended: true }));
  
  // CORS
  app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
    if (req.method === 'OPTIONS') {
      return res.sendStatus(200);
    }
    next();
  });
  
  // Swagger
  app.get('/swagger', swaggerHandler);
  app.get('/docs', swaggerUIHandler);
  
  // API Routes
  setupRoutes(app);
  
  // Connect to Meteor's WebApp
  WebApp.connectHandlers.use(app);
  
  console.log('Classifieds Hub started on port 3000');
});