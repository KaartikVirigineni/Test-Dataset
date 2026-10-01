import { WebApp } from 'meteor/webapp';
import express from 'express';
import bodyParser from 'body-parser';
import Database from 'better-sqlite3';
import bcrypt from 'bcrypt';
import jwt from 'jsonwebtoken';
import fs from 'fs';
import yaml from 'js-yaml';
import path from 'path';

const JWT_SECRET = process.env.JWT_SECRET || 'clinic-booking-secret-key-change-in-production';
const db = new Database(process.env.DB_PATH || 'clinic.db');

// Initialize database
db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('admin', 'provider', 'client')),
    name TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    duration INTEGER NOT NULL,
    price REAL NOT NULL,
    provider_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    provider_id INTEGER NOT NULL,
    appointment_date DATETIME NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('pending', 'confirmed', 'cancelled', 'completed')),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (provider_id) REFERENCES users(id)
  );

  CREATE INDEX IF NOT EXISTS idx_appointments_date ON appointments(appointment_date);
  CREATE INDEX IF NOT EXISTS idx_appointments_client ON appointments(client_id);
  CREATE INDEX IF NOT EXISTS idx_appointments_provider ON appointments(provider_id);
`);

// Create default admin if not exists
const adminExists = db.prepare('SELECT id FROM users WHERE role = ?').get('admin');
if (!adminExists) {
  const hashedPassword = bcrypt.hashSync('admin123', 10);
  db.prepare('INSERT INTO users (username, password, role, name) VALUES (?, ?, ?, ?)').run(
    'admin',
    hashedPassword,
    'admin',
    'System Administrator'
  );
}

// Express app
const app = express();
app.use(bodyParser.json());

// Authentication middleware
const authenticate = (req, res, next) => {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  
  const token = authHeader.substring(7);
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    req.user = decoded;
    next();
  } catch (err) {
    return res.status(401).json({ error: 'Invalid token' });
  }
};

// Role-based access control middleware
const authorize = (...roles) => {
  return (req, res, next) => {
    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ error: 'Forbidden' });
    }
    next();
  };
};

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok' });
});

// Swagger spec
app.get('/swagger', (req, res) => {
  const swaggerPath = path.join(process.cwd(), 'openapi.yaml');
  if (fs.existsSync(swaggerPath)) {
    const spec = fs.readFileSync(swaggerPath, 'utf8');
    res.setHeader('Content-Type', 'application/yaml');
    res.send(spec);
  } else {
    res.status(404).json({ error: 'Swagger spec not found' });
  }
});

// Swagger UI
app.get('/swagger-ui', (req, res) => {
  res.setHeader('Content-Type', 'text/html');
  res.send(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>ClinicBook API</title>
      <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui.css" />
    </head>
    <body>
      <div id="swagger-ui"></div>
      <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
      <script>
        window.onload = function() {
          SwaggerUIBundle({
            url: '/swagger',
            dom_id: '#swagger-ui',
          });
        };
      </script>
    </body>
    </html>
  `);
});

// Auth endpoints
app.post('/api/auth/register', (req, res) => {
  const { username, password, name, role } = req.body;
  
  if (!username || !password || !name) {
    return res.status(400).json({ error: 'Missing required fields' });
  }
  
  const userRole = role && ['admin', 'provider', 'client'].includes(role) ? role : 'client';
  
  try {
    const hashedPassword = bcrypt.hashSync(password, 10);
    const result = db.prepare(
      'INSERT INTO users (username, password, role, name) VALUES (?, ?, ?, ?)'
    ).run(username, hashedPassword, userRole, name);
    
    res.status(201).json({
      id: result.lastInsertRowid,
      username,
      role: userRole,
      name
    });
  } catch (err) {
    if (err.message.includes('UNIQUE constraint failed')) {
      return res.status(409).json({ error: 'Username already exists' });
    }
    res.status(500).json({ error: 'Registration failed' });
  }
});

app.post('/api/auth/login', (req, res) => {
  const { username, password } = req.body;
  
  if (!username || !password) {
    return res.status(400).json({ error: 'Missing credentials' });
  }
  
  const user = db.prepare('SELECT * FROM users WHERE username = ?').get(username);
  
  if (!user || !bcrypt.compareSync(password, user.password)) {
    return res.status(401).json({ error: 'Invalid credentials' });
  }
  
  const token = jwt.sign(
    { id: user.id, username: user.username, role: user.role },
    JWT_SECRET,
    { expiresIn: '24h' }
  );
  
  res.json({
    token,
    user: {
      id: user.id,
      username: user.username,
      role: user.role,
      name: user.name
    }
  });
});

app.get('/api/auth/me', authenticate, (req, res) => {
  const user = db.prepare('SELECT id, username, role, name, created_at FROM users WHERE id = ?')
    .get(req.user.id);
  res.json(user);
});

// Users endpoints
app.get('/api/users', authenticate, authorize('admin'), (req, res) => {
  const users = db.prepare('SELECT id, username, role, name, created_at FROM users').all();
  res.json(users);
});

