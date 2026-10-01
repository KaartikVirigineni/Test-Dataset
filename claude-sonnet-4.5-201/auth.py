from pyramid.view import view_config
from pyramid.response import Response
from passlib.hash import bcrypt
import jwt
import datetime
from .models import User


def create_token(user_id, secret):
    payload = {
        'user_id': user_id,
        'exp': datetime.datetime.utcnow() + datetime.timedelta(days=7),
        'iat': datetime.datetime.utcnow()
    }
    return jwt.encode(payload, secret, algorithm='HS256')


def verify_token(token, secret):
    try:
        payload = jwt.decode(token, secret, algorithms=['HS256'])
        return payload['user_id']
    except jwt.ExpiredSignatureError:
        return None
    except jwt.InvalidTokenError:
        return None


def get_current_user(request):
    auth_header = request.headers.get('Authorization', '')
    if not auth_header.startswith('Bearer '):
        return None
    
    token = auth_header[7:]
    secret = request.registry.settings['jwt.secret']
    user_id = verify_token(token, secret)
    
    if user_id:
        return request.dbsession.query(User).filter_by(id=user_id).first()
    return None


@view_config(route_name='register', renderer='json', request_method='POST')
def register(request):
    data = request.json_body
    username = data.get('username')
    password = data.get('password')
    
    if not username or not password:
        request.response.status = 400
        return {'error': 'Username and password required'}
    
    existing = request.dbsession.query(User).filter_by(username=username).first()
    if existing:
        request.response.status = 409
        return {'error': 'Username already exists'}
    
    password_hash = bcrypt.hash(password)
    user = User(username=username, password_hash=password_hash)
    request.dbsession.add(user)
    request.dbsession.flush()
    
    token = create_token(user.id, request.registry.settings['jwt.secret'])
    
    return {
        'user_id': user.id,
        'username': user.username,
        'token': token
    }


@view_config(route_name='login', renderer='json', request_method='POST')
def login(request):
    data = request.json_body
    username = data.get('username')
    password = data.get('password')
    
    if not username or not password:
        request.response.status = 400
        return {'error': 'Username and password required'}
    
    user = request.dbsession.query(User).filter_by(username=username).first()
    
    if not user or not bcrypt.verify(password, user.password_hash):
        request.response.status = 401
        return {'error': 'Invalid credentials'}
    
    token = create_token(user.id, request.registry.settings['jwt.secret'])
    
    return {
        'user_id': user.id,
        'username': user.username,
        'token': token
    }


@view_config(route_name='me', renderer='json', request_method='GET')
def me(request):
    user = get_current_user(request)
    if not user:
        request.response.status = 401
        return {'error': 'Unauthorized'}
    
    return {
        'user_id': user.id,
        'username': user.username,
        'created_at': user.created_at.isoformat()
    }


def includeme(config):
    config.add_route('register', '/api/auth/register')
    config.add_route('login', '/api/auth/login')
    config.add_route('me', '/api/auth/me')