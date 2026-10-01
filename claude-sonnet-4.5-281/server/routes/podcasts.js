import express from 'express';
import db from '../db';
import { authenticateToken } from '../middleware/auth';

const router = express.Router();

router.get('/', (req, res) => {
  try {
    const podcasts = db.prepare(`
      SELECT p.*, u.name as author_name 
      FROM podcasts p 
      JOIN users u ON p.user_id = u.id
      ORDER BY p.created_at DESC
    `).all();

    res.json({ podcasts });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch podcasts', details: err.message });
  }
});

router.get('/:id', (req, res) => {
  try {
    const podcast = db.prepare(`
      SELECT p.*, u.name as author_name 
      FROM podcasts p 
      JOIN users u ON p.user_id = u.id
      WHERE p.id = ?
    `).get(req.params.id);

    if (!podcast) {
      return res.status(404).json({ error: 'Podcast not found' });
    }

    const episodes = db.prepare(
      'SELECT * FROM episodes WHERE podcast_id = ? ORDER BY published_at DESC'
    ).all(req.params.id);

    res.json({ podcast, episodes });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch podcast', details: err.message });
  }
});

router.post('/', authenticateToken, (req, res) => {
  try {
    const { title, description, category, image_url } = req.body;

    if (!title) {
      return res.status(400).json({ error: 'Title is required' });
    }

    const result = db.prepare(`
      INSERT INTO podcasts (user_id, title, description, category, image_url)
      VALUES (?, ?, ?, ?, ?)
    `).run(req.user.id, title, description || null, category || null, image_url || null);

    const podcast = db.prepare('SELECT * FROM podcasts WHERE id = ?').get(result.lastInsertRowid);

    res.status(201).json({ message: 'Podcast created', podcast });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create podcast', details: err.message });
  }
});

router.put('/:id', authenticateToken, (req, res) => {
  try {
    const podcast = db.prepare('SELECT * FROM podcasts WHERE id = ?').get(req.params.id);

    if (!podcast) {
      return res.status(404).json({ error: 'Podcast not found' });
    }

    if (podcast.user_id !== req.user.id) {
      return res.status(403).json({ error: 'Unauthorized' });
    }

    const { title, description, category, image_url } = req.body;

    db.prepare(`
      UPDATE podcasts 
      SET title = COALESCE(?, title),
          description = COALESCE(?, description),
          category = COALESCE(?, category),
          image_url = COALESCE(?, image_url)
      WHERE id = ?
    `).run(title, description, category, image_url, req.params.id);

    const updated = db.prepare('SELECT * FROM podcasts WHERE id = ?').get(req.params.id);

    res.json({ message: 'Podcast updated', podcast: updated });
  } catch (err) {
    res.status(500).json({ error: 'Failed to update podcast', details: err.message });
  }
});

router.delete('/:id', authenticateToken, (req, res) => {
  try {
    const podcast = db.prepare('SELECT * FROM podcasts WHERE id = ?').get(req.params.id);

    if (!podcast) {
      return res.status(404).json({ error: 'Podcast not found' });
    }

    if (podcast.user_id !== req.user.id) {
      return res.status(403).json({ error: 'Unauthorized' });
    }

    db.prepare('DELETE FROM podcasts WHERE id = ?').run(req.params.id);

    res.json({ message: 'Podcast deleted' });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete podcast', details: err.message });
  }
});

export default router;