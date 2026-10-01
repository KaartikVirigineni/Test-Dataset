import fs from 'fs';
import path from 'path';
import yaml from 'js-yaml';

let swaggerSpec = null;

function loadSwaggerSpec() {
  if (!swaggerSpec) {
    const swaggerPath = path.join(process.cwd(), 'openapi.yaml');
    const fileContent = fs.readFileSync(swaggerPath, 'utf8');
    swaggerSpec = yaml.load(fileContent);
  }
  return swaggerSpec;
}

export function swaggerHandler(req, res) {
  try {
    const spec = loadSwaggerSpec();
    res.setHeader('Content-Type', 'application/yaml');
    res.send(yaml.dump(spec));
  } catch (err) {
    res.status(500).json({ error: 'Failed to load swagger spec' });
  }
}

export function swaggerUIHandler(req, res) {
  const html = `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Classifieds Hub API Documentation</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui.css">
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-bundle.js"></script>
  <script>
    window.onload = function() {
      SwaggerUIBundle({
        url: '/swagger',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIBundle.SwaggerUIStandalonePreset
        ]
      });
    };
  </script>
</body>
</html>
  `;
  res.setHeader('Content-Type', 'text/html');
  res.send(html);
}