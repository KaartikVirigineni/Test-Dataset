<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1 { color: #333; }
        .endpoint { 
            background: #f4f4f4; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        code { 
            background: #eee; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-size: 0.9em;
        }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Analytics Dashboard API</h1>
    <p>Production-ready analytics platform with REST and GraphQL support.</p>
    
    <h2>Available Endpoints</h2>
    
    <div class="endpoint">
        <strong>REST API Documentation:</strong><br>
        <a href="/swagger-ui" target="_blank">Swagger UI</a> | 
        <a href="/swagger" target="_blank">OpenAPI Spec</a>
    </div>
    
    <div class="endpoint">
        <strong>GraphQL Endpoint:</strong><br>
        <code>POST /graphql</code><br>
        Interactive playground: <code>GET /graphql</code>
    </div>
    
    <div class="endpoint">
        <strong>Authentication:</strong><br>
        <code>POST /api/auth/register</code><br>
        <code>POST /api/auth/login</code>
    </div>
    
    <div class="endpoint">
        <strong>Analytics Management:</strong><br>
        <code>GET /api/analytics</code> - List all analytics<br>
        <code>POST /api/analytics</code> - Create analytic<br>
        <code>GET /api/analytics/{id}</code> - Get single analytic<br>
        <code>PUT /api/analytics/{id}</code> - Update analytic<br>
        <code>DELETE /api/analytics/{id}</code> - Delete analytic<br>
        <code>GET /api/dashboard/stats</code> - Dashboard statistics
    </div>
    
    <p><small>All analytics endpoints require Bearer token authentication.</small></p>
</body>
</html>