import fs from 'fs';
import path from 'path';

export function serveSwagger(req, res) {
  try {
    const swaggerPath = path.join(process.cwd(), 'openapi.yaml');
    const swaggerContent = fs.readFileSync(swaggerPath, 'utf8');
    res.setHeader('Content-Type', 'application/yaml');
    res.send(swaggerContent);
  } catch (err) {
    res.status(500).json({ error: 'Failed to load swagger spec' });
  }
}

export function serveSwaggerUI(req, res) {
  const html = `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PodcastHub API Documentation</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css">
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
  <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
  <script>
    window.onload = function() {
      SwaggerUIBundle({
        url: '/swagger',
        dom_id: '#swagger-ui',
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        layout: "StandaloneLayout"
      });
    };
  </script>
</body>
</html>
  `;
  res.setHeader('Content-Type', 'text/html');
  res.send(html);
}