const Router = require('@koa/router');
const { getDb } = require('../db');
const { authenticate } = require('../middleware/auth');

const router = new Router();

router.get('/api/customers', authenticate, async (ctx) => {
  const db = getDb();
  const customers = db.prepare('SELECT * FROM customers WHERE user_id = ? ORDER BY created_at DESC').all(ctx.state.user.id);
  
  ctx.body = { customers };
});

router.get('/api/customers/:id', authenticate, async (ctx) => {
  const db = getDb();
  const customer = db.prepare('SELECT * FROM customers WHERE id = ? AND user_id = ?').get(ctx.params.id, ctx.state.user.id);
  
  if (!customer) {
    ctx.status = 404;
    ctx.body = { error: 'Customer not found' };
    return;
  }

  ctx.body = { customer };
});

router.post('/api/customers', authenticate, async (ctx) => {
  const { name, email, phone, company, status } = ctx.request.body;

  if (!name || !email) {
    ctx.status = 400;
    ctx.body = { error: 'Name and email are required' };
    return;
  }

  const db = getDb();
  
  try {
    const result = db.prepare(
      'INSERT INTO customers (name, email, phone, company, status, user_id) VALUES (?, ?, ?, ?, ?, ?)'
    ).run(name, email, phone || null, company || null, status || 'active', ctx.state.user.id);

    const customer = db.prepare('SELECT * FROM customers WHERE id = ?').get(result.lastInsertRowid);

    ctx.status = 201;
    ctx.body = { message: 'Customer created successfully', customer };
  } catch (err) {
    if (err.message.includes('UNIQUE constraint failed')) {
      ctx.status = 409;
      ctx.body = { error: 'Email already exists' };
    } else {
      throw err;
    }
  }
});

router.put('/api/customers/:id', authenticate, async (ctx) => {
  const { name, email, phone, company, status } = ctx.request.body;
  const db = getDb();

  const customer = db.prepare('SELECT * FROM customers WHERE id = ? AND user_id = ?').get(ctx.params.id, ctx.state.user.id);
  
  if (!customer) {
    ctx.status = 404;
    ctx.body = { error: 'Customer not found' };
    return;
  }

  try {
    db.prepare(
      'UPDATE customers SET name = ?, email = ?, phone = ?, company = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
    ).run(
      name || customer.name,
      email || customer.email,
      phone !== undefined ? phone : customer.phone,
      company !== undefined ? company : customer.company,
      status || customer.status,
      ctx.params.id
    );

    const updated = db.prepare('SELECT * FROM customers WHERE id = ?').get(ctx.params.id);

    ctx.body = { message: 'Customer updated successfully', customer: updated };
  } catch (err) {
    if (err.message.includes('UNIQUE constraint failed')) {
      ctx.status = 409;
      ctx.body = { error: 'Email already exists' };
    } else {
      throw err;
    }
  }
});

router.delete('/api/customers/:id', authenticate, async (ctx) => {
  const db = getDb();
  const customer = db.prepare('SELECT * FROM customers WHERE id = ? AND user_id = ?').get(ctx.params.id, ctx.state.user.id);
  
  if (!customer) {
    ctx.status = 404;
    ctx.body = { error: 'Customer not found' };
    return;
  }

  db.prepare('DELETE FROM customers WHERE id = ?').run(ctx.params.id);

  ctx.body = { message: 'Customer deleted successfully' };
});

module.exports = router;