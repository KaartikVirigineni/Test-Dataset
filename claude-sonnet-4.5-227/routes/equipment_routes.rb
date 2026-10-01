require 'sinatra/namespace'

namespace '/api/equipment' do
  get '' do
    db = Database.connection
    
    type_filter = params['type']
    status_filter = params['status']
    
    query = "SELECT * FROM equipment WHERE 1=1"
    filters = []
    
    if type_filter && !type_filter.empty?
      query += " AND type = ?"
      filters << type_filter
    end
    
    if status_filter && !status_filter.empty?
      query += " AND status = ?"
      filters << status_filter
    end
    
    query += " ORDER BY created_at DESC"
    
    equipment = db.execute(query, filters)
    db.close
    
    json({ equipment: equipment })
  end

  get '/:id' do
    db = Database.connection
    equipment = db.execute("SELECT * FROM equipment WHERE id = ?", [params['id']]).first
    db.close
    
    halt 404, json({ error: 'Equipment not found' }) unless equipment
    
    json({ equipment: equipment })
  end

  post '' do
    authenticate!
    authorize_admin!
    
    data = JSON.parse(request.body.read)
    
    halt 422, json({ error: 'Name, type, and daily_rate are required' }) unless data['name'] && data['type'] && data['daily_rate']
    
    db = Database.connection
    
    db.execute(
      "INSERT INTO equipment (name, type, description, daily_rate, status) VALUES (?, ?, ?, ?, ?)",
      [data['name'], data['type'], data['description'], data['daily_rate'], data['status'] || 'available']
    )
    
    equipment_id = db.last_insert_row_id
    equipment = db.execute("SELECT * FROM equipment WHERE id = ?", [equipment_id]).first
    db.close
    
    status 201
    json({ message: 'Equipment created successfully', equipment: equipment })
  end

  put '/:id' do
    authenticate!
    authorize_admin!
    
    data = JSON.parse(request.body.read)
    
    db = Database.connection
    
    existing = db.execute("SELECT * FROM equipment WHERE id = ?", [params['id']]).first
    halt 404, json({ error: 'Equipment not found' }) unless existing
    
    name = data['name'] || existing['name']
    type = data['type'] || existing['type']
    description = data['description'] || existing['description']
    daily_rate = data['daily_rate'] || existing['daily_rate']
    status = data['status'] || existing['status']
    
    db.execute(
      "UPDATE equipment SET name = ?, type = ?, description = ?, daily_rate = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
      [name, type, description, daily_rate, status, params['id']]
    )
    
    equipment = db.execute("SELECT * FROM equipment WHERE id = ?", [params['id']]).first
    db.close
    
    json({ message: 'Equipment updated successfully', equipment: equipment })
  end

  delete '/:id' do
    authenticate!
    authorize_admin!
    
    db = Database.connection
    
    existing = db.execute("SELECT * FROM equipment WHERE id = ?", [params['id']]).first
    halt 404, json({ error: 'Equipment not found' }) unless existing
    
    rentals = db.execute("SELECT COUNT(*) as count FROM rentals WHERE equipment_id = ? AND status IN ('pending', 'active')", [params['id']])[0]['count']
    halt 422, json({ error: 'Cannot delete equipment with active rentals' }) if rentals > 0
    
    db.execute("DELETE FROM equipment WHERE id = ?", [params['id']])
    db.close
    
    json({ message: 'Equipment deleted successfully' })
  end
end