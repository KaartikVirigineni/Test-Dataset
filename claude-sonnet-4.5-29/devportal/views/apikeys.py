from pyramid.view import view_config
from pyramid.httpexceptions import HTTPBadRequest, HTTPUnauthorized, HTTPNotFound, HTTPForbidden
from ..models.api_key import APIKey


@view_config(route_name='apikeys_list', request_method='GET', renderer='json')
def list_apikeys(request):
    if not request.current_user:
        raise HTTPUnauthorized('Authentication required')
    
    keys = request.dbsession.query(APIKey).filter(
        APIKey.user_id == request.current_user.id
    ).all()
    
    return {
        'api_keys': [
            {
                'id': k.id,
                'name': k.name,
                'key': k.key,
                'is_active': k.is_active,
                'created_at': k.created_at.isoformat()
            }
            for k in keys
        ]
    }


@view_config(route_name='apikeys_create', request_method='POST', renderer='json')
def create_apikey(request):
    if not request.current_user:
        raise HTTPUnauthorized('Authentication required')
    
    try:
        data = request.json_body
    except:
        raise HTTPBadRequest('Invalid JSON')
    
    name = data.get('name')
    if not name:
        raise HTTPBadRequest('Name is required')
    
    api_key = APIKey(
        name=name,
        key=APIKey.generate_key(),
        user_id=request.current_user.id
    )
    
    request.dbsession.add(api_key)
    request.dbsession.flush()
    
    return {
        'id': api_key.id,
        'name': api_key.name,
        'key': api_key.key,
        'is_active': api_key.is_active,
        'created_at': api_key.created_at.isoformat()
    }


@view_config(route_name='apikeys_delete', request_method='DELETE', renderer='json')
def delete_apikey(request):
    if not request.current_user:
        raise HTTPUnauthorized('Authentication required')
    
    key_id = request.matchdict['id']
    
    api_key = request.dbsession.query(APIKey).filter(
        APIKey.id == key_id
    ).first()
    
    if not api_key:
        raise HTTPNotFound('API key not found')
    
    if api_key.user_id != request.current_user.id:
        raise HTTPForbidden('Access denied')
    
    request.dbsession.delete(api_key)
    
    return {'message': 'API key deleted successfully'}