require 'sinatra/namespace'

namespace '/api/auth' do
  post '/register' do
    data = JSON.parse(request.body.read)
    
    halt 422, json({ error: 'Email, password, and name are required' }) unless data['email'] && data['password'] && data['name']
    
    db = Database.connection
    
    existing = db.execute("SELECT id FROM users WHERE email = ?", [data['email']])
    halt 422, json({ error: 'Email already registered' }) unless existing.empty?
    
    password_hash = Auth.hash_password(data['password'])
    
    db.execute(
      "INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)",
      [data['email'], password_hash, data['name'], 'user']
    )
    
    user_id = db.last_insert_row_id
    token = Auth.generate_token(user_id, data['email'], 'user')
    
    db.close
    
    status 201
    json({
      message: 'User registered successfully',
      token: token,
      user: {
        id: user_id,
        email: data['email'],
        name: data['name'],
        role: 'user'
      }
    })
  end

  post '/login' do
    data = JSON.parse(request.body.read)
    
    halt 422, json({ error: 'Email and password are required' }) unless data['email'] && data['password']
    
    db = Database.connection
    
    user = db.execute("SELECT * FROM users WHERE email = ?", [data['email']]).first
    db.close
    
    halt 401, json({ error: 'Invalid credentials' }) unless user
    halt 401, json({ error: 'Invalid credentials' }) unless Auth.verify_password(data['password'], user['password_hash'])
    
    token = Auth.generate_token(user['id'], user['email'], user['role'])
    
    json({
      message: 'Login successful',
      token: token,
      user: {
        id: user['id'],
        email: user['email'],
        name: user['name'],
        role: user['role']
      }
    })
  end

  get '/me' do
    authenticate!
    
    db = Database.connection
    user = db.execute("SELECT id, email, name, role, created_at FROM users WHERE id = ?", [@current_user['user_id']]).first
    db.close
    
    halt 404, json({ error: 'User not found' }) unless user
    
    json({ user: user })
  end
end