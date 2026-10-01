const Router = require('koa-router');
const { getDb } = require('../database');
const { authenticate, authorize } = require('../middleware/auth');

const router = new Router();

router.get('/', authenticate, authorize(['admin']), async (ctx) => {
  const db = getDb();
  const users = db.prepare('SELECT id, email, name, role, created_at FROM users').all();
  ctx.body = { users };
});

router.get('/me', authenticate, async (ctx) => {
  const db = getDb();
  const user = db.prepare('SELECT id, email, name, role, created_at FROM users WHERE id = ?').get(ctx.state.user.userId);

  if (!user) {
    ctx.status = 404;
    ctx.body = { error: 'User not found' };
    return;
  }

  ctx.body = { user };
});

router.get('/:id', authenticate, authorize(['admin']), async (ctx) => {
  const db = getDb();
  const user = db.prepare('SELECT id, email, name, role, created_at FROM users WHERE id = ?').get(ctx.params.id);

  if (!user) {
    ctx.status = 404;
    ctx.body = { error: 'User not found' };
    return;
  }

  ctx.body = { user };
});

module.exports = router;