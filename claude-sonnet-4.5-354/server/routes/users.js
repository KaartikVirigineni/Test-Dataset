import { Meteor } from 'meteor/meteor';
import { WebApp } from 'meteor/webapp';
import express from 'express';
import { Users } from '../collections';
import { authenticate, requireRole } from '../auth';

const router = express.Router();

router.get('/api/users/me', authenticate, (req, res) => {
  try {
    const user = Users.findOne(req.user.userId);
    
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    res.json({
      id: user._id,
      username: user.username,
      email: user.email,
      role: user.role,
      createdAt: user.createdAt
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.get('/api/users', authenticate, requireRole('admin'), (req, res) => {
  try {
    const users = Users.find({}, {
      fields: { password: 0 }
    }).fetch();

    res.json(users);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.get('/api/users/:id', authenticate, requireRole('admin'), (req, res) => {
  try {
    const user = Users.findOne(req.params.id, {
      fields: { password: 0 }
    });
    
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    res.json(user);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

router.patch('/api/users/:id/role', authenticate, requireRole('admin'), (req, res) => {
  try {
    const { role } = req.body;
    
    if (!role) {
      return res.status(400).json({ error: 'Role required' });
    }

    const validRoles = ['customer', 'staff', 'admin'];
    if (!validRoles.includes(role)) {
      return res.status(400).json({ error: 'Invalid role' });
    }

    const user = Users.findOne(req.params.id);
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    Users.update(req.params.id, {
      $set: { role }
    });

    const updatedUser = Users.findOne(req.params.id, {
      fields: { password: 0 }
    });

    res.json(updatedUser);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

WebApp.connectHandlers.use(router);