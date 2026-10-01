from pyramid.view import view_config
from pyramid.httpexceptions import HTTPNotFound, HTTPForbidden
from sqlalchemy.orm import sessionmaker
from models import Gist, User
from auth import require_auth, get_authenticated_user
from schemas import GistCreateSchema, GistUpdateSchema, GistResponseSchema
from marshmallow import ValidationError
from database import get_engine

@view_config(route_name='gists_list', request_method='GET', renderer='json')
def list_gists(request):
    engine = get_engine()
    Session = sessionmaker(bind=engine)
    session = Session()
    
    user = get_authenticated_user(request)
    
    if user:
        # Authenticated users see their own gists and public gists
        gists = session.query(Gist).filter(
            (Gist.owner_id == user['user_id']) | (Gist.is_public == 1)
        ).all()
    else:
        # Unauthenticated users see only public gists
        gists = session.query(Gist).filter(Gist.is_public == 1).all()
    
    schema = GistResponseSchema(many=True)
    result = []
    for gist in gists:
        gist_data = schema.dump([gist])[0]
        gist_data['owner_username'] = gist.owner.username
        result.append(gist_data)
    
    session.close()
    return {'gists': result}

@view_config(route_name='gists_create', request_method='POST', renderer='json')
@require_auth
def create_gist(request):
    schema = GistCreateSchema()
    try:
        data = schema.load(request.json_body)
    except ValidationError as e:
        request.response.status = 400
        return {'error': 'Validation error', 'details': e.messages}
    
    engine = get_engine()
    Session = sessionmaker(bind=engine)
    session = Session()
    
    gist = Gist(
        title=data['title'],
        description=data.get('description'),
        content=data['content'],
        language=data.get('language', 'text'),
        is_public=1 if data.get('is_public', True) else 0,
        owner_id=request.user['user_id']
    )
    
    session.add(gist)
    session.commit()
    
    response_schema = GistResponseSchema()
    result = response_schema.dump(gist)
    result['owner_username'] = request.user['username']
    
    session.close()
    
    request.response.status = 201
    return {'message': 'Gist created successfully', 'gist': result}

@view_config(route_name='gist_detail', request_method='GET', renderer='json')
def get_gist(request):
    gist_id = request.matchdict['id']
    
    engine = get_engine()
    Session = sessionmaker(bind=engine)
    session = Session()
    
    gist = session.query(Gist).filter(Gist.id == gist_id).first()
    
    if not gist:
        session.close()
        raise HTTPNotFound(json_body={'error': 'Gist not found'})
    
    user = get_authenticated_user(request)
    
    # Check permissions
    if not gist.is_public:
        if not user or user['user_id'] != gist.owner_id:
            session.close()
            raise HTTPForbidden(json_body={'error': 'Access denied'})
    
    schema = GistResponseSchema()
    result = schema.dump(gist)
    result['owner_username'] = gist.owner.username
    
    session.close()
    return {'gist': result}

@view_config(route_name='gist_update', request_method='PUT', renderer='json')
@require_auth
def update_gist(request):
    gist_id = request.matchdict['id']
    
    schema = GistUpdateSchema()
    try:
        data = schema.load(request.json_body)
    except ValidationError as e:
        request.response.status = 400
        return {'error': 'Validation error', 'details': e.messages}
    
    engine = get_engine()
    Session = sessionmaker(bind=engine)
    session = Session()
    
    gist = session.query(Gist).filter(Gist.id == gist_id).first()
    
    if not gist:
        session.close()
        raise HTTPNotFound(json_body={'error': 'Gist not found'})
    
    # Check ownership or admin role
    if gist.owner_id != request.user['user_id'] and request.user['role'] != 'admin':
        session.close()
        raise HTTPForbidden(json_body={'error': 'Access denied'})
    
    # Update fields
    if 'title' in data:
        gist.title = data['title']
    if 'description' in data:
        gist.description = data['description']
    if 'content' in data:
        gist.content = data['content']
    if 'language' in data:
        gist.language = data['language']
    if 'is_public' in data:
        gist.is_public = 1 if data['is_public'] else 0
    
    session.commit()
    
    response_schema = GistResponseSchema()
    result = response_schema.dump(gist)
    result['owner_username'] = gist.owner.username
    
    session.close()
    
    return {'message': 'Gist updated successfully', 'gist': result}

@view_config(route_name='gist_delete', request_method='DELETE', renderer='json')
@require_auth
def delete_gist(request):
    gist_id = request.matchdict['id']
    
    engine = get_engine()
    Session = sessionmaker(bind=engine)
    session = Session()
    
    gist = session.query(Gist).filter(Gist.id == gist_id).first()
    
    if not gist:
        session.close()
        raise HTTPNotFound(json_body={'error': 'Gist not found'})
    
    # Check ownership or admin role
    if gist.owner_id != request.user['user_id'] and request.user['role'] != 'admin':
        session.close()
        raise HTTPForbidden(json_body={'error': 'Access denied'})
    
    session.delete(gist)
    session.commit()
    session.close()
    
    return {'message': 'Gist deleted successfully'}