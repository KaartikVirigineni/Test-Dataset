<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MockAPI Server</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; line-height: 1.6; }
        .container { max-width: 800px; margin: 50px auto; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 10px; }
        p { margin-bottom: 20px; color: #666; }
        .links { margin-top: 30px; }
        .links a { display: inline-block; margin-right: 20px; color: #3498db; text-decoration: none; font-weight: 500; }
        .links a:hover { text-decoration: underline; }
        .endpoint { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; font-family: monospace; }
        .method { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; margin-right: 10px; }
        .method.post { background: #28a745; color: white; }
        .method.get { background: #007bff; color: white; }
        .method.put { background: #ffc107; color: #333; }
        .method.delete { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 MockAPI Server</h1>
        <p>Production-ready REST API mock server with authentication and RBAC</p>
        
        <div class="links">
            <a href="/docs">📖 Swagger UI</a>
            <a href="/swagger">📄 OpenAPI Spec</a>
        </div>

        <h2 style="margin-top: 30px; margin-bottom: 15px;">Quick Start</h2>
        
        <div class="endpoint">
            <span class="method post">POST</span> /api/auth/register
        </div>
        
        <div class="endpoint">
            <span class="method post">POST</span> /api/auth/login
        </div>
        
        <div class="endpoint">
            <span class="method get">GET</span> /api/auth/me <small>(requires token)</small>
        </div>
        
        <div class="endpoint">
            <span class="method get">GET</span> /api/mocks <small>(requires token)</small>
        </div>
        
        <div class="endpoint">
            <span class="method post">POST</span> /api/mocks <small>(requires token)</small>
        </div>
        
        <p style="margin-top: 20px; font-size: 14px; color: #888;">
            Default credentials: admin@example.com / admin123 or user@example.com / user123
        </p>
    </div>
</body>
</html>