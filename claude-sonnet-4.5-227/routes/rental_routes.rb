require 'sinatra/namespace'
require 'date'

namespace '/api/rentals' do
  get '' do
    authenticate!
    
    db = Database.connection
    
    if @current_user['role'] == 'admin'
      rentals = db.execute(<<-SQL)
        SELECT r.*, u.name as user_name, u.email as user_email, e.name as equipment_name, e.type as equipment_type
        FROM rentals r
        JOIN users u ON r.user_id = u.id
        JOIN equipment e ON r.equipment_id = e.id
        ORDER BY r.created_at DESC
      SQL
    else
      rentals = db.execute(<<-SQL, [@current_user['user_id']])
        SELECT r.*, e.name as equipment_name, e.type as equipment_type
        FROM rentals r
        JOIN equipment e ON r.equipment_id = e.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
      SQL
    end
    
    db.close
    
    json({ rentals: rentals })
  end

  get '/:id' do
    authenticate!
    
    db = Database.connection
    
    rental = db.execute(<<-SQL, [params['id']])
      SELECT r.*, u.name as user_name, u.email as user_email, e.name as equipment_name, e.type as equipment_type
      FROM rentals r
      JOIN users u ON r.user_id = u.id
      JOIN equipment e ON r.equipment_id = e.id
      WHERE r.id = ?
    SQL
    
    db.close
    
    halt 404, json({ error: 'Rental not found' }) if rental.empty?
    
    rental = rental.first
    
    halt 403, json({ error: 'Access denied' }) unless @current_user['role'] == 'admin' || rental['user_id'] == @current_user['user_id']
    
    json({ rental: rental })
  end

  post '' do
    authenticate!
    
    data = JSON.parse(request.body.read)
    
    halt 422, json({ error: 'equipment_id, start_date, and end_date are required' }) unless data['equipment_id'] && data['start_date'] && data['end_date']
    
    begin
      start_date = Date.parse(data['start_date'])
      end_date = Date.parse(data['end_date'])
    rescue
      halt 422, json({ error: 'Invalid date format' })
    end
    
    halt 422, json({ error: 'End date must be after start date' }) unless end_date > start_date
    
    db = Database.connection
    
    equipment = db.execute("SELECT * FROM equipment WHERE id = ?", [data['equipment_id']]).first
    halt 404, json({ error: 'Equipment not found' }) unless equipment
    halt 422, json({ error: 'Equipment not available' }) unless equipment['status'] == 'available'
    
    days = (end_date - start_date).to_i
    total_cost = days * equipment['daily_rate']
    
    db.execute(
      "INSERT INTO rentals (user_id, equipment_id, start_date, end_date, total_cost, status) VALUES (?, ?, ?, ?, ?, ?)",
      [@current_user['user_id'], data['equipment_id'], data['start_date'], data['end_date'], total_cost, 'pending']
    )
    
    rental_id = db.last_insert_row_id
    
    db.execute("UPDATE equipment SET status = 'rented' WHERE id = ?", [data['equipment_id']])
    
    rental = db.execute(<<-SQL, [rental_id])
      SELECT r.*, e.name as equipment_name, e.type as equipment_type
      FROM rentals r
      JOIN equipment e ON r.equipment_id = e.id
      WHERE r.id = ?
    SQL
    
    db.close
    
    status 201
    json({ message: 'Rental created successfully', rental: rental.first })
  end

  put '/:id/status' do
    authenticate!
    
    data = JSON.parse(request.body.read)
    
    halt 422, json({ error: 'Status is required' }) unless data['status']
    halt 422, json({ error: 'Invalid status' }) unless ['pending', 'active', 'completed', 'cancelled'].include?(data['status'])
    
    db = Database.connection
    
    rental = db.execute("SELECT * FROM rentals WHERE id = ?", [params['id']]).first
    halt 404, json({ error: 'Rental not found' }) unless rental
    
    halt 403, json({ error: 'Access denied' }) unless @current_user['role'] == 'admin' || rental['user_id'] == @current_user['user_id']
    
    db.execute("UPDATE rentals SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [data['status'], params['id']])
    
    if data['status'] == 'completed' || data['status'] == 'cancelled'
      db.execute("UPDATE equipment SET status = 'available' WHERE id = ?", [rental['equipment_id']])
    end
    
    rental = db.execute(<<-SQL, [params['id']])
      SELECT r.*, e.name as equipment_name, e.type as equipment_type
      FROM rentals r
      JOIN equipment e ON r.equipment_id = e.id
      WHERE r.id = ?
    SQL
    
    db.close
    
    json({ message: 'Rental status updated successfully', rental: rental.first })
  end

  delete '/:id' do
    authenticate!
    
    db = Database.connection
    
    rental = db.execute("SELECT * FROM rentals WHERE id = ?", [params['id']]).first
    halt 404, json({ error: 'Rental not found' }) unless rental
    
    halt 403, json({ error: 'Access denied' }) unless @current_user['role'] == 'admin'
    
    db.execute("UPDATE equipment SET status = 'available' WHERE id = ?", [rental['equipment_id']])
    db.execute("DELETE FROM rentals WHERE id = ?", [params['id']])
    db.close
    
    json({ message: 'Rental deleted successfully' })
  end
end