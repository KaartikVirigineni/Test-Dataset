from pyramid.config import Configurator


def includeme(config: Configurator):
    config.add_route('swagger', '/swagger')
    config.add_route('swagger_ui', '/swagger-ui')
    
    config.add_route('auth_register', '/api/auth/register')
    config.add_route('auth_login', '/api/auth/login')
    config.add_route('auth_me', '/api/auth/me')
    
    config.add_route('projects_list', '/api/projects')
    config.add_route('projects_create', '/api/projects')
    config.add_route('projects_detail', '/api/projects/{id}')
    config.add_route('projects_update', '/api/projects/{id}')
    config.add_route('projects_delete', '/api/projects/{id}')
    
    config.add_route('apikeys_list', '/api/keys')
    config.add_route('apikeys_create', '/api/keys')
    config.add_route('apikeys_delete', '/api/keys/{id}')