app.get('/api/users/:id', authenticate, (req, res) => {
  const { id } = req.params;
  
  if (req.user.role !== 'admin' && req.user.id !== parseInt(id)) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  const user = db.prepare('SELECT id, username, role, name, created_at FROM users WHERE id = ?')
    .get(id);
  
  if (!user) {
    return res.status(404).json({ error: 'User not found' });
  }
  
  res.json(user);
});

app.put('/api/users/:id', authenticate, (req, res) => {
  const { id } = req.params;
  const { name, password } = req.body;
  
  if (req.user.role !== 'admin' && req.user.id !== parseInt(id)) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  const updates = [];
  const params = [];
  
  if (name) {
    updates.push('name = ?');
    params.push(name);
  }
  
  if (password) {
    updates.push('password = ?');
    params.push(bcrypt.hashSync(password, 10));
  }
  
  if (updates.length === 0) {
    return res.status(400).json({ error: 'No fields to update' });
  }
  
  params.push(id);
  
  db.prepare(`UPDATE users SET ${updates.join(', ')} WHERE id = ?`).run(...params);
  
  const user = db.prepare('SELECT id, username, role, name, created_at FROM users WHERE id = ?')
    .get(id);
  res.json(user);
});

app.delete('/api/users/:id', authenticate, authorize('admin'), (req, res) => {
  const { id } = req.params;
  
  const result = db.prepare('DELETE FROM users WHERE id = ?').run(id);
  
  if (result.changes === 0) {
    return res.status(404).json({ error: 'User not found' });
  }
  
  res.status(204).send();
});

// Services endpoints
app.get('/api/services', (req, res) => {
  const services = db.prepare(`
    SELECT s.*, u.name as provider_name 
    FROM services s
    LEFT JOIN users u ON s.provider_id = u.id
  `).all();
  res.json(services);
});

app.get('/api/services/:id', (req, res) => {
  const { id } = req.params;
  const service = db.prepare(`
    SELECT s.*, u.name as provider_name 
    FROM services s
    LEFT JOIN users u ON s.provider_id = u.id
    WHERE s.id = ?
  `).get(id);
  
  if (!service) {
    return res.status(404).json({ error: 'Service not found' });
  }
  
  res.json(service);
});

app.post('/api/services', authenticate, authorize('admin', 'provider'), (req, res) => {
  const { name, description, duration, price, provider_id } = req.body;
  
  if (!name || !duration || !price) {
    return res.status(400).json({ error: 'Missing required fields' });
  }
  
  const finalProviderId = req.user.role === 'provider' ? req.user.id : provider_id;
  
  try {
    const result = db.prepare(
      'INSERT INTO services (name, description, duration, price, provider_id) VALUES (?, ?, ?, ?, ?)'
    ).run(name, description || null, duration, price, finalProviderId || null);
    
    const service = db.prepare('SELECT * FROM services WHERE id = ?').get(result.lastInsertRowid);
    res.status(201).json(service);
  } catch (err) {
    res.status(500).json({ error: 'Failed to create service' });
  }
});

app.put('/api/services/:id', authenticate, authorize('admin', 'provider'), (req, res) => {
  const { id } = req.params;
  const { name, description, duration, price, provider_id } = req.body;
  
  const service = db.prepare('SELECT * FROM services WHERE id = ?').get(id);
  
  if (!service) {
    return res.status(404).json({ error: 'Service not found' });
  }
  
  if (req.user.role === 'provider' && service.provider_id !== req.user.id) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  const updates = [];
  const params = [];
  
  if (name !== undefined) {
    updates.push('name = ?');
    params.push(name);
  }
  if (description !== undefined) {
    updates.push('description = ?');
    params.push(description);
  }
  if (duration !== undefined) {
    updates.push('duration = ?');
    params.push(duration);
  }
  if (price !== undefined) {
    updates.push('price = ?');
    params.push(price);
  }
  if (provider_id !== undefined && req.user.role === 'admin') {
    updates.push('provider_id = ?');
    params.push(provider_id);
  }
  
  if (updates.length === 0) {
    return res.status(400).json({ error: 'No fields to update' });
  }
  
  params.push(id);
  
  db.prepare(`UPDATE services SET ${updates.join(', ')} WHERE id = ?`).run(...params);
  
  const updatedService = db.prepare('SELECT * FROM services WHERE id = ?').get(id);
  res.json(updatedService);
});

app.delete('/api/services/:id', authenticate, authorize('admin', 'provider'), (req, res) => {
  const { id } = req.params;
  
  const service = db.prepare('SELECT * FROM services WHERE id = ?').get(id);
  
  if (!service) {
    return res.status(404).json({ error: 'Service not found' });
  }
  
  if (req.user.role === 'provider' && service.provider_id !== req.user.id) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  db.prepare('DELETE FROM services WHERE id = ?').run(id);
  res.status(204).send();
});

