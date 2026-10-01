import express from 'express';
import db from '../db';
import { authenticateToken } from '../middleware/auth';

const router = express.Router();

router.use(authenticateToken);

router.get('/service/:serviceId', (req, res) => {
  try {
    const service = db.prepare('SELECT * FROM services WHERE id = ? AND user_id = ?').get(req.params.serviceId, req.user.id);
    if (!service) {
      return res.status(404).json({ error: 'Service not found' });
    }

    const limit = parseInt(req.query.limit) || 100;
    const checks = db.prepare(
      'SELECT * FROM checks WHERE service_id = ? ORDER BY checked_at DESC LIMIT ?'
    ).all(req.params.serviceId, limit);

    res.json({ checks });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch checks', details: err.message });
  }
});

router.post('/', (req, res) => {
  try {
    const { service_id, status, response_time, status_code } = req.body;

    if (!service_id || !status) {
      return res.status(400).json({ error: 'Service ID and status required' });
    }

    const service = db.prepare('SELECT * FROM services WHERE id = ? AND user_id = ?').get(service_id, req.user.id);
    if (!service) {
      return res.status(404).json({ error: 'Service not found' });
    }

    const result = db.prepare(
      'INSERT INTO checks (service_id, status, response_time, status_code) VALUES (?, ?, ?, ?)'
    ).run(service_id, status, response_time || null, status_code || null);

    db.prepare('UPDATE services SET status = ?, last_check = CURRENT_TIMESTAMP WHERE id = ?').run(status, service_id);

    const check = db.prepare('SELECT * FROM checks WHERE id = ?').get(result.lastInsertRowid);
    res.status(201).json({ check });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create check', details: err.message });
  }
});

router.get('/stats/:serviceId', (req, res) => {
  try {
    const service = db.prepare('SELECT * FROM services WHERE id = ? AND user_id = ?').get(req.params.serviceId, req.user.id);
    if (!service) {
      return res.status(404).json({ error: 'Service not found' });
    }

    const total = db.prepare('SELECT COUNT(*) as count FROM checks WHERE service_id = ?').get(req.params.serviceId);
    const upCount = db.prepare('SELECT COUNT(*) as count FROM checks WHERE service_id = ? AND status = "up"').get(req.params.serviceId);
    const downCount = db.prepare('SELECT COUNT(*) as count FROM checks WHERE service_id = ? AND status = "down"').get(req.params.serviceId);
    const avgResponse = db.prepare('SELECT AVG(response_time) as avg FROM checks WHERE service_id = ? AND response_time IS NOT NULL').get(req.params.serviceId);

    const uptime = total.count > 0 ? ((upCount.count / total.count) * 100).toFixed(2) : 0;

    res.json({
      stats: {
        total_checks: total.count,
        up_count: upCount.count,
        down_count: downCount.count,
        uptime_percentage: parseFloat(uptime),
        avg_response_time: avgResponse.avg ? Math.round(avgResponse.avg) : null
      }
    });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch stats', details: err.message });
  }
});

export default router;