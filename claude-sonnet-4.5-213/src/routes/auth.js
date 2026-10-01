const Router = require('koa-router');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const Joi = require('joi');
const db = require('../db');
const { JWT_SECRET } = require('../middleware/auth');

const router = new Router();

const registerSchema = Joi.object({
  email: Joi.string().email().required(),
  password: Joi.string().min(6).required(),
  role: Joi.string().valid('user', 'admin').default('user')
});

const loginSchema = Joi.object({
  email: Joi.string().email().required(),
  password: Joi.string().required()
});

router.post('/register', async (ctx) => {
  const { error, value } = registerSchema.validate(ctx.request.body);
  
  if (error) {
    ctx.status = 400;
    ctx.body = { error: error.details[0].message };
    return;
  }

  const { email, password, role } = value;

  try {
    const hashedPassword = await bcrypt.hash(password, 10);
    
    const stmt = db.prepare('INSERT INTO users (email, password, role) VALUES (?, ?, ?)');
    const result = stmt.run(email, hashedPassword, role || 'user');

    const token = jwt.sign(
      { id: result.lastInsertRowid, email, role: role || 'user' },
      JWT_SECRET,
      { expiresIn: '24h' }
    );

    ctx.status = 201;
    ctx.body = {
      message: 'User registered successfully',
      user: { id: result.lastInsertRowid, email, role: role || 'user' },
      token
    };
  } catch (err) {
    if (err.message.includes('UNIQUE constraint failed')) {
      ctx.status = 409;
      ctx.body = { error: 'Email already registered' };
    } else {
      ctx.status = 500;
      ctx.body = { error: 'Registration failed' };
    }
  }
});

router.post('/login', async (ctx) => {
  const { error, value } = loginSchema.validate(ctx.request.body);
  
  if (error) {
    ctx.status = 400;
    ctx.body = { error: error.details[0].message };
    return;
  }

  const { email, password } = value;

  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email);

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
    { id: user.id, email: user.email, role: user.role },
    JWT_SECRET,
    { expiresIn: '24h' }
  );

  ctx.body = {
    message: 'Login successful',
    user: { id: user.id, email: user.email, role: user.role },
    token
  };
});

module.exports = router;