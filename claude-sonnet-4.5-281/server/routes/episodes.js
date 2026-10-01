import express from 'express';
import db from '../db';
import { authenticateToken } from '../middleware/auth';

const router = express.Router();

router.get('/', (req, res) => {
  try {
    const { podcast_id } = req.query;
    
    let episodes;
    if (podcast_id) {
      episodes = db.prepare(`
        SELECT e.*, p.title as podcast_title 
        FROM episodes e 
        JOIN podcasts p ON e.podcast_id = p.id
        WHERE e.podcast_id = ?
        ORDER BY e.published_at DESC
      `).all(podcast_id);
    } else {
      episodes = db.prepare(`
        SELECT e.*, p.title as podcast_title 
        FROM episodes e 
        JOIN podcasts p ON e.podcast_id = p.id
        ORDER BY e.published_at DESC
      `).all();
    }

    res.json({ episodes });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch episodes', details: err.message });
  }
});

router.get('/:id', (req, res) => {
  try {
    const episode = db.prepare(`
      SELECT e.*, p.title as podcast_title, p.user_id
      FROM episodes e 
      JOIN podcasts p ON e.podcast_id = p.id
      WHERE e.id = ?
    `).get(req.params.id);

    if (!episode) {
      return res.status(404).json({ error: 'Episode not found' });
    }

    res.json({ episode });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch episode', details: err.message });
  }
});

router.post('/', authenticateToken, (req, res) => {
  try {
    const { podcast_id, title, description, audio_url, duration } = req.body;

    if (!podcast_id || !title || !audio_url) {
      return res.status(400).json({ error: 'podcast_id, title, and audio_url are required' });
    }

    const podcast = db.prepare('SELECT * FROM podcasts WHERE id = ?').get(podcast_id);
    if (!podcast) {
      return res.status(404).json({ error: 'Podcast not found' });
    }

    if (podcast.user_id !== req.user.id) {
      return res.status(403).json({ error: 'Unauthorized' });
    }

    const result = db.prepare(`
      INSERT INTO episodes (podcast_id, title, description, audio_url, duration)
      VALUES (?, ?, ?, ?, ?)
    `).run(podcast_id, title, description || null, audio_url, duration || null);

    const episode = db.prepare('SELECT * FROM episodes WHERE id = ?').get(result.lastInsertRowid);

    res.status(201).json({ message: 'Episode created', episode });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create episode', details: err.message });
  }
});

router.put('/:id', authenticateToken, (req, res) => {
  try {
    const episode = db.prepare(`
      SELECT e.*, p.user_id 
      FROM episodes e 
      JOIN podcasts p ON e.podcast_id = p.id
      WHERE e.id = ?
    `).get(req.params.id);

    if (!episode) {
      return res.status(404).json({ error: 'Episode not found' });
    }

    if (episode.user_id !== req.user.id) {
      return res.status(403).json({ error: 'Unauthorized' });
    }

    const { title, description, audio_url, duration } = req.body;

    db.prepare(`
      UPDATE episodes 
      SET title = COALESCE(?, title),
          description = COALESCE(?, description),
          audio_url = COALESCE(?, audio_url),
          duration = COALESCE(?, duration)
      WHERE id = ?
    `).run(title, description, audio_url, duration, req.params.id);

    const updated = db.prepare('SELECT * FROM episodes WHERE id = ?').get(req.params.id);

    res.json({ message: 'Episode updated', episode: updated });
  } catch (err) {
    res.status(500).json({ error: 'Failed to update episode', details: err.message });
  }
});

router.delete('/:id', authenticateToken, (req, res) => {
  try {
    const episode = db.prepare(`
      SELECT e.*, p.user_id 
      FROM episodes e 
      JOIN podcasts p ON e.podcast_id = p.id
      WHERE e.id = ?
    `).get(req.params.id);

    if (!episode) {
      return res.status(404).json({ error: 'Episode not found' });
    }

    if (episode.user_id !== req.user.id) {
      return res.status(403).json({ error: 'Unauthorized' });
    }

    db.prepare('DELETE FROM episodes WHERE id = ?').run(req.params.id);

    res.json({ message: 'Episode deleted' });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete episode', details: err.message });
  }
});

export default router;