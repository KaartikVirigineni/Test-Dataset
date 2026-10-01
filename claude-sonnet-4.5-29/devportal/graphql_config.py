from pyramid.config import Configurator
from pyramid.view import view_config
from pyramid.httpexceptions import HTTPBadRequest, HTTPUnauthorized
import graphene
from .models.user import User
from .models.project import Project
from .models.api_key import APIKey
import json


class UserType(graphene.ObjectType):
    id = graphene.Int()
    username = graphene.String()
    email = graphene.String()
    is_active = graphene.Boolean()


class ProjectType(graphene.ObjectType):
    id = graphene.Int()
    name = graphene.String()
    description = graphene.String()
    repository_url = graphene.String()
    documentation_url = graphene.String()
    is_public = graphene.Boolean()
    created_at = graphene.String()
    updated_at = graphene.String()


class APIKeyType(graphene.ObjectType):
    id = graphene.Int()
    name = graphene.String()
    key = graphene.String()
    is_active = graphene.Boolean()
    created_at = graphene.String()


class Query(graphene.ObjectType):
    me = graphene.Field(UserType)
    projects = graphene.List(ProjectType)
    project = graphene.Field(ProjectType, id=graphene.Int(required=True))
    api_keys = graphene.List(APIKeyType)
    
    def resolve_me(self, info):
        request = info.context
        if not request.current_user:
            return None
        user = request.current_user
        return UserType(
            id=user.id,
            username=user.username,
            email=user.email,
            is_active=user.is_active
        )
    
    def resolve_projects(self, info):
        request = info.context
        query = request.dbsession.query(Project)
        
        if not request.current_user:
            query = query.filter(Project.is_public == True)
        else:
            query = query.filter(
                (Project.is_public == True) | 
                (Project.user_id == request.current_user.id)
            )
        
        projects = query.all()
        return [
            ProjectType(
                id=p.id,
                name=p.name,
                description=p.description,
                repository_url=p.repository_url,
                documentation_url=p.documentation_url,
                is_public=p.is_public,
                created_at=p.created_at.isoformat(),
                updated_at=p.updated_at.isoformat()
            )
            for p in projects
        ]
    
    def resolve_project(self, info, id):
        request = info.context
        project = request.dbsession.query(Project).filter(Project.id == id).first()
        
        if not project:
            return None
        
        if not project.is_public:
            if not request.current_user or request.current_user.id != project.user_id:
                return None
        
        return ProjectType(
            id=project.id,
            name=project.name,
            description=project.description,
            repository_url=project.repository_url,
            documentation_url=project.documentation_url,
            is_public=project.is_public,
            created_at=project.created_at.isoformat(),
            updated_at=project.updated_at.isoformat()
        )
    
    def resolve_api_keys(self, info):
        request = info.context
        if not request.current_user:
            return []
        
        keys = request.dbsession.query(APIKey).filter(
            APIKey.user_id == request.current_user.id
        ).all()
        
        return [
            APIKeyType(
                id=k.id,
                name=k.name,
                key=k.key,
                is_active=k.is_active,
                created_at=k.created_at.isoformat()
            )
            for k in keys
        ]


class CreateProject(graphene.Mutation):
    class Arguments:
        name = graphene.String(required=True)
        description = graphene.String()
        repository_url = graphene.String()
        documentation_url = graphene.String()
        is_public = graphene.Boolean()
    
    project = graphene.Field(ProjectType)
    
    def mutate(self, info, name, description=None, repository_url=None, 
               documentation_url=None, is_public=True):
        request = info.context
        if not request.current_user:
            raise Exception('Authentication required')
        
        project = Project(
            name=name,
            description=description,
            repository_url=repository_url,
            documentation_url=documentation_url,
            is_public=is_public,
            user_id=request.current_user.id
        )
        
        request.dbsession.add(project)
        request.dbsession.flush()
        
        return CreateProject(
            project=ProjectType(
                id=project.id,
                name=project.name,
                description=project.description,
                repository_url=project.repository_url,
                documentation_url=project.documentation_url,
                is_public=project.is_public,
                created_at=project.created_at.isoformat(),
                updated_at=project.updated_at.isoformat()
            )
        )


