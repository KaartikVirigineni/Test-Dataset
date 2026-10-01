from pyramid.view import view_config
from pyramid.response import Response
from sqlalchemy.orm import sessionmaker
from models import User, Role
from auth import hash_password, verify_password, generate_token
from schemas import UserRegistrationSchema, UserLoginSchema, UserResponseSchema
from marshmallow import ValidationError
from database import get_engine

@view_config(route_name='register', request_method='POST', renderer='json')
def register(request):
    schema = UserRegistrationSchema()
    try:
        data = schema.load(request.json_body)
    except ValidationError as e:
        request.response.status = 400
        return {'error': 'Validation error', 'details': e.messages}
    
    engine = get_engine()
    Session = sessionmaker(bind=engine)
    session = Session()
    
    # Check if username or email already exists
    existing_user = session.query(User).filter(
        (User.username == data['username']) | (User.email == data['email'])
    ).first()
    
    if existing_user:
        session.close()
        request.response.status = 409
        return {'error': 'Username or email already exists'}
    
    # Create new user
    user = User(
        username=data['username'],
        email=data['email'],
        password_hash=hash_password(data['password']),
        role=Role.USER
    )
    
    session.add(user)
    session.commit()
    
    user_schema = UserResponseSchema()
    result = user_schema.dump(user)
    result['role'] = user.role.value
    
    session.close()
    
    request.response.status = 201
    return {'message': 'User registered successfully', 'user': result}

@view_config(route_name='login', request_method='POST', renderer='json')
def login(request):
    schema = UserLoginSchema()
    try:
        data = schema.load(request.json_body)
    except ValidationError as e:
        request.response.status = 400
        return {'error': 'Validation error', 'details': e.messages}
    
    engine = get_engine()
    Session = sessionmaker(bind=engine)
    session = Session()
    
    user = session.query(User).filter(User.username == data['username']).first()
    
    if not user or not verify_password(data['password'], user.password_hash):
        session.close()
        request.response.status = 401
        return {'error': 'Invalid username or password'}
    
    secret = request.registry.settings['jwt.secret']
    token = generate_token(user.id, user.username, user.role.value, secret)
    
    user_schema = UserResponseSchema()
    result = user_schema.dump(user)
    result['role'] = user.role.value
    
    session.close()
    
    return {
        'message': 'Login successful',
        'token': token,
        'user': result
    }