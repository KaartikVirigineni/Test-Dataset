import { Meteor } from 'meteor/meteor';
import { WebApp } from 'meteor/webapp';
import express from 'express';
import bodyParser from 'body-parser';
import './db';
import authRoutes from './routes/auth';
import servicesRoutes from './routes/services';
import checksRoutes from './routes/checks';
import swaggerRoute from './routes/swagger';

Meteor.startup(() => {
  const app = express();
  
  app.use(bodyParser.json());
  app.use(bodyParser.urlencoded({ extended: true }));
  
  // CORS headers
  app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
    if (req.method === 'OPTIONS') {
      return res.sendStatus(200);
    }
    next();
  });
  
  // Routes
  app.use('/api/auth', authRoutes);
  app.use('/api/services', servicesRoutes);
  app.use('/api/checks', checksRoutes);
  app.use('/', swaggerRoute);
  
  // Health check
  app.get('/health', (req, res) => {
    res.json({ status: 'ok' });
  });
  
  WebApp.connectHandlers.use(app);
  
  console.log('Uptime Monitor API started on port 3000');
});