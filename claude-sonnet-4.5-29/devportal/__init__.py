from pyramid.config import Configurator
from pyramid.renderers import JSON
import os


def main(global_config, **settings):
    if 'sqlalchemy.url' not in settings:
        settings['sqlalchemy.url'] = os.environ.get(
            'DATABASE_URL', 
            'sqlite:///data/devportal.db'
        )
    
    settings['jwt_secret'] = os.environ.get('JWT_SECRET', 'dev-secret-change-in-production')
    
    config = Configurator(settings=settings)
    
    config.add_renderer('json', JSON(indent=4, sort_keys=True))
    
    config.include('pyramid_tm')
    config.include('.models')
    config.include('.routes')
    config.include('.auth')
    config.include('.graphql_config')
    
    config.scan()
    
    return config.make_wsgi_app()