from pyramid.config import Configurator
from pyramid.authentication import AuthTktAuthenticationPolicy
from pyramid.authorization import ACLAuthorizationPolicy
from pyramid.session import SignedCookieSessionFactory
import os


def main(global_config=None, **settings):
    if global_config is None:
        global_config = {}
    
    settings = settings or {}
    settings['jwt.secret'] = os.environ.get('JWT_SECRET', 'dev-secret-key-change-me')
    settings['sqlalchemy.url'] = os.environ.get('DATABASE_URL', 'sqlite:///data/nps.db')
    
    config = Configurator(settings=settings)
    
    config.include('pyramid_cors')
    config.add_cors_preflight_handler()
    
    config.include('.models')
    config.include('.auth')
    config.include('.routes')
    
    config.scan()
    
    return config.make_wsgi_app()