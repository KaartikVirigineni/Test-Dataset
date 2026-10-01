require 'sinatra'
require 'sinatra/json'
require 'sinatra/namespace'
require 'rack/cors'
require_relative 'lib/database'
require_relative 'lib/auth'
require_relative 'routes/auth_routes'
require_relative 'routes/restaurant_routes'
require_relative 'routes/order_routes'
require_relative 'routes/swagger_routes'

configure do
  set :bind, '0.0.0.0'
  set :port, 4567
  set :show_exceptions, false
  
  use Rack::Cors do
    allow do
      origins '*'
      resource '*', headers: :any, methods: [:get, :post, :put, :patch, :delete, :options]
    end
  end
  
  Database.setup
end

before do
  content_type :json unless request.path_info.start_with?('/swagger-ui') || request.path_info == '/swagger'
end

get '/' do
  json({
    message: 'FoodHub Aggregator API',
    version: '1.0.0',
    endpoints: {
      auth: '/api/auth',
      restaurants: '/api/restaurants',
      orders: '/api/orders',
      swagger: '/swagger',
      docs: '/swagger-ui'
    }
  })
end

get '/health' do
  json({ status: 'healthy', timestamp: Time.now.to_i })
end

error 401 do
  json({ error: 'Unauthorized' })
end

error 403 do
  json({ error: 'Forbidden' })
end

error 404 do
  json({ error: 'Not found' })
end

error 500 do
  json({ error: 'Internal server error' })
end