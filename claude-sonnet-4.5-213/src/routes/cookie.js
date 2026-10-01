const Router = require('koa-router');
const Joi = require('joi');
const db = require('../db');
const { authMiddleware, adminMiddleware } = require('../middleware/auth');

const router = new Router();

const cookieSchema = Joi.object({
  name: Joi.string().required(),
  category: Joi.string().valid('Essential', 'Analytics', 'Marketing', 'Functional').required(),
  description: Joi.string().allow(''),
  duration: Joi.number().integer().min(0),
  vendor: Joi.string().allow(''),
  is_essential: Joi.boolean().default(false)
});

router.get('/', async (ctx) => {
  const cookies = db.prepare('SELECT * FROM cookies ORDER BY category, name').all();
  ctx.body = { cookies };
});

router.get('/:id', async (ctx) => {
  const cookie = db.prepare('SELECT * FROM cookies WHERE id = ?').get(ctx.params.id);
  
  if (!cookie) {
    ctx.status = 404;
    ctx.body = { error: 'Cookie not found' };
    return;
  }

  ctx.body = { cookie };
});

router.post('/', authMiddleware, adminMiddleware, async (ctx) => {
  const { error, value } = cookieSchema.validate(ctx.request.body);
  
  if (error) {
    ctx.status = 400;
    ctx.body = { error: error.details[0].message };
    return;
  }

  const { name, category, description, duration, vendor, is_essential } = value;

  try {
    const stmt = db.prepare(`
      INSERT INTO cookies (name, category, description, duration, vendor, is_essential)
      VALUES (?, ?, ?, ?, ?, ?)
    `);
    const result = stmt.run(
      name,
      category,
      description || null,
      duration || null,
      vendor || null,
      is_essential ? 1 : 0
    );

    const cookie = db.prepare('SELECT * FROM cookies WHERE id = ?').get(result.lastInsertRowid);

    ctx.status = 201;
    ctx.body = { message: 'Cookie created successfully', cookie };
  } catch (err) {
    ctx.status = 500;
    ctx.body = { error: 'Failed to create cookie' };
  }
});

router.put('/:id', authMiddleware, adminMiddleware, async (ctx) => {
  const { error, value } = cookieSchema.validate(ctx.request.body);
  
  if (error) {
    ctx.status = 400;
    ctx.body = { error: error.details[0].message };
    return;
  }

  const { name, category, description, duration, vendor, is_essential } = value;

  try {
    const stmt = db.prepare(`
      UPDATE cookies 
      SET name = ?, category = ?, description = ?, duration = ?, vendor = ?, 
          is_essential = ?, updated_at = CURRENT_TIMESTAMP
      WHERE id = ?
    `);
    const result = stmt.run(
      name,
      category,
      description || null,
      duration || null,
      vendor || null,
      is_essential ? 1 : 0,
      ctx.params.id
    );

    if (result.changes === 0) {
      ctx.status = 404;
      ctx.body = { error: 'Cookie not found' };
      return;
    }

    const cookie = db.prepare('SELECT * FROM cookies WHERE id = ?').get(ctx.params.id);
    ctx.body = { message: 'Cookie updated successfully', cookie };
  } catch (err) {
    ctx.status = 500;
    ctx.body = { error: 'Failed to update cookie' };
  }
});

router.delete('/:id', authMiddleware, adminMiddleware, async (ctx) => {
  try {
    const stmt = db.prepare('DELETE FROM cookies WHERE id = ?');
    const result = stmt.run(ctx.params.id);

    if (result.changes === 0) {
      ctx.status = 404;
      ctx.body = { error: 'Cookie not found' };
      return;
    }

    ctx.body = { message: 'Cookie deleted successfully' };
  } catch (err) {
    ctx.status = 500;
    ctx.body = { error: 'Failed to delete cookie' };
  }
});

module.exports = router;