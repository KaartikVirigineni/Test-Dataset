import express from 'express';
import { getDatabase } from '../db';
import { authenticateToken } from '../middleware/auth';

export const monitoringRouter = express.Router();

monitoringRouter.use(authenticateToken);

monitoringRouter.get('/events', (req, res) => {
  try {
    const db = getDatabase();
    const { service_name, event_type, limit = 100 } = req.query;
    
    let query = 'SELECT * FROM monitoring_events WHERE user_id = ?';
    const params = [req.user.id];
    
    if (service_name) {
      query += ' AND service_name = ?';
      params.push(service_name);
    }
    
    if (event_type) {
      query += ' AND event_type = ?';
      params.push(event_type);
    }
    
    query += ' ORDER BY timestamp DESC LIMIT ?';
    params.push(parseInt(limit));
    
    const stmt = db.prepare(query);
    const events = stmt.all(...params);
    
    res.json({ events });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch monitoring events' });
  }
});

monitoringRouter.post('/events', (req, res) => {
  const { service_name, event_type, message, metadata } = req.body;
  
  if (!service_name || !event_type) {
    return res.status(400).json({ error: 'service_name and event_type required' });
  }
  
  try {
    const db = getDatabase();
    const stmt = db.prepare(
      'INSERT INTO monitoring_events (user_id, service_name, event_type, message, metadata) VALUES (?, ?, ?, ?, ?)'
    );
    const result = stmt.run(
      req.user.id,
      service_name,
      event_type,
      message || null,
      metadata ? JSON.stringify(metadata) : null
    );
    
    const newEvent = db.prepare('SELECT * FROM monitoring_events WHERE id = ?').get(result.lastInsertRowid);
    
    if (newEvent.metadata) {
      try {
        newEvent.metadata = JSON.parse(newEvent.metadata);
      } catch (e) {}
    }
    
    res.status(201).json({ event: newEvent });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create monitoring event' });
  }
});

monitoringRouter.get('/stats', (req, res) => {
  try {
    const db = getDatabase();
    
    const alertStats = db.prepare(`
      SELECT 
        severity,
        status,
        COUNT(*) as count
      FROM alerts
      WHERE user_id = ?
      GROUP BY severity, status
    `).all(req.user.id);
    
    const eventStats = db.prepare(`
      SELECT 
        service_name,
        event_type,
        COUNT(*) as count
      FROM monitoring_events
      WHERE user_id = ?
      GROUP BY service_name, event_type
    `).all(req.user.id);
    
    res.json({ 
      alert_statistics: alertStats,
      event_statistics: eventStats
    });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch statistics' });
  }
});