import strawberry
from typing import List, Optional
from datetime import datetime
from sqlalchemy.orm import Session
from .models import Project, Resource
import secrets

@strawberry.type
class ProjectType:
    id: int
    name: str
    description: str
    api_key: str
    created_at: datetime

@strawberry.type
class ResourceType:
    id: int
    title: str
    content: str
    category: str
    created_at: datetime

@strawberry.type
class Query:
    @strawberry.field
    def projects(self, info: strawberry.Info) -> List[ProjectType]:
        db: Session = info.context["db"]
        user = info.context["user"]
        projects = db.query(Project).filter(Project.owner_id == user.id).all()
        return [
            ProjectType(
                id=p.id,
                name=p.name,
                description=p.description,
                api_key=p.api_key,
                created_at=p.created_at
            )
            for p in projects
        ]
    
    @strawberry.field
    def resources(self, info: strawberry.Info) -> List[ResourceType]:
        db: Session = info.context["db"]
        resources = db.query(Resource).all()
        return [
            ResourceType(
                id=r.id,
                title=r.title,
                content=r.content,
                category=r.category,
                created_at=r.created_at
            )
            for r in resources
        ]

@strawberry.type
class Mutation:
    @strawberry.mutation
    def create_project(self, name: str, description: str, info: strawberry.Info) -> ProjectType:
        db: Session = info.context["db"]
        user = info.context["user"]
        
        api_key = secrets.token_urlsafe(32)
        
        project = Project(
            name=name,
            description=description,
            api_key=api_key,
            owner_id=user.id
        )
        db.add(project)
        db.commit()
        db.refresh(project)
        
        return ProjectType(
            id=project.id,
            name=project.name,
            description=project.description,
            api_key=project.api_key,
            created_at=project.created_at
        )
    
    @strawberry.mutation
    def delete_project(self, id: int, info: strawberry.Info) -> bool:
        db: Session = info.context["db"]
        user = info.context["user"]
        
        project = db.query(Project).filter(
            Project.id == id,
            Project.owner_id == user.id
        ).first()
        
        if not project:
            return False
        
        db.delete(project)
        db.commit()
        return True