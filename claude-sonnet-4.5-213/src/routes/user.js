const Router = require('koa-router');
const db = require('../db');
const { authMiddleware } = require('../middleware/auth');

const router = new Router();

router.use(authMiddleware);

router.get('/me', async (ctx) => {
  const user = db.prepare('SELECT id, email, role, created_at FROM users WHERE id = ?')
    .get(ctx.state.user.id);

  if (!user) {
    ctx.status = 404;
    ctx.body = { error: 'User not found' };
    return;
  }

  ctx.body = { user };
});

router.get('/me/consents', async (ctx) => {
  const consents = db.prepare(`
    SELECT 
      c.id as consent_id,
      c.granted,
      c.consent_date,
      ck.id as cookie_id,
      ck.name as cookie_name,
      ck.category,
      ck.description,
      ck.is_essential
    FROM consents c
    JOIN cookies ck ON c.cookie_id = ck.id
    WHERE c.user_id = ?
    ORDER BY c.consent_date DESC
  `).all(ctx.state.user.id);

  ctx.body = { consents };
});

router.get('/me/logs', async (ctx) => {
  const logs = db.prepare(`
    SELECT id, action, details, timestamp
    FROM consent_logs
    WHERE user_id = ?
    ORDER BY timestamp DESC
    LIMIT 100
  `).all(ctx.state.user.id);

  ctx.body = { logs };
});

module.exports = router;