require 'jwt'
require 'bcrypt'

class Auth
  SECRET_KEY = ENV['JWT_SECRET'] || 'rentatrack_secret_key_change_in_production'
  ALGORITHM = 'HS256'

  def self.hash_password(password)
    BCrypt::Password.create(password)
  end

  def self.verify_password(password, hash)
    BCrypt::Password.new(hash) == password
  end

  def self.generate_token(user_id, email, role)
    payload = {
      user_id: user_id,
      email: email,
      role: role,
      exp: Time.now.to_i + (24 * 3600)
    }
    JWT.encode(payload, SECRET_KEY, ALGORITHM)
  end

  def self.decode_token(token)
    begin
      decoded = JWT.decode(token, SECRET_KEY, true, { algorithm: ALGORITHM })
      decoded[0]
    rescue JWT::DecodeError, JWT::ExpiredSignature
      nil
    end
  end

  def self.authenticate_request(request)
    header = request.env['HTTP_AUTHORIZATION']
    return nil unless header

    token = header.split(' ').last
    decode_token(token)
  end
end

def authenticate!
  payload = Auth.authenticate_request(request)
  halt 401, json({ error: 'Unauthorized', message: 'Invalid or missing token' }) unless payload
  @current_user = payload
end

def authorize_admin!
  authenticate!
  halt 403, json({ error: 'Forbidden', message: 'Admin access required' }) unless @current_user['role'] == 'admin'
end