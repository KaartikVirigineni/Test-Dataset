<?php

use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->add(new BasePathMiddleware($app));
$app->add(new CorsMiddleware());
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Initialize database
$container->set('db', function () {
    $db = new PDO('sqlite:' . __DIR__ . '/../data/tripplanner.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    $db->exec('CREATE TABLE IF NOT EXISTS trips (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        start_date TEXT,
        end_date TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id)
    )');
    
    $db->exec('CREATE TABLE IF NOT EXISTS itinerary_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        trip_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        location TEXT,
        scheduled_at TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trip_id) REFERENCES trips (id) ON DELETE CASCADE
    )');
    
    return $db;
});

$container->set('jwt_secret', function () {
    return getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
});

// Serve Swagger spec
$app->get('/swagger', function (Request $request, Response $response) {
    $yaml = file_get_contents(__DIR__ . '/../openapi.yaml');
    $response->getBody()->write($yaml);
    return $response->withHeader('Content-Type', 'application/yaml');
});

// Serve Swagger UI
$app->get('/swagger-ui', function (Request $request, Response $response) {
    $html = file_get_contents(__DIR__ . '/../swagger-ui.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// Health check
$app->get('/health', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Auth routes
$app->post('/auth/register', function (Request $request, Response $response) {
    $db = $this->get('db');
    $data = $request->getParsedBody();
    
    if (!isset($data['email']) || !isset($data['password'])) {
        $response->getBody()->write(json_encode(['error' => 'Email and password required']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $db->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
        $stmt->execute([$data['email'], $hashedPassword]);
        
        $response->getBody()->write(json_encode([
            'id' => $db->lastInsertId(),
            'email' => $data['email']
        ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Email already exists']));
        return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/auth/login', function (Request $request, Response $response) {
    $db = $this->get('db');
    $jwtSecret = $this->get('jwt_secret');
    $data = $request->getParsedBody();
    
    if (!isset($data['email']) || !isset($data['password'])) {
        $response->getBody()->write(json_encode(['error' => 'Email and password required']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($data['password'], $user['password'])) {
        $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    
    $payload = [
        'sub' => $user['id'],
        'email' => $user['email'],
        'iat' => time(),
        'exp' => time() + (60 * 60 * 24 * 7) // 7 days
    ];
    
    $token = \Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
    
    $response->getBody()->write(json_encode(['token' => $token]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Protected routes - Trips
$app->group('/trips', function ($group) {
    $group->get('', function (Request $request, Response $response) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        
        $stmt = $db->prepare('SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response->getBody()->write(json_encode($trips));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $group->post('', function (Request $request, Response $response) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();
        
        if (!isset($data['title'])) {
            $response->getBody()->write(json_encode(['error' => 'Title required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $stmt = $db->prepare('INSERT INTO trips (user_id, title, description, start_date, end_date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $data['title'],
            $data['description'] ?? null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null
        ]);
        
        $tripId = $db->lastInsertId();
        $stmt = $db->prepare('SELECT * FROM trips WHERE id = ?');
        $stmt->execute([$tripId]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response->getBody()->write(json_encode($trip));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    });
    
    $group->get('/{id}', function (Request $request, Response $response, array $args) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        
        $stmt = $db->prepare('SELECT * FROM trips WHERE id = ? AND user_id = ?');
        $stmt->execute([$args['id'], $userId]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$trip) {
            $response->getBody()->write(json_encode(['error' => 'Trip not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode($trip));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $group->put('/{id}', function (Request $request, Response $response, array $args) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();
        
        $stmt = $db->prepare('SELECT * FROM trips WHERE id = ? AND user_id = ?');
        $stmt->execute([$args['id'], $userId]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$trip) {
            $response->getBody()->write(json_encode(['error' => 'Trip not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $stmt = $db->prepare('UPDATE trips SET title = ?, description = ?, start_date = ?, end_date = ? WHERE id = ?');
        $stmt->execute([
            $data['title'] ?? $trip['title'],
            $data['description'] ?? $trip['description'],
            $data['start_date'] ?? $trip['start_date'],
            $data['end_date'] ?? $trip['end_date'],
            $args['id']
        ]);
        
        $stmt = $db->prepare('SELECT * FROM trips WHERE id = ?');
        $stmt->execute([$args['id']]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response->getBody()->write(json_encode($updated));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $group->delete('/{id}', function (Request $request, Response $response, array $args) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        
        $stmt = $db->prepare('DELETE FROM trips WHERE id = ? AND user_id = ?');
        $stmt->execute([$args['id'], $userId]);
        
        if ($stmt->rowCount() === 0) {
            $response->getBody()->write(json_encode(['error' => 'Trip not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        return $response->withStatus(204);
    });
    
    // Itinerary items
    $group->get('/{id}/items', function (Request $request, Response $response, array $args) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        
        $stmt = $db->prepare('SELECT * FROM trips WHERE id = ? AND user_id = ?');
        $stmt->execute([$args['id'], $userId]);
        if (!$stmt->fetch()) {
            $response->getBody()->write(json_encode(['error' => 'Trip not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $stmt = $db->prepare('SELECT * FROM itinerary_items WHERE trip_id = ? ORDER BY scheduled_at');
        $stmt->execute([$args['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response->getBody()->write(json_encode($items));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $group->post('/{id}/items', function (Request $request, Response $response, array $args) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();
        
        $stmt = $db->prepare('SELECT * FROM trips WHERE id = ? AND user_id = ?');
        $stmt->execute([$args['id'], $userId]);
        if (!$stmt->fetch()) {
            $response->getBody()->write(json_encode(['error' => 'Trip not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if (!isset($data['title'])) {
            $response->getBody()->write(json_encode(['error' => 'Title required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $stmt = $db->prepare('INSERT INTO itinerary_items (trip_id, title, description, location, scheduled_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $args['id'],
            $data['title'],
            $data['description'] ?? null,
            $data['location'] ?? null,
            $data['scheduled_at'] ?? null
        ]);
        
        $itemId = $db->lastInsertId();
        $stmt = $db->prepare('SELECT * FROM itinerary_items WHERE id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response->getBody()->write(json_encode($item));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    });
})->add(new AuthMiddleware($container));

$app->group('/items', function ($group) {
    $group->get('/{id}', function (Request $request, Response $response, array $args) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        
        $stmt = $db->prepare('
            SELECT i.* FROM itinerary_items i
            JOIN trips t ON i.trip_id = t.id
            WHERE i.id = ? AND t.user_id = ?
        ');
        $stmt->execute([$args['id'], $userId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode($item));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $group->put('/{id}', function (Request $request, Response $response, array $args) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();
        
        $stmt = $db->prepare('
            SELECT i.* FROM itinerary_items i
            JOIN trips t ON i.trip_id = t.id
            WHERE i.id = ? AND t.user_id = ?
        ');
        $stmt->execute([$args['id'], $userId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $stmt = $db->prepare('UPDATE itinerary_items SET title = ?, description = ?, location = ?, scheduled_at = ? WHERE id = ?');
        $stmt->execute([
            $data['title'] ?? $item['title'],
            $data['description'] ?? $item['description'],
            $data['location'] ?? $item['location'],
            $data['scheduled_at'] ?? $item['scheduled_at'],
            $args['id']
        ]);
        
        $stmt = $db->prepare('SELECT * FROM itinerary_items WHERE id = ?');
        $stmt->execute([$args['id']]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response->getBody()->write(json_encode($updated));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $group->delete('/{id}', function (Request $request, Response $response, array $args) {
        $db = $this->get('db');
        $userId = $request->getAttribute('user_id');
        
        $stmt = $db->prepare('
            DELETE FROM itinerary_items
            WHERE id = ? AND trip_id IN (SELECT id FROM trips WHERE user_id = ?)
        ');
        $stmt->execute([$args['id'], $userId]);
        
        if ($stmt->rowCount() === 0) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        return $response->withStatus(204);
    });
})->add(new AuthMiddleware($container));

$app->run();