import { Meteor } from 'meteor/meteor';
import { WebApp } from 'meteor/webapp';
import express from 'express';
import { Products } from '../collections';
import { authenticate, requireRole } from '../auth';

const router = express.Router();

router.get('/api/products', (req, res) => {
  try {
    const { category, minPrice, maxPrice } = req.query;
    let query = {};

    if (category) {
      query.category = category;
    }

    if (minPrice || maxPrice) {
      query.price = {};
      if (minPrice) query.price.$gte = parseFloat(minPrice);
      if (maxPrice) query.price.$lte = parseFloat(maxPrice);
    }

    const products = Products.find(query).fetch();
    res.json(products);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.get('/api/products/:id', (req, res) => {
  try {
    const product = Products.findOne(req.params.id);
    
    if (!product) {
      return res.status(404).json({ error: 'Product not found' });
    }

    res.json(product);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.post('/api/products', authenticate, requireRole('admin'), (req, res) => {
  try {
    const { name, description, price, category, sku, stock } = req.body;

    if (!name || !price || !sku) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    const existingProduct = Products.findOne({ sku });
    if (existingProduct) {
      return res.status(409).json({ error: 'Product with this SKU already exists' });
    }

    const productId = Products.insert({
      name,
      description: description || '',
      price: parseFloat(price),
      category: category || 'general',
      sku,
      stock: stock || 0,
      createdAt: new Date(),
      updatedAt: new Date()
    });

    const product = Products.findOne(productId);
    res.status(201).json(product);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.put('/api/products/:id', authenticate, requireRole('admin'), (req, res) => {
  try {
    const { name, description, price, category, stock } = req.body;
    
    const product = Products.findOne(req.params.id);
    if (!product) {
      return res.status(404).json({ error: 'Product not found' });
    }

    const updateFields = {
      updatedAt: new Date()
    };

    if (name) updateFields.name = name;
    if (description !== undefined) updateFields.description = description;
    if (price) updateFields.price = parseFloat(price);
    if (category) updateFields.category = category;
    if (stock !== undefined) updateFields.stock = stock;

    Products.update(req.params.id, { $set: updateFields });
    
    const updatedProduct = Products.findOne(req.params.id);
    res.json(updatedProduct);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.delete('/api/products/:id', authenticate, requireRole('admin'), (req, res) => {
  try {
    const product = Products.findOne(req.params.id);
    
    if (!product) {
      return res.status(404).json({ error: 'Product not found' });
    }

    Products.remove(req.params.id);
    res.status(204).send();
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

WebApp.connectHandlers.use(router);