const Router = require('koa-router');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { getDb } = require('../database');

const router = new Router();
const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key-change-in-production';

router.post('/register', async (ctx) => {
  const { email, password, name, role } = ctx.request.body;

  if (!email || !password || !name) {
    ctx.status = 400;
    ctx.body = { error: 'Email, password, and name are required' };
    return;
  }

  const db = getDb();
  const existingUser = db.prepare('SELECT * FROM users WHERE email = ?').get(email);

  if (existingUser) {
    ctx.status = 409;
    ctx.body = { error: 'User already exists' };
    return;
  }

  const hashedPassword = await bcrypt.hash(password, 10);
  const userRole = role === 'admin' ? 'admin' : 'customer';

  const result = db.prepare(
    'INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)'
  ).run(email, hashedPassword, name, userRole);

  const token = jwt.sign(
    { userId: result.lastInsertRowid, email, role: userRole },
    JWT_SECRET,
    { expiresIn: '24h' }
  );

  ctx.status = 201;
  ctx.body = {
    message: 'User registered successfully',
    token,
    user: { id: result.lastInsertRowid, email, name, role: userRole }
  };
});

router.post('/login', async (ctx) => {
  const { email, password } = ctx.request.body;

  if (!email || !password) {
    ctx.status = 400;
    ctx.body = { error: 'Email and password are required' };
    return;
  }

  const db = getDb();
  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email);

  if (!user || !(await bcrypt.compare(password, user.password))) {
    ctx.status = 401;
    ctx.body = { error: 'Invalid credentials' };
    return;
  }

  const token = jwt.sign(
    { userId: user.id, email: user.email, role: user.role },
    JWT_SECRET,
    { expiresIn: '24h' }
  );

  ctx.body = {
    message: 'Login successful',
    token,
    user: { id: user.id, email: user.email, name: user.name, role: user.role }
  };
});

module.exports = router;