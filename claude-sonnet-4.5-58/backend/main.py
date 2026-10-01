from fastapi import FastAPI, Depends, HTTPException, status
from fastapi.responses import FileResponse, Response
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
import strawberry
from strawberry.fastapi import GraphQLRouter
from contextlib import asynccontextmanager
import yaml
import os
from .database import engine, Base, get_db
from .auth import router as auth_router, get_current_user
from .models import User
from .schema import Query, Mutation
from sqlalchemy.orm import Session

@asynccontextmanager
async def lifespan(app: FastAPI):
    Base.metadata.create_all(bind=engine)
    from .seed import seed_data
    seed_data()
    yield

app = FastAPI(title="DevPortal API", version="1.0.0", lifespan=lifespan)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

schema = strawberry.Schema(query=Query, mutation=Mutation)

async def get_context(
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user)
):
    return {"db": db, "user": user}

graphql_app = GraphQLRouter(schema, context_getter=get_context)

app.include_router(auth_router, prefix="/api/auth", tags=["auth"])
app.include_router(graphql_app, prefix="/graphql")

@app.get("/health")
async def health():
    return {"status": "healthy"}

@app.get("/swagger", response_class=Response)
async def get_swagger():
    with open("openapi.yaml", "r") as f:
        content = f.read()
    return Response(content=content, media_type="application/yaml")

if os.path.exists("frontend"):
    app.mount("/", StaticFiles(directory="frontend", html=True), name="frontend")