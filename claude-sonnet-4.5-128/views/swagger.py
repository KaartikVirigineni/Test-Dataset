from pyramid.view import view_config
from pyramid.response import Response
import os

@view_config(route_name='swagger', request_method='GET')
def swagger_spec(request):
    spec_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'openapi.yaml')
    with open(spec_path, 'r') as f:
        spec_content = f.read()
    return Response(spec_content, content_type='application/yaml')

@view_config(route_name='swagger_ui', request_method='GET', renderer='string')
def swagger_ui(request):
    html = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Gist Manager API Documentation</title>
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui.css">
        <style>
            body { margin: 0; padding: 0; }
        </style>
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-bundle.js"></script>
        <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-standalone-preset.js"></script>
        <script>
            window.onload = function() {
                SwaggerUIBundle({
                    url: '/swagger',
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
    """
    return Response(html, content_type='text/html')