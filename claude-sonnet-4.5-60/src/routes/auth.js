const Router = require('koa-router');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { getDb } = require('../db');

const router = new Router({ prefix: '/api/auth' });
const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key-change-in-production';

router.post('/register', async (ctx) => {
  const { username, password } = ctx.request.body;

  if (!username || !password) {
    ctx.status = 400;
    ctx.body = { error: 'Username and password are required' };
    return;
  }

  if (password.length < 6) {
    ctx.status = 400;
    ctx.body = { error: 'Password must be at least 6 characters' };
    return;
  }

  const db = getDb();
  const hashedPassword = await bcrypt.hash(password, 10);

  try {
    const stmt = db.prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    const result = stmt.run(username, hashedPassword);

    const token = jwt.sign(
      { id: result.lastInsertRowid, username },
      JWT_SECRET,
      { expiresIn: '7d' }
    );

    ctx.status = 201;
    ctx.body = {
      id: result.lastInsertRowid,
      username,
      token
    };
  } catch (err) {
    if (err.message.includes('UNIQUE constraint failed')) {
      ctx.status = 409;
      ctx.body = { error: 'Username already exists' };
    } else {
      throw err;
    }
  }
});

router.post('/login', async (ctx) => {
  const { username, password } = ctx.request.body;

  if (!username || !password) {
    ctx.status = 400;
    ctx.body = { error: 'Username and password are required' };
    return;
  }

  const db = getDb();
  const stmt = db.prepare('SELECT * FROM users WHERE username = ?');
  const user = stmt.get(username);

  if (!user) {
    ctx.status = 401;
    ctx.body = { error: 'Invalid credentials' };
    return;
  }

  const validPassword = await bcrypt.compare(password, user.password);

  if (!validPassword) {
    ctx.status = 401;
    ctx.body = { error: 'Invalid credentials' };
    return;
  }

  const token = jwt.sign(
    { id: user.id, username: user.username },
    JWT_SECRET,
    { expiresIn: '7d' }
  );

  ctx.body = {
    id: user.id,
    username: user.username,
    token
  };
});

module.exports = router;