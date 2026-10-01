import { Meteor } from 'meteor/meteor';
import { WebApp } from 'meteor/webapp';
import express from 'express';
import { Orders, Products } from '../collections';
import { authenticate, requireRole } from '../auth';

const router = express.Router();

router.get('/api/orders', authenticate, (req, res) => {
  try {
    let query = {};
    
    if (req.user.role === 'customer') {
      query.userId = req.user.userId;
    }

    const { status } = req.query;
    if (status) {
      query.status = status;
    }

    const orders = Orders.find(query).fetch();
    res.json(orders);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.get('/api/orders/:id', authenticate, (req, res) => {
  try {
    const order = Orders.findOne(req.params.id);
    
    if (!order) {
      return res.status(404).json({ error: 'Order not found' });
    }

    if (req.user.role === 'customer' && order.userId !== req.user.userId) {
      return res.status(403).json({ error: 'Access denied' });
    }

    res.json(order);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.post('/api/orders', authenticate, (req, res) => {
  try {
    const { items, shippingAddress } = req.body;

    if (!items || !Array.isArray(items) || items.length === 0) {
      return res.status(400).json({ error: 'Invalid items' });
    }

    if (!shippingAddress) {
      return res.status(400).json({ error: 'Shipping address required' });
    }

    let total = 0;
    const orderItems = [];

    for (const item of items) {
      const product = Products.findOne(item.productId);
      
      if (!product) {
        return res.status(404).json({ error: `Product ${item.productId} not found` });
      }

      if (product.stock < item.quantity) {
        return res.status(400).json({ error: `Insufficient stock for ${product.name}` });
      }

      const itemTotal = product.price * item.quantity;
      total += itemTotal;

      orderItems.push({
        productId: product._id,
        productName: product.name,
        price: product.price,
        quantity: item.quantity,
        subtotal: itemTotal
      });

      Products.update(product._id, {
        $inc: { stock: -item.quantity }
      });
    }

    const orderId = Orders.insert({
      userId: req.user.userId,
      items: orderItems,
      total,
      shippingAddress,
      status: 'pending',
      createdAt: new Date(),
      updatedAt: new Date()
    });

    const order = Orders.findOne(orderId);
    res.status(201).json(order);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.patch('/api/orders/:id/status', authenticate, requireRole('admin', 'staff'), (req, res) => {
  try {
    const { status } = req.body;
    
    if (!status) {
      return res.status(400).json({ error: 'Status required' });
    }

    const validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!validStatuses.includes(status)) {
      return res.status(400).json({ error: 'Invalid status' });
    }

    const order = Orders.findOne(req.params.id);
    if (!order) {
      return res.status(404).json({ error: 'Order not found' });
    }

    Orders.update(req.params.id, {
      $set: {
        status,
        updatedAt: new Date()
      }
    });

    const updatedOrder = Orders.findOne(req.params.id);
    res.json(updatedOrder);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.delete('/api/orders/:id', authenticate, (req, res) => {
  try {
    const order = Orders.findOne(req.params.id);
    
    if (!order) {
      return res.status(404).json({ error: 'Order not found' });
    }

    if (req.user.role === 'customer' && order.userId !== req.user.userId) {
      return res.status(403).json({ error: 'Access denied' });
    }

    if (order.status !== 'pending') {
      return res.status(400).json({ error: 'Cannot delete order that is not pending' });
    }

    for (const item of order.items) {
      Products.update(item.productId, {
        $inc: { stock: item.quantity }
      });
    }

    Orders.remove(req.params.id);
    res.status(204).send();
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

WebApp.connectHandlers.use(router);