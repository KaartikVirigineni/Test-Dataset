require 'sinatra/namespace'

namespace '/api/auth' do
  post '/register' do
    data = JSON.parse(request.body.read)
    
    email = data['email']
    password = data['password']
    name = data['name']
    
    halt 400, json({ error: 'Email, password, and name are required' }) if email.nil? || password.nil? || name.nil?
    halt 400, json({ error: 'Password must be at least 6 characters' }) if password.length < 6
    
    db = Database.connection
    
    existing = db.execute('SELECT id FROM users WHERE email = ?', [email]).first
    halt 400, json({ error: 'Email already registered' }) if existing
    
    password_hash = Auth.hash_password(password)
    
    db.execute(
      'INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)',
      [email, password_hash, name, 'customer']
    )
    
    user_id = db.last_insert_row_id
    token = Auth.generate_token(user_id, email, 'customer')
    
    db.close
    
    status 201
    json({
      message: 'User registered successfully',
      token: token,
      user: {
        id: user_id,
        email: email,
        name: name,
        role: 'customer'
      }
    })
  end
  
  post '/login' do
    data = JSON.parse(request.body.read)
    
    email = data['email']
    password = data['password']
    
    halt 400, json({ error: 'Email and password are required' }) if email.nil? || password.nil?
    
    db = Database.connection
    user = db.execute('SELECT * FROM users WHERE email = ?', [email]).first
    db.close
    
    halt 401, json({ error: 'Invalid credentials' }) unless user
    halt 401, json({ error: 'Invalid credentials' }) unless Auth.verify_password(password, user['password_hash'])
    
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
    require_auth!
    
    db = Database.connection
    user = db.execute('SELECT id, email, name, role, created_at FROM users WHERE id = ?', [current_user['user_id']]).first
    db.close
    
    halt 404, json({ error: 'User not found' }) unless user
    
    json({ user: user })
  end
end