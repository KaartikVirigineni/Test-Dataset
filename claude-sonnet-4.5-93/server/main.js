import { Meteor } from 'meteor/meteor';
import { WebApp } from 'meteor/webapp';
import express from 'express';
import bodyParser from 'body-parser';
import { initDatabase } from './db';
import { authRoutes } from './routes/auth';
import { inventoryRoutes } from './routes/inventory';
import { swaggerRoutes } from './routes/swagger';
import { authMiddleware } from './middleware/auth';

Meteor.startup(() => {
  initDatabase();

  const app = express();
  
  app.use(bodyParser.json());
  app.use(bodyParser.urlencoded({ extended: true }));

  app.use('/api/auth', authRoutes);
  app.use('/api/inventory', authMiddleware, inventoryRoutes);
  app.use('/', swaggerRoutes);

  app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).json({ error: 'Internal server error' });
  });

  WebApp.connectHandlers.use(app);

  console.log('InventoryHub API started');
});