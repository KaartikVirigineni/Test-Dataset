import jwt from 'jsonwebtoken';
import bcrypt from 'bcrypt';
import { Meteor } from 'meteor/meteor';
import { getQuery, runQuery } from './db';

const JWT_SECRET = Meteor.settings.jwtSecret || 'default-secret-change-me';

export function generateToken(user) {
  return jwt.sign(
    { id: user.id, username: user.username, role: user.role },
    JWT_SECRET,
    { expiresIn: '24h' }
  );
}

export function verifyToken(token) {
  try {
    return jwt.verify(token, JWT_SECRET);
  } catch (err) {
    return null;
  }
}

export async function authenticateUser(username, password) {
  const user = await getQuery(
    'SELECT * FROM users WHERE username = ?',
    [username]
  );
  
  if (!user) {
    return null;
  }
  
  const isValid = await bcrypt.compare(password, user.password);
  if (!isValid) {
    return null;
  }
  
  return user;
}

export async function registerUser(username, password, email) {
  const hashedPassword = await bcrypt.hash(password, 10);
  
  try {
    const result = await runQuery(
      'INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)',
      [username, hashedPassword, email, 'user']
    );
    
    return { id: result.id, username, email, role: 'user' };
  } catch (err) {
    throw new Error('User already exists');
  }
}

export function authMiddleware(req, res, next) {
  const authHeader = req.headers.authorization;
  
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({ error: 'No token provided' });
  }
  
  const token = authHeader.substring(7);
  const decoded = verifyToken(token);
  
  if (!decoded) {
    return res.status(401).json({ error: 'Invalid token' });
  }
  
  req.user = decoded;
  next();
}

export function requireRole(...roles) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required' });
    }
    
    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ error: 'Insufficient permissions' });
    }
    
    next();
  };
}