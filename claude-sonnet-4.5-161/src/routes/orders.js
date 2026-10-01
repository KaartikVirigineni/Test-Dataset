const Router = require('koa-router');
const { getDb } = require('../database');
const { authenticate, authorize } = require('../middleware/auth');

const router = new Router();

router.get('/', authenticate, async (ctx) => {
  const db = getDb();
  let orders;

  if (ctx.state.user.role === 'admin') {
    orders = db.prepare(`
      SELECT o.*, u.email as user_email, u.name as user_name
      FROM orders o
      JOIN users u ON o.user_id = u.id
      ORDER BY o.created_at DESC
    `).all();
  } else {
    orders = db.prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC').all(ctx.state.user.userId);
  }

  const ordersWithItems = orders.map(order => {
    const items = db.prepare(`
      SELECT oi.*, p.name as product_name
      FROM order_items oi
      JOIN products p ON oi.product_id = p.id
      WHERE oi.order_id = ?
    `).all(order.id);
    return { ...order, items };
  });

  ctx.body = { orders: ordersWithItems };
});

router.get('/:id', authenticate, async (ctx) => {
  const db = getDb();
  const order = db.prepare('SELECT * FROM orders WHERE id = ?').get(ctx.params.id);

  if (!order) {
    ctx.status = 404;
    ctx.body = { error: 'Order not found' };
    return;
  }

  if (ctx.state.user.role !== 'admin' && order.user_id !== ctx.state.user.userId) {
    ctx.status = 403;
    ctx.body = { error: 'Access denied' };
    return;
  }

  const items = db.prepare(`
    SELECT oi.*, p.name as product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
  `).all(order.id);

  ctx.body = { order: { ...order, items } };
});

router.post('/', authenticate, async (ctx) => {
  const { items } = ctx.request.body;

  if (!items || !Array.isArray(items) || items.length === 0) {
    ctx.status = 400;
    ctx.body = { error: 'Items array is required' };
    return;
  }

  const db = getDb();
  let totalAmount = 0;

  for (const item of items) {
    if (!item.product_id || !item.quantity || item.quantity <= 0) {
      ctx.status = 400;
      ctx.body = { error: 'Invalid item format' };
      return;
    }

    const product = db.prepare('SELECT * FROM products WHERE id = ? AND deleted_at IS NULL').get(item.product_id);
    if (!product) {
      ctx.status = 404;
      ctx.body = { error: `Product ${item.product_id} not found` };
      return;
    }

    if (product.stock < item.quantity) {
      ctx.status = 400;
      ctx.body = { error: `Insufficient stock for product ${product.name}` };
      return;
    }

    totalAmount += product.price * item.quantity;
  }

  const orderResult = db.prepare(
    'INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, ?)'
  ).run(ctx.state.user.userId, totalAmount, 'pending');

  const orderId = orderResult.lastInsertRowid;

  for (const item of items) {
    const product = db.prepare('SELECT * FROM products WHERE id = ?').get(item.product_id);
    db.prepare(
      'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)'
    ).run(orderId, item.product_id, item.quantity, product.price);

    db.prepare('UPDATE products SET stock = stock - ? WHERE id = ?').run(item.quantity, item.product_id);
  }

  const order = db.prepare('SELECT * FROM orders WHERE id = ?').get(orderId);
  const orderItems = db.prepare(`
    SELECT oi.*, p.name as product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
  `).all(orderId);

  ctx.status = 201;
  ctx.body = { message: 'Order created successfully', order: { ...order, items: orderItems } };
});

router.put('/:id/status', authenticate, authorize(['admin']), async (ctx) => {
  const { status } = ctx.request.body;
  const validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

  if (!status || !validStatuses.includes(status)) {
    ctx.status = 400;
    ctx.body = { error: `Status must be one of: ${validStatuses.join(', ')}` };
    return;
  }

  const db = getDb();
  const order = db.prepare('SELECT * FROM orders WHERE id = ?').get(ctx.params.id);

  if (!order) {
    ctx.status = 404;
    ctx.body = { error: 'Order not found' };
    return;
  }

  db.prepare('UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?').run(status, ctx.params.id);

  const updatedOrder = db.prepare('SELECT * FROM orders WHERE id = ?').get(ctx.params.id);
  ctx.body = { message: 'Order status updated successfully', order: updatedOrder };
});

module.exports = router;