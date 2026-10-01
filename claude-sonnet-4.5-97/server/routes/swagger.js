import express from 'express';
import path from 'path';
import fs from 'fs';
import yaml from 'js-yaml';

export const swaggerRouter = express.Router();

swaggerRouter.get('/swagger', (req, res) => {
  try {
    const swaggerPath = path.join(process.cwd(), 'openapi.yaml');
    const fileContents = fs.readFileSync(swaggerPath, 'utf8');
    res.setHeader('Content-Type', 'application/yaml');
    res.send(fileContents);
  } catch (err) {
    res.status(500).json({ error: 'Failed to load API specification' });
  }
});

swaggerRouter.get('/swagger-ui', (req, res) => {
  res.send(`
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>AlertHub API Documentation</title>
      <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui.css">
    </head>
    <body>
      <div id="swagger-ui"></div>
      <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-bundle.js"></script>
      <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-standalone-preset.js"></script>
      <script>
        window.onload = function() {
          SwaggerUIBundle({
            url: "/swagger",
            dom_id: '#swagger-ui',
            presets: [
              SwaggerUIBundle.presets.apis,
              SwaggerUIStandalonePreset
            ],
            layout: "BaseLayout"
          });
        };
      </script>
    </body>
    </html>
  `);
});