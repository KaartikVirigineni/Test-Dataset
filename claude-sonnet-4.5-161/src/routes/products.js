const Router = require('koa-router');
const { getDb } = require('../database');
const { authenticate, authorize } = require('../middleware/auth');

const router = new Router();

router.get('/', async (ctx) => {
  const db = getDb();
  const products = db.prepare('SELECT * FROM products WHERE deleted_at IS NULL').all();
  ctx.body = { products };
});

router.get('/:id', async (ctx) => {
  const db = getDb();
  const product = db.prepare('SELECT * FROM products WHERE id = ? AND deleted_at IS NULL').get(ctx.params.id);

  if (!product) {
    ctx.status = 404;
    ctx.body = { error: 'Product not found' };
    return;
  }

  ctx.body = { product };
});

router.post('/', authenticate, authorize(['admin']), async (ctx) => {
  const { name, description, price, stock } = ctx.request.body;

  if (!name || price === undefined || stock === undefined) {
    ctx.status = 400;
    ctx.body = { error: 'Name, price, and stock are required' };
    return;
  }

  const db = getDb();
  const result = db.prepare(
    'INSERT INTO products (name, description, price, stock) VALUES (?, ?, ?, ?)'
  ).run(name, description || '', price, stock);

  const product = db.prepare('SELECT * FROM products WHERE id = ?').get(result.lastInsertRowid);

  ctx.status = 201;
  ctx.body = { message: 'Product created successfully', product };
});

router.put('/:id', authenticate, authorize(['admin']), async (ctx) => {
  const { name, description, price, stock } = ctx.request.body;
  const db = getDb();

  const existing = db.prepare('SELECT * FROM products WHERE id = ? AND deleted_at IS NULL').get(ctx.params.id);
  if (!existing) {
    ctx.status = 404;
    ctx.body = { error: 'Product not found' };
    return;
  }

  db.prepare(
    'UPDATE products SET name = ?, description = ?, price = ?, stock = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
  ).run(
    name !== undefined ? name : existing.name,
    description !== undefined ? description : existing.description,
    price !== undefined ? price : existing.price,
    stock !== undefined ? stock : existing.stock,
    ctx.params.id
  );

  const product = db.prepare('SELECT * FROM products WHERE id = ?').get(ctx.params.id);
  ctx.body = { message: 'Product updated successfully', product };
});

router.delete('/:id', authenticate, authorize(['admin']), async (ctx) => {
  const db = getDb();

  const existing = db.prepare('SELECT * FROM products WHERE id = ? AND deleted_at IS NULL').get(ctx.params.id);
  if (!existing) {
    ctx.status = 404;
    ctx.body = { error: 'Product not found' };
    return;
  }

  db.prepare('UPDATE products SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?').run(ctx.params.id);

  ctx.body = { message: 'Product deleted successfully' };
});

module.exports = router;