class UpdateProject(graphene.Mutation):
    class Arguments:
        id = graphene.Int(required=True)
        name = graphene.String()
        description = graphene.String()
        repository_url = graphene.String()
        documentation_url = graphene.String()
        is_public = graphene.Boolean()
    
    project = graphene.Field(ProjectType)
    
    def mutate(self, info, id, name=None, description=None, repository_url=None,
               documentation_url=None, is_public=None):
        request = info.context
        if not request.current_user:
            raise Exception('Authentication required')
        
        project = request.dbsession.query(Project).filter(Project.id == id).first()
        
        if not project:
            raise Exception('Project not found')
        
        if project.user_id != request.current_user.id:
            raise Exception('Access denied')
        
        if name is not None:
            project.name = name
        if description is not None:
            project.description = description
        if repository_url is not None:
            project.repository_url = repository_url
        if documentation_url is not None:
            project.documentation_url = documentation_url
        if is_public is not None:
            project.is_public = is_public
        
        request.dbsession.flush()
        
        return UpdateProject(
            project=ProjectType(
                id=project.id,
                name=project.name,
                description=project.description,
                repository_url=project.repository_url,
                documentation_url=project.documentation_url,
                is_public=project.is_public,
                created_at=project.created_at.isoformat(),
                updated_at=project.updated_at.isoformat()
            )
        )


class DeleteProject(graphene.Mutation):
    class Arguments:
        id = graphene.Int(required=True)
    
    success = graphene.Boolean()
    
    def mutate(self, info, id):
        request = info.context
        if not request.current_user:
            raise Exception('Authentication required')
        
        project = request.dbsession.query(Project).filter(Project.id == id).first()
        
        if not project:
            raise Exception('Project not found')
        
        if project.user_id != request.current_user.id:
            raise Exception('Access denied')
        
        request.dbsession.delete(project)
        
        return DeleteProject(success=True)


class CreateAPIKey(graphene.Mutation):
    class Arguments:
        name = graphene.String(required=True)
    
    api_key = graphene.Field(APIKeyType)
    
    def mutate(self, info, name):
        request = info.context
        if not request.current_user:
            raise Exception('Authentication required')
        
        api_key = APIKey(
            name=name,
            key=APIKey.generate_key(),
            user_id=request.current_user.id
        )
        
        request.dbsession.add(api_key)
        request.dbsession.flush()
        
        return CreateAPIKey(
            api_key=APIKeyType(
                id=api_key.id,
                name=api_key.name,
                key=api_key.key,
                is_active=api_key.is_active,
                created_at=api_key.created_at.isoformat()
            )
        )


class Mutation(graphene.ObjectType):
    create_project = CreateProject.Field()
    update_project = UpdateProject.Field()
    delete_project = DeleteProject.Field()
    create_api_key = CreateAPIKey.Field()


schema = graphene.Schema(query=Query, mutation=Mutation)


@view_config(route_name='graphql', request_method=['GET', 'POST'], renderer='json')
def graphql_view(request):
    if request.method == 'GET':
        query = request.params.get('query')
        variables = request.params.get('variables')
    else:
        try:
            data = request.json_body
            query = data.get('query')
            variables = data.get('variables')
        except:
            raise HTTPBadRequest('Invalid JSON')
    
    if not query:
        raise HTTPBadRequest('No query provided')
    
    if isinstance(variables, str):
        try:
            variables = json.loads(variables)
        except:
            variables = None
    
    result = schema.execute(
        query,
        context_value=request,
        variable_values=variables
    )
    
    response = {}
    if result.data:
        response['data'] = result.data
    if result.errors:
        response['errors'] = [str(e) for e in result.errors]
    
    return response


def includeme(config: Configurator):
    config.add_route('graphql', '/graphql')