// Appointments endpoints
app.get('/api/appointments', authenticate, (req, res) => {
  let query = `
    SELECT a.*, 
           s.name as service_name,
           c.name as client_name,
           p.name as provider_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users c ON a.client_id = c.id
    JOIN users p ON a.provider_id = p.id
  `;
  
  const params = [];
  
  if (req.user.role === 'client') {
    query += ' WHERE a.client_id = ?';
    params.push(req.user.id);
  } else if (req.user.role === 'provider') {
    query += ' WHERE a.provider_id = ?';
    params.push(req.user.id);
  }
  
  query += ' ORDER BY a.appointment_date DESC';
  
  const appointments = db.prepare(query).all(...params);
  res.json(appointments);
});

app.get('/api/appointments/:id', authenticate, (req, res) => {
  const { id } = req.params;
  const appointment = db.prepare(`
    SELECT a.*, 
           s.name as service_name,
           c.name as client_name,
           p.name as provider_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users c ON a.client_id = c.id
    JOIN users p ON a.provider_id = p.id
    WHERE a.id = ?
  `).get(id);
  
  if (!appointment) {
    return res.status(404).json({ error: 'Appointment not found' });
  }
  
  if (req.user.role === 'client' && appointment.client_id !== req.user.id) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  if (req.user.role === 'provider' && appointment.provider_id !== req.user.id) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  res.json(appointment);
});

app.post('/api/appointments', authenticate, (req, res) => {
  const { service_id, appointment_date, notes, client_id } = req.body;
  
  if (!service_id || !appointment_date) {
    return res.status(400).json({ error: 'Missing required fields' });
  }
  
  const service = db.prepare('SELECT * FROM services WHERE id = ?').get(service_id);
  
  if (!service) {
    return res.status(404).json({ error: 'Service not found' });
  }
  
  const finalClientId = req.user.role === 'admin' && client_id ? client_id : req.user.id;
  
  try {
    const result = db.prepare(
      `INSERT INTO appointments (service_id, client_id, provider_id, appointment_date, status, notes) 
       VALUES (?, ?, ?, ?, ?, ?)`
    ).run(service_id, finalClientId, service.provider_id, appointment_date, 'pending', notes || null);
    
    const appointment = db.prepare(`
      SELECT a.*, 
             s.name as service_name,
             c.name as client_name,
             p.name as provider_name
      FROM appointments a
      JOIN services s ON a.service_id = s.id
      JOIN users c ON a.client_id = c.id
      JOIN users p ON a.provider_id = p.id
      WHERE a.id = ?
    `).get(result.lastInsertRowid);
    
    res.status(201).json(appointment);
  } catch (err) {
    res.status(500).json({ error: 'Failed to create appointment' });
  }
});

app.put('/api/appointments/:id', authenticate, (req, res) => {
  const { id } = req.params;
  const { status, notes, appointment_date } = req.body;
  
  const appointment = db.prepare('SELECT * FROM appointments WHERE id = ?').get(id);
  
  if (!appointment) {
    return res.status(404).json({ error: 'Appointment not found' });
  }
  
  if (req.user.role === 'client' && appointment.client_id !== req.user.id) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  if (req.user.role === 'provider' && appointment.provider_id !== req.user.id) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  const updates = [];
  const params = [];
  
  if (status !== undefined && ['pending', 'confirmed', 'cancelled', 'completed'].includes(status)) {
    updates.push('status = ?');
    params.push(status);
  }
  
  if (notes !== undefined) {
    updates.push('notes = ?');
    params.push(notes);
  }
  
  if (appointment_date !== undefined && (req.user.role === 'admin' || req.user.role === 'provider')) {
    updates.push('appointment_date = ?');
    params.push(appointment_date);
  }
  
  if (updates.length === 0) {
    return res.status(400).json({ error: 'No fields to update' });
  }
  
  params.push(id);
  
  db.prepare(`UPDATE appointments SET ${updates.join(', ')} WHERE id = ?`).run(...params);
  
  const updatedAppointment = db.prepare(`
    SELECT a.*, 
           s.name as service_name,
           c.name as client_name,
           p.name as provider_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users c ON a.client_id = c.id
    JOIN users p ON a.provider_id = p.id
    WHERE a.id = ?
  `).get(id);
  
  res.json(updatedAppointment);
});

app.delete('/api/appointments/:id', authenticate, (req, res) => {
  const { id } = req.params;
  
  const appointment = db.prepare('SELECT * FROM appointments WHERE id = ?').get(id);
  
  if (!appointment) {
    return res.status(404).json({ error: 'Appointment not found' });
  }
  
  if (req.user.role === 'client' && appointment.client_id !== req.user.id) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  if (req.user.role === 'provider' && appointment.provider_id !== req.user.id) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  
  db.prepare('DELETE FROM appointments WHERE id = ?').run(id);
  res.status(204).send();
});

// Connect Express to WebApp
WebApp.connectHandlers.use(app);

console.log('ClinicBook API server started');