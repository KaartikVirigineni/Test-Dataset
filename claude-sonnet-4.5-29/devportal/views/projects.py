from pyramid.view import view_config
from pyramid.httpexceptions import HTTPBadRequest, HTTPUnauthorized, HTTPNotFound, HTTPForbidden
from ..models.project import Project


@view_config(route_name='projects_list', request_method='GET', renderer='json')
def list_projects(request):
    query = request.dbsession.query(Project)
    
    if not request.current_user:
        query = query.filter(Project.is_public == True)
    else:
        query = query.filter(
            (Project.is_public == True) | 
            (Project.user_id == request.current_user.id)
        )
    
    projects = query.all()
    
    return {
        'projects': [
            {
                'id': p.id,
                'name': p.name,
                'description': p.description,
                'repository_url': p.repository_url,
                'documentation_url': p.documentation_url,
                'is_public': p.is_public,
                'created_at': p.created_at.isoformat(),
                'updated_at': p.updated_at.isoformat()
            }
            for p in projects
        ]
    }


@view_config(route_name='projects_create', request_method='POST', renderer='json')
def create_project(request):
    if not request.current_user:
        raise HTTPUnauthorized('Authentication required')
    
    try:
        data = request.json_body
    except:
        raise HTTPBadRequest('Invalid JSON')
    
    name = data.get('name')
    if not name:
        raise HTTPBadRequest('Name is required')
    
    project = Project(
        name=name,
        description=data.get('description'),
        repository_url=data.get('repository_url'),
        documentation_url=data.get('documentation_url'),
        is_public=data.get('is_public', True),
        user_id=request.current_user.id
    )
    
    request.dbsession.add(project)
    request.dbsession.flush()
    
    return {
        'id': project.id,
        'name': project.name,
        'description': project.description,
        'repository_url': project.repository_url,
        'documentation_url': project.documentation_url,
        'is_public': project.is_public,
        'created_at': project.created_at.isoformat(),
        'updated_at': project.updated_at.isoformat()
    }


@view_config(route_name='projects_detail', request_method='GET', renderer='json')
def get_project(request):
    project_id = request.matchdict['id']
    
    project = request.dbsession.query(Project).filter(
        Project.id == project_id
    ).first()
    
    if not project:
        raise HTTPNotFound('Project not found')
    
    if not project.is_public:
        if not request.current_user or request.current_user.id != project.user_id:
            raise HTTPForbidden('Access denied')
    
    return {
        'id': project.id,
        'name': project.name,
        'description': project.description,
        'repository_url': project.repository_url,
        'documentation_url': project.documentation_url,
        'is_public': project.is_public,
        'created_at': project.created_at.isoformat(),
        'updated_at': project.updated_at.isoformat()
    }


@view_config(route_name='projects_update', request_method='PUT', renderer='json')
def update_project(request):
    if not request.current_user:
        raise HTTPUnauthorized('Authentication required')
    
    project_id = request.matchdict['id']
    
    project = request.dbsession.query(Project).filter(
        Project.id == project_id
    ).first()
    
    if not project:
        raise HTTPNotFound('Project not found')
    
    if project.user_id != request.current_user.id:
        raise HTTPForbidden('Access denied')
    
    try:
        data = request.json_body
    except:
        raise HTTPBadRequest('Invalid JSON')
    
    if 'name' in data:
        project.name = data['name']
    if 'description' in data:
        project.description = data['description']
    if 'repository_url' in data:
        project.repository_url = data['repository_url']
    if 'documentation_url' in data:
        project.documentation_url = data['documentation_url']
    if 'is_public' in data:
        project.is_public = data['is_public']
    
    request.dbsession.flush()
    
    return {
        'id': project.id,
        'name': project.name,
        'description': project.description,
        'repository_url': project.repository_url,
        'documentation_url': project.documentation_url,
        'is_public': project.is_public,
        'created_at': project.created_at.isoformat(),
        'updated_at': project.updated_at.isoformat()
    }


@view_config(route_name='projects_delete', request_method='DELETE', renderer='json')
def delete_project(request):
    if not request.current_user:
        raise HTTPUnauthorized('Authentication required')
    
    project_id = request.matchdict['id']
    
    project = request.dbsession.query(Project).filter(
        Project.id == project_id
    ).first()
    
    if not project:
        raise HTTPNotFound('Project not found')
    
    if project.user_id != request.current_user.id:
        raise HTTPForbidden('Access denied')
    
    request.dbsession.delete(project)
    
    return {'message': 'Project deleted successfully'}