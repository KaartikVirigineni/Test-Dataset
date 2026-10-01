from pyramid.view import view_config
from pyramid.response import Response, FileResponse
import os


@view_config(route_name='swagger', renderer='string')
def swagger_spec(request):
    spec_path = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'openapi.yaml')
    with open(spec_path, 'r') as f:
        content = f.read()
    return Response(content, content_type='application/yaml')


@view_config(route_name='swagger_ui', renderer='string')
def swagger_ui(request):
    html = """
    <!DOCTYPE html>
    <html>
    <head>
        <title>DevPortal API Documentation</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.9.0/swagger-ui.css">
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
        <script>
            SwaggerUIBundle({
                url: '/swagger',
                dom_id: '#swagger-ui',
            });
        </script>
    </body>
    </html>
    """
    return Response(html, content_type='text/html')