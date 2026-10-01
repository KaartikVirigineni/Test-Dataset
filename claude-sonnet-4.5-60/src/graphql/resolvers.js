const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { getDb } = require('../db');

const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key-change-in-production';

function getUserFromContext(context) {
  const authHeader = context.headers.authorization;
  
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    throw new Error('No token provided');
  }

  const token = authHeader.substring(7);

  try {
    return jwt.verify(token, JWT_SECRET);
  } catch (err) {
    throw new Error('Invalid or expired token');
  }
}

const resolvers = {
  async register({ username, password }) {
    if (!username || !password) {
      throw new Error('Username and password are required');
    }

    if (password.length < 6) {
      throw new Error('Password must be at least 6 characters');
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

      return {
        id: result.lastInsertRowid,
        username,
        token
      };
    } catch (err) {
      if (err.message.includes('UNIQUE constraint failed')) {
        throw new Error('Username already exists');
      }
      throw err;
    }
  },

  async login({ username, password }) {
    if (!username || !password) {
      throw new Error('Username and password are required');
    }

    const db = getDb();
    const stmt = db.prepare('SELECT * FROM users WHERE username = ?');
    const user = stmt.get(username);

    if (!user) {
      throw new Error('Invalid credentials');
    }

    const validPassword = await bcrypt.compare(password, user.password);

    if (!validPassword) {
      throw new Error('Invalid credentials');
    }

    const token = jwt.sign(
      { id: user.id, username: user.username },
      JWT_SECRET,
      { expiresIn: '7d' }
    );

    return {
      id: user.id,
      username: user.username,
      token
    };
  },

  async tasks({ status, priority }, context) {
    const user = getUserFromContext(context);
    const db = getDb();
    
    let query = 'SELECT * FROM tasks WHERE user_id = ?';
    const params = [user.id];

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
    return stmt.all(...params);
  },

  async task({ id }, context) {
    const user = getUserFromContext(context);
    const db = getDb();
    const stmt = db.prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
    const task = stmt.get(id, user.id);

    if (!task) {
      throw new Error('Task not found');
    }

    return task;
  },

  async createTask({ input }, context) {
    const user = getUserFromContext(context);
    const { title, description, status, priority } = input;

    if (!title) {
      throw new Error('Title is required');
    }

    const validStatuses = ['pending', 'in_progress', 'completed'];
    const validPriorities = ['low', 'medium', 'high'];

    if (status && !validStatuses.includes(status)) {
      throw new Error('Invalid status');
    }

    if (priority && !validPriorities.includes(priority)) {
      throw new Error('Invalid priority');
    }

    const db = getDb();
    const stmt = db.prepare(
      'INSERT INTO tasks (user_id, title, description, status, priority) VALUES (?, ?, ?, ?, ?)'
    );
    
    const result = stmt.run(
      user.id,
      title,
      description || null,
      status || 'pending',
      priority || 'medium'
    );

    return db.prepare('SELECT * FROM tasks WHERE id = ?').get(result.lastInsertRowid);
  },

  async updateTask({ id, input }, context) {
    const user = getUserFromContext(context);
    const { title, description, status, priority } = input;
    const db = getDb();

    const existing = db.prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?')
      .get(id, user.id);

    if (!existing) {
      throw new Error('Task not found');
    }

    const validStatuses = ['pending', 'in_progress', 'completed'];
    const validPriorities = ['low', 'medium', 'high'];

    if (status && !validStatuses.includes(status)) {
      throw new Error('Invalid status');
    }

    if (priority && !validPriorities.includes(priority)) {
      throw new Error('Invalid priority');
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
      id,
      user.id
    );

    return db.prepare('SELECT * FROM tasks WHERE id = ?').get(id);
  },

  async deleteTask({ id }, context) {
    const user = getUserFromContext(context);
    const db = getDb();
    const stmt = db.prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
    const result = stmt.run(id, user.id);

    if (result.changes === 0) {
      throw new Error('Task not found');
    }

    return true;
  }
};

module.exports = resolvers;