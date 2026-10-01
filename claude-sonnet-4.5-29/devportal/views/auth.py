from pyramid.view import view_config
from pyramid.response import Response
from pyramid.httpexceptions import HTTPBadRequest, HTTPUnauthorized, HTTPCreated
from ..models.user import User
from ..auth import create_jwt_token
import json


@view_config(route_name='auth_register', request_method='POST', renderer='json')
def register(request):
    try:
        data = request.json_body
    except:
        raise HTTPBadRequest('Invalid JSON')
    
    username = data.get('username')
    email = data.get('email')
    password = data.get('password')
    
    if not all([username, email, password]):
        raise HTTPBadRequest('Missing required fields')
    
    existing = request.dbsession.query(User).filter(
        (User.username == username) | (User.email == email)
    ).first()
    
    if existing:
        raise HTTPBadRequest('Username or email already exists')
    
    user = User(username=username, email=email)
    user.set_password(password)
    
    request.dbsession.add(user)
    request.dbsession.flush()
    
    token = create_jwt_token(user.id, request.registry.settings['jwt_secret'])
    
    return {
        'token': token,
        'user': {
            'id': user.id,
            'username': user.username,
            'email': user.email
        }
    }


@view_config(route_name='auth_login', request_method='POST', renderer='json')
def login(request):
    try:
        data = request.json_body
    except:
        raise HTTPBadRequest('Invalid JSON')
    
    username = data.get('username')
    password = data.get('password')
    
    if not all([username, password]):
        raise HTTPBadRequest('Missing username or password')
    
    user = request.dbsession.query(User).filter(
        User.username == username
    ).first()
    
    if not user or not user.check_password(password):
        raise HTTPUnauthorized('Invalid credentials')
    
    if not user.is_active:
        raise HTTPUnauthorized('Account is disabled')
    
    token = create_jwt_token(user.id, request.registry.settings['jwt_secret'])
    
    return {
        'token': token,
        'user': {
            'id': user.id,
            'username': user.username,
            'email': user.email
        }
    }


@view_config(route_name='auth_me', request_method='GET', renderer='json', permission='view')
def me(request):
    if not request.current_user:
        raise HTTPUnauthorized('Not authenticated')
    
    user = request.current_user
    return {
        'id': user.id,
        'username': user.username,
        'email': user.email,
        'is_active': user.is_active
    }