from fastapi import FastAPI, Depends, HTTPException, status, UploadFile, File, Header
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from fastapi.responses import FileResponse, Response
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession, async_sessionmaker
from sqlalchemy.orm import declarative_base
from sqlalchemy import Column, Integer, String, DateTime, ForeignKey, select
from datetime import datetime, timedelta
from typing import Optional, List
import os
import uuid
import yaml
from pathlib import Path

from auth import AuthService, get_current_user, User as AuthUser, create_admin_user
from models import Base, User, File as FileModel, Role
from database import engine, async_session

app = FastAPI(title="SecureStore API", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

UPLOAD_DIR = Path("/app/uploads")
UPLOAD_DIR.mkdir(exist_ok=True)

auth_service = AuthService()


@app.on_event("startup")
async def startup():
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)
    
    async with async_session() as session:
        await create_admin_user(session)


@app.get("/")
async def root():
    return {"message": "SecureStore API", "version": "1.0.0"}


@app.get("/swagger")
async def get_swagger():
    with open("openapi.yaml", "r") as f:
        content = f.read()
    return Response(content=content, media_type="application/yaml")


@app.post("/api/auth/register")
async def register(username: str, password: str, email: str):
    async with async_session() as session:
        result = await session.execute(select(User).where(User.username == username))
        if result.scalar_one_or_none():
            raise HTTPException(status_code=400, detail="Username already exists")
        
        user = User(
            username=username,
            email=email,
            password_hash=auth_service.hash_password(password),
            role=Role.USER
        )
        session.add(user)
        await session.commit()
        await session.refresh(user)
        
        token = auth_service.create_access_token({"sub": user.username, "role": user.role.value})
        return {"access_token": token, "token_type": "bearer", "user_id": user.id, "role": user.role.value}


@app.post("/api/auth/login")
async def login(username: str, password: str):
    async with async_session() as session:
        result = await session.execute(select(User).where(User.username == username))
        user = result.scalar_one_or_none()
        
        if not user or not auth_service.verify_password(password, user.password_hash):
            raise HTTPException(status_code=401, detail="Invalid credentials")
        
        token = auth_service.create_access_token({"sub": user.username, "role": user.role.value})
        return {"access_token": token, "token_type": "bearer", "user_id": user.id, "role": user.role.value}


@app.post("/api/files/upload")
async def upload_file(
    file: UploadFile = File(...),
    current_user: AuthUser = Depends(get_current_user)
):
    file_id = str(uuid.uuid4())
    file_ext = Path(file.filename).suffix
    stored_filename = f"{file_id}{file_ext}"
    file_path = UPLOAD_DIR / stored_filename
    
    content = await file.read()
    with open(file_path, "wb") as f:
        f.write(content)
    
    async with async_session() as session:
        result = await session.execute(select(User).where(User.username == current_user.username))
        user = result.scalar_one_or_none()
        
        file_model = FileModel(
            filename=file.filename,
            stored_filename=stored_filename,
            file_size=len(content),
            content_type=file.content_type or "application/octet-stream",
            user_id=user.id
        )
        session.add(file_model)
        await session.commit()
        await session.refresh(file_model)
        
        return {
            "id": file_model.id,
            "filename": file_model.filename,
            "size": file_model.file_size,
            "content_type": file_model.content_type,
            "uploaded_at": file_model.uploaded_at.isoformat()
        }


@app.get("/api/files")
async def list_files(current_user: AuthUser = Depends(get_current_user)):
    async with async_session() as session:
        result = await session.execute(select(User).where(User.username == current_user.username))
        user = result.scalar_one_or_none()
        
        if current_user.role == "admin":
            files_result = await session.execute(select(FileModel))
        else:
            files_result = await session.execute(select(FileModel).where(FileModel.user_id == user.id))
        
        files = files_result.scalars().all()
        
        return [
            {
                "id": f.id,
                "filename": f.filename,
                "size": f.file_size,
                "content_type": f.content_type,
                "uploaded_at": f.uploaded_at.isoformat(),
                "user_id": f.user_id
            }
            for f in files
        ]


@app.get("/api/files/{file_id}")
async def get_file(file_id: int, current_user: AuthUser = Depends(get_current_user)):
    async with async_session() as session:
        result = await session.execute(select(User).where(User.username == current_user.username))
        user = result.scalar_one_or_none()
        
        file_result = await session.execute(select(FileModel).where(FileModel.id == file_id))
        file_model = file_result.scalar_one_or_none()
        
        if not file_model:
            raise HTTPException(status_code=404, detail="File not found")
        
        if current_user.role != "admin" and file_model.user_id != user.id:
            raise HTTPException(status_code=403, detail="Permission denied")
        
        file_path = UPLOAD_DIR / file_model.stored_filename
        if not file_path.exists():
            raise HTTPException(status_code=404, detail="File not found on disk")
        
        return FileResponse(
            path=file_path,
            filename=file_model.filename,
            media_type=file_model.content_type
        )


@app.delete("/api/files/{file_id}")
async def delete_file(file_id: int, current_user: AuthUser = Depends(get_current_user)):
    async with async_session() as session:
        result = await session.execute(select(User).where(User.username == current_user.username))
        user = result.scalar_one_or_none()
        
        file_result = await session.execute(select(FileModel).where(FileModel.id == file_id))
        file_model = file_result.scalar_one_or_none()
        
        if not file_model:
            raise HTTPException(status_code=404, detail="File not found")
        
        if current_user.role != "admin" and file_model.user_id != user.id:
            raise HTTPException(status_code=403, detail="Permission denied")
        
        file_path = UPLOAD_DIR / file_model.stored_filename
        if file_path.exists():
            file_path.unlink()
        
        await session.delete(file_model)
        await session.commit()
        
        return {"message": "File deleted successfully"}


@app.get("/api/users")
async def list_users(current_user: AuthUser = Depends(get_current_user)):
    if current_user.role != "admin":
        raise HTTPException(status_code=403, detail="Admin access required")
    
    async with async_session() as session:
        result = await session.execute(select(User))
        users = result.scalars().all()
        
        return [
            {
                "id": u.id,
                "username": u.username,
                "email": u.email,
                "role": u.role.value,
                "created_at": u.created_at.isoformat()
            }
            for u in users
        ]


@app.put("/api/users/{user_id}/role")
async def update_user_role(user_id: int, role: str, current_user: AuthUser = Depends(get_current_user)):
    if current_user.role != "admin":
        raise HTTPException(status_code=403, detail="Admin access required")
    
    if role not in ["user", "admin"]:
        raise HTTPException(status_code=400, detail="Invalid role")
    
    async with async_session() as session:
        result = await session.execute(select(User).where(User.id == user_id))
        user = result.scalar_one_or_none()
        
        if not user:
            raise HTTPException(status_code=404, detail="User not found")
        
        user.role = Role(role)
        await session.commit()
        
        return {"message": "Role updated successfully", "user_id": user_id, "new_role": role}