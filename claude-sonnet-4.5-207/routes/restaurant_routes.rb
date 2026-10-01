require 'sinatra/namespace'

namespace '/api/restaurants' do
  get '' do
    db = Database.connection
    
    cuisine = params['cuisine']
    min_rating = params['min_rating']
    
    query = 'SELECT * FROM restaurants WHERE is_active = 1'
    conditions = []
    values = []
    
    if cuisine
      conditions << 'cuisine = ?'
      values << cuisine
    end
    
    if min_rating
      conditions << 'rating >= ?'
      values << min_rating.to_f
    end
    
    query += ' AND ' + conditions.join(' AND ') unless conditions.empty?
    query += ' ORDER BY rating DESC'
    
    restaurants = db.execute(query, values)
    db.close
    
    json({ restaurants: restaurants })
  end
  
  get '/:id' do
    db = Database.connection
    restaurant = db.execute('SELECT * FROM restaurants WHERE id = ?', [params['id']]).first
    
    halt 404, json({ error: 'Restaurant not found' }) unless restaurant
    
    menu_items = db.execute('SELECT * FROM menu_items WHERE restaurant_id = ? AND is_available = 1', [params['id']])
    db.close
    
    json({
      restaurant: restaurant,
      menu_items: menu_items
    })
  end
  
  post '' do
    require_auth!
    
    data = JSON.parse(request.body.read)
    
    name = data['name']
    cuisine = data['cuisine']
    address = data['address']
    delivery_fee = data['delivery_fee'] || 0.0
    min_order = data['min_order'] || 0.0
    
    halt 400, json({ error: 'Name, cuisine, and address are required' }) if name.nil? || cuisine.nil? || address.nil?
    
    db = Database.connection
    db.execute(
      'INSERT INTO restaurants (name, cuisine, address, delivery_fee, min_order, owner_id) VALUES (?, ?, ?, ?, ?, ?)',
      [name, cuisine, address, delivery_fee.to_f, min_order.to_f, current_user['user_id']]
    )
    
    restaurant_id = db.last_insert_row_id
    restaurant = db.execute('SELECT * FROM restaurants WHERE id = ?', [restaurant_id]).first
    db.close
    
    status 201
    json({
      message: 'Restaurant created successfully',
      restaurant: restaurant
    })
  end
  
  put '/:id' do
    require_auth!
    
    db = Database.connection
    restaurant = db.execute('SELECT * FROM restaurants WHERE id = ?', [params['id']]).first
    
    halt 404, json({ error: 'Restaurant not found' }) unless restaurant
    halt 403, json({ error: 'Not authorized to update this restaurant' }) unless restaurant['owner_id'] == current_user['user_id']
    
    data = JSON.parse(request.body.read)
    
    updates = []
    values = []
    
    ['name', 'cuisine', 'address'].each do |field|
      if data[field]
        updates << "#{field} = ?"
        values << data[field]
      end
    end
    
    ['delivery_fee', 'min_order', 'rating'].each do |field|
      if data[field]
        updates << "#{field} = ?"
        values << data[field].to_f
      end
    end
    
    if data['is_active'] != nil
      updates << "is_active = ?"
      values << (data['is_active'] ? 1 : 0)
    end
    
    halt 400, json({ error: 'No fields to update' }) if updates.empty?
    
    values << params['id']
    db.execute("UPDATE restaurants SET #{updates.join(', ')} WHERE id = ?", values)
    
    updated = db.execute('SELECT * FROM restaurants WHERE id = ?', [params['id']]).first
    db.close
    
    json({
      message: 'Restaurant updated successfully',
      restaurant: updated
    })
  end
  
  delete '/:id' do
    require_auth!
    
    db = Database.connection
    restaurant = db.execute('SELECT * FROM restaurants WHERE id = ?', [params['id']]).first
    
    halt 404, json({ error: 'Restaurant not found' }) unless restaurant
    halt 403, json({ error: 'Not authorized to delete this restaurant' }) unless restaurant['owner_id'] == current_user['user_id']
    
    db.execute('DELETE FROM restaurants WHERE id = ?', [params['id']])
    db.close
    
    json({ message: 'Restaurant deleted successfully' })
  end
  
  post '/:id/menu' do
    require_auth!
    
    db = Database.connection
    restaurant = db.execute('SELECT * FROM restaurants WHERE id = ?', [params['id']]).first
    
    halt 404, json({ error: 'Restaurant not found' }) unless restaurant
    halt 403, json({ error: 'Not authorized to add menu items' }) unless restaurant['owner_id'] == current_user['user_id']
    
    data = JSON.parse(request.body.read)
    
    name = data['name']
    price = data['price']
    
    halt 400, json({ error: 'Name and price are required' }) if name.nil? || price.nil?
    
    description = data['description']
    category = data['category']
    
    db.execute(
      'INSERT INTO menu_items (restaurant_id, name, description, price, category) VALUES (?, ?, ?, ?, ?)',
      [params['id'], name, description, price.to_f, category]
    )
    
    item_id = db.last_insert_row_id
    item = db.execute('SELECT * FROM menu_items WHERE id = ?', [item_id]).first
    db.close
    
    status 201
    json({
      message: 'Menu item added successfully',
      item: item
    })
  end
end