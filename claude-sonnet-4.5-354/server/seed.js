import { Meteor } from 'meteor/meteor';
import bcrypt from 'bcrypt';
import { Users, Products } from './collections';

Meteor.startup(async () => {
  if (Users.find().count() === 0) {
    console.log('Seeding admin user...');
    
    const hashedPassword = await bcrypt.hash('admin123', 10);
    
    Users.insert({
      username: 'admin',
      email: 'admin@meteormart.com',
      password: hashedPassword,
      role: 'admin',
      createdAt: new Date()
    });

    Users.insert({
      username: 'customer',
      email: 'customer@example.com',
      password: await bcrypt.hash('customer123', 10),
      role: 'customer',
      createdAt: new Date()
    });
  }

  if (Products.find().count() === 0) {
    console.log('Seeding products...');
    
    const products = [
      {
        name: 'Laptop Pro 15',
        description: 'High-performance laptop with 15-inch display',
        price: 1299.99,
        category: 'electronics',
        sku: 'LAPTOP-001',
        stock: 25,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        name: 'Wireless Mouse',
        description: 'Ergonomic wireless mouse with precision tracking',
        price: 29.99,
        category: 'electronics',
        sku: 'MOUSE-001',
        stock: 100,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        name: 'Office Chair',
        description: 'Comfortable office chair with lumbar support',
        price: 249.99,
        category: 'furniture',
        sku: 'CHAIR-001',
        stock: 50,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        name: 'Desk Lamp',
        description: 'LED desk lamp with adjustable brightness',
        price: 39.99,
        category: 'furniture',
        sku: 'LAMP-001',
        stock: 75,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        name: 'USB-C Cable',
        description: 'Fast charging USB-C cable 6ft',
        price: 12.99,
        category: 'accessories',
        sku: 'CABLE-001',
        stock: 200,
        createdAt: new Date(),
        updatedAt: new Date()
      }
    ];

    products.forEach(product => Products.insert(product));
  }
});