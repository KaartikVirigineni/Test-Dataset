import express from 'express';
import { getDatabase } from '../db';
import { authenticateToken } from '../middleware/auth';

export const alertsRouter = express.Router();

alertsRouter.use(authenticateToken);

alertsRouter.get('/', (req, res) => {
  try {
    const db = getDatabase();
    const { status, severity } = req.query;
    
    let query = 'SELECT * FROM alerts WHERE user_id = ?';
    const params = [req.user.id];
    
    if (status) {
      query += ' AND status = ?';
      params.push(status);
    }
    
    if (severity) {
      query += ' AND severity = ?';
      params.push(severity);
    }
    
    query += ' ORDER BY created_at DESC';
    
    const stmt = db.prepare(query);
    const alerts = stmt.all(...params);
    
    res.json({ alerts });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch alerts' });
  }
});

alertsRouter.get('/:id', (req, res) => {
  try {
    const db = getDatabase();
    const stmt = db.prepare('SELECT * FROM alerts WHERE id = ? AND user_id = ?');
    const alert = stmt.get(req.params.id, req.user.id);
    
    if (!alert) {
      return res.status(404).json({ error: 'Alert not found' });
    }
    
    res.json({ alert });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch alert' });
  }
});

alertsRouter.post('/', (req, res) => {
  const { title, description, severity } = req.body;
  
  if (!title || !severity) {
    return res.status(400).json({ error: 'Title and severity required' });
  }
  
  const validSeverities = ['low', 'medium', 'high', 'critical'];
  if (!validSeverities.includes(severity)) {
    return res.status(400).json({ error: 'Invalid severity level' });
  }
  
  try {
    const db = getDatabase();
    const stmt = db.prepare(
      'INSERT INTO alerts (user_id, title, description, severity) VALUES (?, ?, ?, ?)'
    );
    const result = stmt.run(req.user.id, title, description || null, severity);
    
    const newAlert = db.prepare('SELECT * FROM alerts WHERE id = ?').get(result.lastInsertRowid);
    
    res.status(201).json({ alert: newAlert });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create alert' });
  }
});

alertsRouter.patch('/:id', (req, res) => {
  const { status, description } = req.body;
  
  if (!status) {
    return res.status(400).json({ error: 'Status required' });
  }
  
  const validStatuses = ['open', 'acknowledged', 'resolved', 'closed'];
  if (!validStatuses.includes(status)) {
    return res.status(400).json({ error: 'Invalid status' });
  }
  
  try {
    const db = getDatabase();
    
    const checkStmt = db.prepare('SELECT * FROM alerts WHERE id = ? AND user_id = ?');
    const existing = checkStmt.get(req.params.id, req.user.id);
    
    if (!existing) {
      return res.status(404).json({ error: 'Alert not found' });
    }
    
    const updateStmt = db.prepare(
      'UPDATE alerts SET status = ?, description = COALESCE(?, description), updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
    );
    updateStmt.run(status, description || null, req.params.id, req.user.id);
    
    const updated = checkStmt.get(req.params.id, req.user.id);
    
    res.json({ alert: updated });
  } catch (err) {
    res.status(500).json({ error: 'Failed to update alert' });
  }
});

alertsRouter.delete('/:id', (req, res) => {
  try {
    const db = getDatabase();
    const stmt = db.prepare('DELETE FROM alerts WHERE id = ? AND user_id = ?');
    const result = stmt.run(req.params.id, req.user.id);
    
    if (result.changes === 0) {
      return res.status(404).json({ error: 'Alert not found' });
    }
    
    res.status(204).send();
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete alert' });
  }
});