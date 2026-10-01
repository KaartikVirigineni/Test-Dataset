import { Mongo } from 'meteor/mongo';

export const Users = new Mongo.Collection('users');
export const Products = new Mongo.Collection('products');
export const Orders = new Mongo.Collection('orders');

// Create indexes
if (Meteor.isServer) {
  Meteor.startup(() => {
    Users.createIndex({ email: 1 }, { unique: true });
    Users.createIndex({ username: 1 }, { unique: true });
    Products.createIndex({ sku: 1 }, { unique: true });
    Orders.createIndex({ userId: 1 });
    Orders.createIndex({ status: 1 });
  });
}