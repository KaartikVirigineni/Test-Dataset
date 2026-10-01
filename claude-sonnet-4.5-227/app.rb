require 'sinatra'
require 'sinatra/json'
require 'sinatra/namespace'
require 'rack/cors'
require 'json'
require_relative 'lib/database'
require_relative 'lib/auth'
require_relative 'routes/auth_routes'
require_relative 'routes/rental_routes'
require_relative 'routes/equipment_routes'
require_relative 'routes/swagger_routes'

use Rack::Cors do
  allow do
    origins '*'
    resource '*', headers: :any, methods: [:get, :post, :put, :patch, :delete, :options]
  end
end

configure do
  set :show_exceptions, false
  set :raise_errors, false
  Database.setup
end

before do
  content_type :json unless request.path.start_with?('/swagger-ui') || request.path == '/swagger'
end

get '/' do
  json({
    name: 'RentaTrack API',
    version: '1.0.0',
    endpoints: {
      swagger: '/swagger',
      swagger_ui: '/swagger-ui',
      auth: '/api/auth/*',
      rentals: '/api/rentals/*',
      equipment: '/api/equipment/*'
    }
  })
end

get '/health' do
  json({ status: 'ok', timestamp: Time.now.utc.iso8601 })
end

error 401 do
  json({ error: 'Unauthorized', message: env['sinatra.error']&.message || 'Authentication required' })
end

error 403 do
  json({ error: 'Forbidden', message: env['sinatra.error']&.message || 'Access denied' })
end

error 404 do
  json({ error: 'Not Found', message: 'Resource not found' })
end

error 422 do
  json({ error: 'Unprocessable Entity', message: env['sinatra.error']&.message || 'Invalid input' })
end

error do
  status 500
  json({ error: 'Internal Server Error', message: env['sinatra.error']&.message || 'An error occurred' })
end