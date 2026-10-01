import jwt
import bcrypt
from datetime import datetime, timedelta
from functools import wraps
from pyramid.httpexceptions import HTTPUnauthorized, HTTPForbidden
from models import User, Role

def hash_password(password):
    return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

def verify_password(password, password_hash):
    return bcrypt.checkpw(password.encode('utf-8'), password_hash.encode('utf-8'))

def generate_token(user_id, username, role, secret):
    payload = {
        'user_id': user_id,
        'username': username,
        'role': role,
        'exp': datetime.utcnow() + timedelta(days=7)
    }
    return jwt.encode(payload, secret, algorithm='HS256')

def decode_token(token, secret):
    try:
        return jwt.decode(token, secret, algorithms=['HS256'])
    except jwt.ExpiredSignatureError:
        return None
    except jwt.InvalidTokenError:
        return None

def get_authenticated_user(request):
    auth_header = request.headers.get('Authorization')
    if not auth_header or not auth_header.startswith('Bearer '):
        return None
    
    token = auth_header.split(' ')[1]
    secret = request.registry.settings['jwt.secret']
    payload = decode_token(token, secret)
    
    if not payload:
        return None
    
    return payload

def require_auth(func):
    @wraps(func)
    def wrapper(request):
        user = get_authenticated_user(request)
        if not user:
            raise HTTPUnauthorized(json_body={'error': 'Authentication required'})
        request.user = user
        return func(request)
    return wrapper

def require_role(*roles):
    def decorator(func):
        @wraps(func)
        def wrapper(request):
            user = get_authenticated_user(request)
            if not user:
                raise HTTPUnauthorized(json_body={'error': 'Authentication required'})
            if user['role'] not in roles:
                raise HTTPForbidden(json_body={'error': 'Insufficient permissions'})
            request.user = user
            return func(request)
        return wrapper
    return decorator