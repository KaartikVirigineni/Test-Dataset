from pyramid.config import Configurator
from pyramid.security import Authenticated, Everyone
from pyramid.request import Request
import jwt
from datetime import datetime, timedelta
from .models.user import User


class AuthPolicy:
    def __init__(self, secret):
        self.secret = secret
    
    def authenticated_userid(self, request):
        token = self._get_token(request)
        if token:
            try:
                payload = jwt.decode(token, self.secret, algorithms=['HS256'])
                return payload.get('user_id')
            except jwt.InvalidTokenError:
                return None
        return None
    
    def effective_principals(self, request):
        principals = [Everyone]
        user_id = self.authenticated_userid(request)
        if user_id:
            principals.append(Authenticated)
            principals.append(f'user:{user_id}')
        return principals
    
    def _get_token(self, request):
        auth_header = request.headers.get('Authorization', '')
        if auth_header.startswith('Bearer '):
            return auth_header[7:]
        return None


def get_current_user(request: Request):
    user_id = request.authenticated_userid
    if user_id:
        return request.dbsession.query(User).filter(User.id == user_id).first()
    return None


def create_jwt_token(user_id, secret, expires_in=3600):
    payload = {
        'user_id': user_id,
        'exp': datetime.utcnow() + timedelta(seconds=expires_in),
        'iat': datetime.utcnow()
    }
    return jwt.encode(payload, secret, algorithm='HS256')


def includeme(config: Configurator):
    settings = config.get_settings()
    secret = settings['jwt_secret']
    
    config.set_security_policy(AuthPolicy(secret))
    config.add_request_method(get_current_user, 'current_user', reify=True)