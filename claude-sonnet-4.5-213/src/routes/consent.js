const Router = require('koa-router');
const Joi = require('joi');
const db = require('../db');
const { authMiddleware } = require('../middleware/auth');

const router = new Router();

router.use(authMiddleware);

const consentSchema = Joi.object({
  cookie_id: Joi.number().integer().required(),
  granted: Joi.boolean().required()
});

const batchConsentSchema = Joi.object({
  consents: Joi.array().items(Joi.object({
    cookie_id: Joi.number().integer().required(),
    granted: Joi.boolean().required()
  })).required()
});

router.post('/', async (ctx) => {
  const { error, value } = consentSchema.validate(ctx.request.body);
  
  if (error) {
    ctx.status = 400;
    ctx.body = { error: error.details[0].message };
    return;
  }

  const { cookie_id, granted } = value;
  const userId = ctx.state.user.id;
  const ipAddress = ctx.request.ip;
  const userAgent = ctx.request.headers['user-agent'];

  const cookie = db.prepare('SELECT * FROM cookies WHERE id = ?').get(cookie_id);
  if (!cookie) {
    ctx.status = 404;
    ctx.body = { error: 'Cookie not found' };
    return;
  }

  try {
    const stmt = db.prepare(`
      INSERT INTO consents (user_id, cookie_id, granted, ip_address, user_agent)
      VALUES (?, ?, ?, ?, ?)
      ON CONFLICT(user_id, cookie_id) 
      DO UPDATE SET granted = ?, consent_date = CURRENT_TIMESTAMP, ip_address = ?, user_agent = ?
    `);
    stmt.run(userId, cookie_id, granted ? 1 : 0, ipAddress, userAgent, granted ? 1 : 0, ipAddress, userAgent);

    const logStmt = db.prepare(`
      INSERT INTO consent_logs (user_id, action, details)
      VALUES (?, ?, ?)
    `);
    logStmt.run(userId, granted ? 'GRANT' : 'REVOKE', JSON.stringify({ cookie_id, cookie_name: cookie.name }));

    const consent = db.prepare(`
      SELECT c.*, ck.name as cookie_name, ck.category 
      FROM consents c
      JOIN cookies ck ON c.cookie_id = ck.id
      WHERE c.user_id = ? AND c.cookie_id = ?
    `).get(userId, cookie_id);

    ctx.status = 200;
    ctx.body = { message: 'Consent recorded successfully', consent };
  } catch (err) {
    ctx.status = 500;
    ctx.body = { error: 'Failed to record consent' };
  }
});

router.post('/batch', async (ctx) => {
  const { error, value } = batchConsentSchema.validate(ctx.request.body);
  
  if (error) {
    ctx.status = 400;
    ctx.body = { error: error.details[0].message };
    return;
  }

  const { consents } = value;
  const userId = ctx.state.user.id;
  const ipAddress = ctx.request.ip;
  const userAgent = ctx.request.headers['user-agent'];

  try {
    const insertStmt = db.prepare(`
      INSERT INTO consents (user_id, cookie_id, granted, ip_address, user_agent)
      VALUES (?, ?, ?, ?, ?)
      ON CONFLICT(user_id, cookie_id) 
      DO UPDATE SET granted = ?, consent_date = CURRENT_TIMESTAMP, ip_address = ?, user_agent = ?
    `);

    const logStmt = db.prepare(`
      INSERT INTO consent_logs (user_id, action, details)
      VALUES (?, ?, ?)
    `);

    const transaction = db.transaction((consentsArray) => {
      for (const consent of consentsArray) {
        const { cookie_id, granted } = consent;
        const cookie = db.prepare('SELECT * FROM cookies WHERE id = ?').get(cookie_id);
        
        if (cookie) {
          insertStmt.run(
            userId, cookie_id, granted ? 1 : 0, ipAddress, userAgent,
            granted ? 1 : 0, ipAddress, userAgent
          );
          logStmt.run(userId, 'BATCH_UPDATE', JSON.stringify({ cookie_id, granted }));
        }
      }
    });

    transaction(consents);

    ctx.body = { message: 'Batch consents recorded successfully', count: consents.length };
  } catch (err) {
    ctx.status = 500;
    ctx.body = { error: 'Failed to record batch consents' };
  }
});

router.get('/', async (ctx) => {
  const consents = db.prepare(`
    SELECT 
      c.id,
      c.granted,
      c.consent_date,
      ck.id as cookie_id,
      ck.name as cookie_name,
      ck.category,
      ck.is_essential
    FROM consents c
    JOIN cookies ck ON c.cookie_id = ck.id
    WHERE c.user_id = ?
    ORDER BY c.consent_date DESC
  `).all(ctx.state.user.id);

  ctx.body = { consents };
});

router.delete('/:cookie_id', async (ctx) => {
  const userId = ctx.state.user.id;
  const cookieId = ctx.params.cookie_id;

  try {
    const stmt = db.prepare('DELETE FROM consents WHERE user_id = ? AND cookie_id = ?');
    const result = stmt.run(userId, cookieId);

    if (result.changes === 0) {
      ctx.status = 404;
      ctx.body = { error: 'Consent not found' };
      return;
    }

    const logStmt = db.prepare(`
      INSERT INTO consent_logs (user_id, action, details)
      VALUES (?, ?, ?)
    `);
    logStmt.run(userId, 'DELETE', JSON.stringify({ cookie_id: cookieId }));

    ctx.body = { message: 'Consent deleted successfully' };
  } catch (err) {
    ctx.status = 500;
    ctx.body = { error: 'Failed to delete consent' };
  }
});

module.exports = router;