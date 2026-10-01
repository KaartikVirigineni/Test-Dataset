from pyramid.config import Configurator
from pyramid.renderers import JSON
import os

def main(global_config, **settings):
    settings['sqlalchemy.url'] = os.getenv('DATABASE_URL', 'sqlite:///gists.db')
    settings['jwt.secret'] = os.getenv('JWT_SECRET', 'change-this-secret-in-production')
    
    config = Configurator(settings=settings)
    
    config.add_renderer('json', JSON(indent=2))
    
    # Auth routes
    config.add_route('register', '/api/auth/register')
    config.add_route('login', '/api/auth/login')
    
    # Gist routes
    config.add_route('gists_list', '/api/gists')
    config.add_route('gists_create', '/api/gists')
    config.add_route('gist_detail', '/api/gists/{id}')
    config.add_route('gist_update', '/api/gists/{id}')
    config.add_route('gist_delete', '/api/gists/{id}')
    
    # Swagger routes
    config.add_route('swagger', '/swagger')
    config.add_route('swagger_ui', '/docs')
    
    config.scan('views')
    
    return config.make_wsgi_app()