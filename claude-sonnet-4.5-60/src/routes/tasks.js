const Router = require('koa-router');
const authMiddleware = require('../middleware/auth');
const { getDb } = require('../db');

const router = new Router({ prefix: '/api/tasks' });

router.use(authMiddleware);

router.get('/', async (ctx) => {
  const db = getDb();
  const { status, priority } = ctx.query;
  
  let query = 'SELECT * FROM tasks WHERE user_id = ?';
  const params = [ctx.state.user.id];

  if (status) {
    query += ' AND status = ?';
    params.push(status);
  }

  if (priority) {
    query += ' AND priority = ?';
    params.push(priority);
  }

  query += ' ORDER BY created_at DESC';

  const stmt = db.prepare(query);
  const tasks = stmt.all(...params);

  ctx.body = tasks;
});

router.get('/:id', async (ctx) => {
  const db = getDb();
  const stmt = db.prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
  const task = stmt.get(ctx.params.id, ctx.state.user.id);

  if (!task) {
    ctx.status = 404;
    ctx.body = { error: 'Task not found' };
    return;
  }

  ctx.body = task;
});

router.post('/', async (ctx) => {
  const { title, description, status, priority } = ctx.request.body;

  if (!title) {
    ctx.status = 400;
    ctx.body = { error: 'Title is required' };
    return;
  }

  const validStatuses = ['pending', 'in_progress', 'completed'];
  const validPriorities = ['low', 'medium', 'high'];

  if (status && !validStatuses.includes(status)) {
    ctx.status = 400;
    ctx.body = { error: 'Invalid status' };
    return;
  }

  if (priority && !validPriorities.includes(priority)) {
    ctx.status = 400;
    ctx.body = { error: 'Invalid priority' };
    return;
  }

  const db = getDb();
  const stmt = db.prepare(
    'INSERT INTO tasks (user_id, title, description, status, priority) VALUES (?, ?, ?, ?, ?)'
  );
  
  const result = stmt.run(
    ctx.state.user.id,
    title,
    description || null,
    status || 'pending',
    priority || 'medium'
  );

  const newTask = db.prepare('SELECT * FROM tasks WHERE id = ?').get(result.lastInsertRowid);

  ctx.status = 201;
  ctx.body = newTask;
});

router.put('/:id', async (ctx) => {
  const { title, description, status, priority } = ctx.request.body;
  const db = getDb();

  const existing = db.prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?')
    .get(ctx.params.id, ctx.state.user.id);

  if (!existing) {
    ctx.status = 404;
    ctx.body = { error: 'Task not found' };
    return;
  }

  const validStatuses = ['pending', 'in_progress', 'completed'];
  const validPriorities = ['low', 'medium', 'high'];

  if (status && !validStatuses.includes(status)) {
    ctx.status = 400;
    ctx.body = { error: 'Invalid status' };
    return;
  }

  if (priority && !validPriorities.includes(priority)) {
    ctx.status = 400;
    ctx.body = { error: 'Invalid priority' };
    return;
  }

  const stmt = db.prepare(`
    UPDATE tasks 
    SET title = ?, description = ?, status = ?, priority = ?, updated_at = CURRENT_TIMESTAMP
    WHERE id = ? AND user_id = ?
  `);

  stmt.run(
    title !== undefined ? title : existing.title,
    description !== undefined ? description : existing.description,
    status !== undefined ? status : existing.status,
    priority !== undefined ? priority : existing.priority,
    ctx.params.id,
    ctx.state.user.id
  );

  const updated = db.prepare('SELECT * FROM tasks WHERE id = ?').get(ctx.params.id);
  ctx.body = updated;
});

router.delete('/:id', async (ctx) => {
  const db = getDb();
  const stmt = db.prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
  const result = stmt.run(ctx.params.id, ctx.state.user.id);

  if (result.changes === 0) {
    ctx.status = 404;
    ctx.body = { error: 'Task not found' };
    return;
  }

  ctx.status = 204;
});

module.exports = router;