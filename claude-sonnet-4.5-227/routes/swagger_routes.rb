get '/swagger' do
  content_type 'application/x-yaml'
  send_file File.join(settings.root, 'openapi.yaml')
end

get '/swagger-ui' do
  content_type 'text/html'
  erb :swagger_ui
end