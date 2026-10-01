require 'jwt'
require 'bcrypt'

module Auth
  SECRET_KEY = ENV['JWT_SECRET'] || 'foodhub_secret_key_change_in_production'
  ALGORITHM = 'HS256'
  
  def self.hash_password(password)
    BCrypt::Password.create(password)
  end
  
  def self.verify_password(password, hash)
    BCrypt::Password.new(hash) == password
  rescue BCrypt::Errors::InvalidHash
    false
  end
  
  def self.generate_token(user_id, email, role)
    payload = {
      user_id: user_id,
      email: email,
      role: role,
      exp: Time.now.to_i + (24 * 60 * 60)
    }
    JWT.encode(payload, SECRET_KEY, ALGORITHM)
  end
  
  def self.decode_token(token)
    JWT.decode(token, SECRET_KEY, true, { algorithm: ALGORITHM })[0]
  rescue JWT::DecodeError, JWT::ExpiredSignature
    nil
  end
  
  def self.authenticate_request(request)
    auth_header = request.env['HTTP_AUTHORIZATION']
    return nil unless auth_header
    
    token = auth_header.split(' ').last
    decode_token(token)
  end
end

module Sinatra
  module AuthHelper
    def current_user
      @current_user ||= Auth.authenticate_request(request)
    end
    
    def authenticated?
      !current_user.nil?
    end
    
    def require_auth!
      halt 401, json({ error: 'Authentication required' }) unless authenticated?
    end
    
    def require_role!(*roles)
      require_auth!
      halt 403, json({ error: 'Insufficient permissions' }) unless roles.include?(current_user['role'])
    end
  end
  
  helpers AuthHelper
end