import { Meteor } from 'meteor/meteor';
import { WebApp } from 'meteor/webapp';
import express from 'express';
import bodyParser from 'body-parser';
import fs from 'fs';
import path from 'path';
import yaml from 'js-yaml';

import './collections';
import './auth';
import './routes/products';
import './routes/orders';
import './routes/users';
import './routes/auth';
import './seed';

Meteor.startup(() => {
  console.log('MeteorMart E-commerce API started on port 3000');
  
  const app = express();
  app.use(bodyParser.json());
  app.use(bodyParser.urlencoded({ extended: true }));

  // Swagger endpoint
  app.get('/swagger', (req, res) => {
    try {
      const swaggerPath = path.join(process.cwd(), 'openapi.yaml');
      const swaggerContent = fs.readFileSync(swaggerPath, 'utf8');
      res.setHeader('Content-Type', 'application/yaml');
      res.send(swaggerContent);
    } catch (error) {
      res.status(500).json({ error: 'Swagger file not found' });
    }
  });

  // Swagger UI (simple HTML)
  app.get('/docs', (req, res) => {
    res.setHeader('Content-Type', 'text/html');
    res.send(`
      <!DOCTYPE html>
      <html lang="en">
      <head>
        <meta charset="UTF-8">
        <title>MeteorMart API Documentation</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
      </head>
      <body>
        <div id="swagger-ui"></div>
        <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
        <script>
          SwaggerUIBundle({
            url: '/swagger',
            dom_id: '#swagger-ui',
          });
        </script>
      </body>
      </html>
    `);
  });

  WebApp.connectHandlers.use(app);
});