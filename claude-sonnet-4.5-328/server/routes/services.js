import express from 'express';
import db from '../db';
import { authenticateToken } from '../middleware/auth';

const router = express.Router();

router.use(authenticateToken);

router.get('/', (req, res) => {
  try {
    const services = db.prepare('SELECT * FROM services WHERE user_id = ? ORDER BY created_at DESC').all(req.user.id);
    res.json({ services });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch services', details: err.message });
  }
});

router.get('/:id', (req, res) => {
  try {
    const service = db.prepare('SELECT * FROM services WHERE id = ? AND user_id = ?').get(req.params.id, req.user.id);
    if (!service) {
      return res.status(404).json({ error: 'Service not found' });
    }
    res.json({ service });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch service', details: err.message });
  }
});

router.post('/', (req, res) => {
  try {
    const { name, url, check_interval } = req.body;

    if (!name || !url) {
      return res.status(400).json({ error: 'Name and URL required' });
    }

    const interval = check_interval || 60;
    const result = db.prepare(
      'INSERT INTO services (user_id, name, url, check_interval) VALUES (?, ?, ?, ?)'
    ).run(req.user.id, name, url, interval);

    const service = db.prepare('SELECT * FROM services WHERE id = ?').get(result.lastInsertRowid);
    res.status(201).json({ service });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create service', details: err.message });
  }
});

router.put('/:id', (req, res) => {
  try {
    const service = db.prepare('SELECT * FROM services WHERE id = ? AND user_id = ?').get(req.params.id, req.user.id);
    if (!service) {
      return res.status(404).json({ error: 'Service not found' });
    }

    const { name, url, check_interval, status } = req.body;
    const updates = [];
    const values = [];

    if (name !== undefined) {
      updates.push('name = ?');
      values.push(name);
    }
    if (url !== undefined) {
      updates.push('url = ?');
      values.push(url);
    }
    if (check_interval !== undefined) {
      updates.push('check_interval = ?');
      values.push(check_interval);
    }
    if (status !== undefined) {
      updates.push('status = ?');
      values.push(status);
    }

    if (updates.length === 0) {
      return res.status(400).json({ error: 'No fields to update' });
    }

    values.push(req.params.id);
    db.prepare(`UPDATE services SET ${updates.join(', ')} WHERE id = ?`).run(...values);

    const updated = db.prepare('SELECT * FROM services WHERE id = ?').get(req.params.id);
    res.json({ service: updated });
  } catch (err) {
    res.status(500).json({ error: 'Failed to update service', details: err.message });
  }
});

router.delete('/:id', (req, res) => {
  try {
    const service = db.prepare('SELECT * FROM services WHERE id = ? AND user_id = ?').get(req.params.id, req.user.id);
    if (!service) {
      return res.status(404).json({ error: 'Service not found' });
    }

    db.prepare('DELETE FROM checks WHERE service_id = ?').run(req.params.id);
    db.prepare('DELETE FROM services WHERE id = ?').run(req.params.id);

    res.json({ message: 'Service deleted successfully' });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete service', details: err.message });
  }
});

export default router;