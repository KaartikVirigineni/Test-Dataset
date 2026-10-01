import express from 'express';
import { getDatabase } from '../db';

const router = express.Router();

router.get('/', (req, res) => {
  const db = getDatabase();
  const stmt = db.prepare('SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC');
  const items = stmt.all(req.user.userId);
  res.json({ items });
});

router.get('/:id', (req, res) => {
  const db = getDatabase();
  const stmt = db.prepare('SELECT * FROM items WHERE id = ? AND user_id = ?');
  const item = stmt.get(req.params.id, req.user.userId);

  if (!item) {
    return res.status(404).json({ error: 'Item not found' });
  }

  res.json(item);
});

router.post('/', (req, res) => {
  const { name, description, quantity, price, sku } = req.body;

  if (!name) {
    return res.status(400).json({ error: 'Name is required' });
  }

  const db = getDatabase();

  try {
    const stmt = db.prepare(`
      INSERT INTO items (name, description, quantity, price, sku, user_id)
      VALUES (?, ?, ?, ?, ?, ?)
    `);
    
    const result = stmt.run(
      name,
      description || null,
      quantity || 0,
      price || 0.0,
      sku || null,
      req.user.userId
    );

    const getStmt = db.prepare('SELECT * FROM items WHERE id = ?');
    const item = getStmt.get(result.lastInsertRowid);

    res.status(201).json(item);
  } catch (error) {
    if (error.message.includes('UNIQUE constraint failed')) {
      return res.status(409).json({ error: 'SKU already exists' });
    }
    res.status(500).json({ error: 'Failed to create item' });
  }
});

router.put('/:id', (req, res) => {
  const { name, description, quantity, price, sku } = req.body;
  const db = getDatabase();

  const checkStmt = db.prepare('SELECT * FROM items WHERE id = ? AND user_id = ?');
  const existingItem = checkStmt.get(req.params.id, req.user.userId);

  if (!existingItem) {
    return res.status(404).json({ error: 'Item not found' });
  }

  try {
    const stmt = db.prepare(`
      UPDATE items
      SET name = ?, description = ?, quantity = ?, price = ?, sku = ?, updated_at = CURRENT_TIMESTAMP
      WHERE id = ? AND user_id = ?
    `);

    stmt.run(
      name !== undefined ? name : existingItem.name,
      description !== undefined ? description : existingItem.description,
      quantity !== undefined ? quantity : existingItem.quantity,
      price !== undefined ? price : existingItem.price,
      sku !== undefined ? sku : existingItem.sku,
      req.params.id,
      req.user.userId
    );

    const getStmt = db.prepare('SELECT * FROM items WHERE id = ?');
    const item = getStmt.get(req.params.id);

    res.json(item);
  } catch (error) {
    if (error.message.includes('UNIQUE constraint failed')) {
      return res.status(409).json({ error: 'SKU already exists' });
    }
    res.status(500).json({ error: 'Failed to update item' });
  }
});

router.delete('/:id', (req, res) => {
  const db = getDatabase();
  const stmt = db.prepare('DELETE FROM items WHERE id = ? AND user_id = ?');
  const result = stmt.run(req.params.id, req.user.userId);

  if (result.changes === 0) {
    return res.status(404).json({ error: 'Item not found' });
  }

  res.json({ message: 'Item deleted successfully' });
});

export { router as inventoryRoutes };