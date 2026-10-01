import { authMiddleware, requireRole, authenticateUser, registerUser, generateToken } from './auth';
import { getQuery, allQuery, runQuery } from './db';

export function setupRoutes(app) {
  // Health check
  app.get('/health', (req, res) => {
    res.json({ status: 'ok', service: 'Classifieds Hub' });
  });
  
  // Auth routes
  app.post('/api/auth/register', async (req, res) => {
    try {
      const { username, password, email } = req.body;
      
      if (!username || !password || !email) {
        return res.status(400).json({ error: 'Missing required fields' });
      }
      
      const user = await registerUser(username, password, email);
      const token = generateToken(user);
      
      res.status(201).json({ user, token });
    } catch (err) {
      res.status(400).json({ error: err.message });
    }
  });
  
  app.post('/api/auth/login', async (req, res) => {
    try {
      const { username, password } = req.body;
      
      if (!username || !password) {
        return res.status(400).json({ error: 'Missing credentials' });
      }
      
      const user = await authenticateUser(username, password);
      
      if (!user) {
        return res.status(401).json({ error: 'Invalid credentials' });
      }
      
      const token = generateToken(user);
      const { password: _, ...userWithoutPassword } = user;
      
      res.json({ user: userWithoutPassword, token });
    } catch (err) {
      res.status(500).json({ error: err.message });
    }
  });
  
  // Listings routes
  app.get('/api/listings', async (req, res) => {
    try {
      const { category, status, user_id } = req.query;
      let sql = 'SELECT * FROM listings WHERE 1=1';
      const params = [];
      
      if (category) {
        sql += ' AND category = ?';
        params.push(category);
      }
      
      if (status) {
        sql += ' AND status = ?';
        params.push(status);
      }
      
      if (user_id) {
        sql += ' AND user_id = ?';
        params.push(user_id);
      }
      
      sql += ' ORDER BY created_at DESC';
      
      const listings = await allQuery(sql, params);
      res.json(listings);
    } catch (err) {
      res.status(500).json({ error: err.message });
    }
  });
  
  app.get('/api/listings/:id', async (req, res) => {
    try {
      const listing = await getQuery(
        'SELECT * FROM listings WHERE id = ?',
        [req.params.id]
      );
      
      if (!listing) {
        return res.status(404).json({ error: 'Listing not found' });
      }
      
      res.json(listing);
    } catch (err) {
      res.status(500).json({ error: err.message });
    }
  });
  
  app.post('/api/listings', authMiddleware, async (req, res) => {
    try {
      const { title, description, category, price, location } = req.body;
      
      if (!title) {
        return res.status(400).json({ error: 'Title is required' });
      }
      
      const result = await runQuery(
        `INSERT INTO listings (user_id, title, description, category, price, location) 
         VALUES (?, ?, ?, ?, ?, ?)`,
        [req.user.id, title, description, category, price, location]
      );
      
      const listing = await getQuery('SELECT * FROM listings WHERE id = ?', [result.id]);
      res.status(201).json(listing);
    } catch (err) {
      res.status(500).json({ error: err.message });
    }
  });
  
  app.put('/api/listings/:id', authMiddleware, async (req, res) => {
    try {
      const listing = await getQuery('SELECT * FROM listings WHERE id = ?', [req.params.id]);
      
      if (!listing) {
        return res.status(404).json({ error: 'Listing not found' });
      }
      
      // Check ownership or admin
      if (listing.user_id !== req.user.id && req.user.role !== 'admin') {
        return res.status(403).json({ error: 'Not authorized to update this listing' });
      }
      
      const { title, description, category, price, location, status } = req.body;
      
      await runQuery(
        `UPDATE listings 
         SET title = COALESCE(?, title),
             description = COALESCE(?, description),
             category = COALESCE(?, category),
             price = COALESCE(?, price),
             location = COALESCE(?, location),
             status = COALESCE(?, status),
             updated_at = CURRENT_TIMESTAMP
         WHERE id = ?`,
        [title, description, category, price, location, status, req.params.id]
      );
      
      const updated = await getQuery('SELECT * FROM listings WHERE id = ?', [req.params.id]);
      res.json(updated);
    } catch (err) {
      res.status(500).json({ error: err.message });
    }
  });
  
  app.delete('/api/listings/:id', authMiddleware, async (req, res) => {
    try {
      const listing = await getQuery('SELECT * FROM listings WHERE id = ?', [req.params.id]);
      
      if (!listing) {
        return res.status(404).json({ error: 'Listing not found' });
      }
      
      // Check ownership or admin
      if (listing.user_id !== req.user.id && req.user.role !== 'admin') {
        return res.status(403).json({ error: 'Not authorized to delete this listing' });
      }
      
      await runQuery('DELETE FROM listings WHERE id = ?', [req.params.id]);
      res.status(204).send();
    } catch (err) {
      res.status(500).json({ error: err.message });
    }
  });
  
  // Admin routes
  app.get('/api/admin/users', authMiddleware, requireRole('admin'), async (req, res) => {
    try {
      const users = await allQuery('SELECT id, username, email, role, created_at FROM users');
      res.json(users);
    } catch (err) {
      res.status(500).json({ error: err.message });
    }
  });
  
  app.put('/api/admin/users/:id/role', authMiddleware, requireRole('admin'), async (req, res) => {
    try {
      const { role } = req.body;
      
      if (!['user', 'admin'].includes(role)) {
        return res.status(400).json({ error: 'Invalid role' });
      }
      
      await runQuery('UPDATE users SET role = ? WHERE id = ?', [role, req.params.id]);
      const user = await getQuery('SELECT id, username, email, role FROM users WHERE id = ?', [req.params.id]);
      
      if (!user) {
        return res.status(404).json({ error: 'User not found' });
      }
      
      res.json(user);
    } catch (err) {
      res.status(500).json({ error: err.message });
    }
  });
}