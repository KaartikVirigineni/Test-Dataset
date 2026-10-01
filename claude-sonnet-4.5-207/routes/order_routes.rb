require 'sinatra/namespace'

namespace '/api/orders' do
  get '' do
    require_auth!
    
    db = Database.connection
    
    if current_user['role'] == 'admin'
      orders = db.execute('SELECT * FROM orders ORDER BY created_at DESC')
    else
      orders = db.execute('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC', [current_user['user_id']])
    end
    
    orders.each do |order|
      items = db.execute(
        'SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?',
        [order['id']]
      )
      order['items'] = items
    end
    
    db.close
    
    json({ orders: orders })
  end
  
  get '/:id' do
    require_auth!
    
    db = Database.connection
    order = db.execute('SELECT * FROM orders WHERE id = ?', [params['id']]).first
    
    halt 404, json({ error: 'Order not found' }) unless order
    halt 403, json({ error: 'Not authorized to view this order' }) unless order['user_id'] == current_user['user_id'] || current_user['role'] == 'admin'
    
    items = db.execute(
      'SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?',
      [order['id']]
    )
    order['items'] = items
    
    db.close
    
    json({ order: order })
  end
  
  post '' do
    require_auth!
    
    data = JSON.parse(request.body.read)
    
    restaurant_id = data['restaurant_id']
    delivery_address = data['delivery_address']
    items = data['items']
    notes = data['notes']
    
    halt 400, json({ error: 'Restaurant ID, delivery address, and items are required' }) if restaurant_id.nil? || delivery_address.nil? || items.nil? || items.empty?
    
    db = Database.connection
    
    restaurant = db.execute('SELECT * FROM restaurants WHERE id = ? AND is_active = 1', [restaurant_id]).first
    halt 404, json({ error: 'Restaurant not found or inactive' }) unless restaurant
    
    total_amount = 0.0
    item_details = []
    
    items.each do |item|
      menu_item = db.execute('SELECT * FROM menu_items WHERE id = ? AND restaurant_id = ? AND is_available = 1', [item['menu_item_id'], restaurant_id]).first
      halt 400, json({ error: "Menu item #{item['menu_item_id']} not found or unavailable" }) unless menu_item
      
      quantity = item['quantity'].to_i
      halt 400, json({ error: 'Quantity must be greater than 0' }) if quantity <= 0
      
      item_total = menu_item['price'].to_f * quantity
      total_amount += item_total
      
      item_details << {
        menu_item_id: menu_item['id'],
        quantity: quantity,
        price: menu_item['price'].to_f
      }
    end
    
    total_amount += restaurant['delivery_fee'].to_f
    
    halt 400, json({ error: "Minimum order amount is #{restaurant['min_order']}" }) if total_amount < restaurant['min_order'].to_f
    
    db.execute(
      'INSERT INTO orders (user_id, restaurant_id, total_amount, delivery_address, notes, status) VALUES (?, ?, ?, ?, ?, ?)',
      [current_user['user_id'], restaurant_id, total_amount, delivery_address, notes, 'pending']
    )
    
    order_id = db.last_insert_row_id
    
    item_details.each do |item|
      db.execute(
        'INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)',
        [order_id, item[:menu_item_id], item[:quantity], item[:price]]
      )
    end
    
    order = db.execute('SELECT * FROM orders WHERE id = ?', [order_id]).first
    order_items = db.execute(
      'SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?',
      [order_id]
    )
    order['items'] = order_items
    
    db.close
    
    status 201
    json({
      message: 'Order created successfully',
      order: order
    })
  end
  
  patch '/:id/status' do
    require_auth!
    
    db = Database.connection
    order = db.execute('SELECT * FROM orders WHERE id = ?', [params['id']]).first
    
    halt 404, json({ error: 'Order not found' }) unless order
    
    restaurant = db.execute('SELECT * FROM restaurants WHERE id = ?', [order['restaurant_id']]).first
    halt 403, json({ error: 'Not authorized to update order status' }) unless restaurant['owner_id'] == current_user['user_id'] || current_user['role'] == 'admin'
    
    data = JSON.parse(request.body.read)
    status_value = data['status']
    
    valid_statuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled']
    halt 400, json({ error: "Invalid status. Must be one of: #{valid_statuses.join(', ')}" }) unless valid_statuses.include?(status_value)
    
    db.execute(
      'UPDATE orders SET status = ?, updated_at = strftime("%s", "now") WHERE id = ?',
      [status_value, params['id']]
    )
    
    updated_order = db.execute('SELECT * FROM orders WHERE id = ?', [params['id']]).first
    db.close
    
    json({
      message: 'Order status updated successfully',
      order: updated_order
    })
  end
end