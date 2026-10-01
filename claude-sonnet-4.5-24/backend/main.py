from fastapi import FastAPI, Depends, HTTPException, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from fastapi.responses import FileResponse, JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from strawberry.fastapi import GraphQLRouter
import strawberry
from typing import Optional, List
from datetime import datetime, timedelta
from jose import JWTError, jwt
from passlib.context import CryptContext
from sqlalchemy import create_engine, Column, Integer, String, DateTime, ForeignKey, Text, Boolean
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, Session, relationship
from pydantic import BaseModel
import os

# Configuration
DATABASE_URL = os.getenv("DATABASE_URL", "sqlite:///./formflow.db")
JWT_SECRET = os.getenv("JWT_SECRET", "your-secret-key-change-in-production")
JWT_ALGORITHM = os.getenv("JWT_ALGORITHM", "HS256")
ACCESS_TOKEN_EXPIRE_MINUTES = int(os.getenv("ACCESS_TOKEN_EXPIRE_MINUTES", "30"))

# Database setup
engine = create_engine(DATABASE_URL, connect_args={"check_same_thread": False})
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

# Password hashing
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
security = HTTPBearer()

# Models
class UserDB(Base):
    __tablename__ = "users"
    id = Column(Integer, primary_key=True, index=True)
    email = Column(String, unique=True, index=True, nullable=False)
    hashed_password = Column(String, nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow)
    surveys = relationship("SurveyDB", back_populates="owner")

class SurveyDB(Base):
    __tablename__ = "surveys"
    id = Column(Integer, primary_key=True, index=True)
    title = Column(String, nullable=False)
    description = Column(Text)
    owner_id = Column(Integer, ForeignKey("users.id"))
    created_at = Column(DateTime, default=datetime.utcnow)
    is_active = Column(Boolean, default=True)
    owner = relationship("UserDB", back_populates="surveys")
    questions = relationship("QuestionDB", back_populates="survey", cascade="all, delete-orphan")
    responses = relationship("ResponseDB", back_populates="survey", cascade="all, delete-orphan")

class QuestionDB(Base):
    __tablename__ = "questions"
    id = Column(Integer, primary_key=True, index=True)
    survey_id = Column(Integer, ForeignKey("surveys.id"))
    question_text = Column(Text, nullable=False)
    question_type = Column(String, nullable=False)
    order = Column(Integer, default=0)
    required = Column(Boolean, default=False)
    survey = relationship("SurveyDB", back_populates="questions")
    answers = relationship("AnswerDB", back_populates="question", cascade="all, delete-orphan")

class ResponseDB(Base):
    __tablename__ = "responses"
    id = Column(Integer, primary_key=True, index=True)
    survey_id = Column(Integer, ForeignKey("surveys.id"))
    submitted_at = Column(DateTime, default=datetime.utcnow)
    survey = relationship("SurveyDB", back_populates="responses")
    answers = relationship("AnswerDB", back_populates="response", cascade="all, delete-orphan")

class AnswerDB(Base):
    __tablename__ = "answers"
    id = Column(Integer, primary_key=True, index=True)
    response_id = Column(Integer, ForeignKey("responses.id"))
    question_id = Column(Integer, ForeignKey("questions.id"))
    answer_text = Column(Text)
    response = relationship("ResponseDB", back_populates="answers")
    question = relationship("QuestionDB", back_populates="answers")

Base.metadata.create_all(bind=engine)

# Pydantic models
class UserRegister(BaseModel):
    email: str
    password: str

class UserLogin(BaseModel):
    email: str
    password: str

class Token(BaseModel):
    access_token: str
    token_type: str

class SurveyCreate(BaseModel):
    title: str
    description: Optional[str] = None

class QuestionCreate(BaseModel):
    question_text: str
    question_type: str
    order: int = 0
    required: bool = False

class AnswerSubmit(BaseModel):
    question_id: int
    answer_text: str

class ResponseSubmit(BaseModel):
    survey_id: int
    answers: List[AnswerSubmit]

# Strawberry GraphQL types
@strawberry.type
class User:
    id: int
    email: str
    created_at: datetime

@strawberry.type
class Question:
    id: int
    question_text: str
    question_type: str
    order: int
    required: bool

@strawberry.type
class Survey:
    id: int
    title: str
    description: Optional[str]
    created_at: datetime
    is_active: bool
    questions: List[Question]

@strawberry.type
class Answer:
    id: int
    question_id: int
    answer_text: str

@strawberry.type
class Response:
    id: int
    survey_id: int
    submitted_at: datetime
    answers: List[Answer]

@strawberry.input
class SurveyInput:
    title: str
    description: Optional[str] = None

@strawberry.input
class QuestionInput:
    question_text: str
    question_type: str
    order: int = 0
    required: bool = False

# Dependency
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# Auth utilities
def verify_password(plain_password, hashed_password):
    return pwd_context.verify(plain_password, hashed_password)

def get_password_hash(password):
    return pwd_context.hash(password)

def create_access_token(data: dict):
    to_encode = data.copy()
    expire = datetime.utcnow() + timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES)
    to_encode.update({"exp": expire})
    return jwt.encode(to_encode, JWT_SECRET, algorithm=JWT_ALGORITHM)

def get_current_user(credentials: HTTPAuthorizationCredentials = Depends(security), db: Session = Depends(get_db)):
    token = credentials.credentials
    try:
        payload = jwt.decode(token, JWT_SECRET, algorithms=[JWT_ALGORITHM])
        email: str = payload.get("sub")
        if email is None:
            raise HTTPException(status_code=401, detail="Invalid authentication credentials")
    except JWTError:
        raise HTTPException(status_code=401, detail="Invalid authentication credentials")
    
    user = db.query(UserDB).filter(UserDB.email == email).first()
    if user is None:
        raise HTTPException(status_code=401, detail="User not found")
    return user

# GraphQL context
async def get_context(db: Session = Depends(get_db)):
    return {"db": db}

# GraphQL resolvers
@strawberry.type
class Query:
    @strawberry.field
    def surveys(self, info) -> List[Survey]:
        db = info.context["db"]
        surveys_db = db.query(SurveyDB).filter(SurveyDB.is_active == True).all()
        return [
            Survey(
                id=s.id,
                title=s.title,
                description=s.description,
                created_at=s.created_at,
                is_active=s.is_active,
                questions=[
                    Question(
                        id=q.id,
                        question_text=q.question_text,
                        question_type=q.question_type,
                        order=q.order,
                        required=q.required
                    ) for q in s.questions
                ]
            ) for s in surveys_db
        ]
    
    @strawberry.field
    def survey(self, info, survey_id: int) -> Optional[Survey]:
        db = info.context["db"]
        s = db.query(SurveyDB).filter(SurveyDB.id == survey_id).first()
        if not s:
            return None
        return Survey(
            id=s.id,
            title=s.title,
            description=s.description,
            created_at=s.created_at,
            is_active=s.is_active,
            questions=[
                Question(
                    id=q.id,
                    question_text=q.question_text,
                    question_type=q.question_type,
                    order=q.order,
                    required=q.required
                ) for q in s.questions
            ]
        )

@strawberry.type
class Mutation:
    @strawberry.mutation
    def create_survey(self, info, survey_input: SurveyInput) -> Survey:
        db = info.context["db"]
        survey = SurveyDB(
            title=survey_input.title,
            description=survey_input.description,
            owner_id=1
        )
        db.add(survey)
        db.commit()
        db.refresh(survey)
        return Survey(
            id=survey.id,
            title=survey.title,
            description=survey.description,
            created_at=survey.created_at,
            is_active=survey.is_active,
            questions=[]
        )
    
    @strawberry.mutation
    def add_question(self, info, survey_id: int, question_input: QuestionInput) -> Question:
        db = info.context["db"]
        question = QuestionDB(
            survey_id=survey_id,
            question_text=question_input.question_text,
            question_type=question_input.question_type,
            order=question_input.order,
            required=question_input.required
        )
        db.add(question)
        db.commit()
        db.refresh(question)
        return Question(
            id=question.id,
            question_text=question.question_text,
            question_type=question.question_type,
            order=question.order,
            required=question.required
        )

schema = strawberry.Schema(query=Query, mutation=Mutation)

# FastAPI app
app = FastAPI(title="FormFlow Survey Builder", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Health check
@app.get("/health")
async def health_check():
    return {"status": "healthy"}

# REST Auth endpoints
@app.post("/api/register", response_model=Token)
async def register(user: UserRegister, db: Session = Depends(get_db)):
    existing_user = db.query(UserDB).filter(UserDB.email == user.email).first()
    if existing_user:
        raise HTTPException(status_code=400, detail="Email already registered")
    
    hashed_password = get_password_hash(user.password)
    new_user = UserDB(email=user.email, hashed_password=hashed_password)
    db.add(new_user)
    db.commit()
    db.refresh(new_user)
    
    access_token = create_access_token(data={"sub": new_user.email})
    return {"access_token": access_token, "token_type": "bearer"}

@app.post("/api/login", response_model=Token)
async def login(user: UserLogin, db: Session = Depends(get_db)):
    db_user = db.query(UserDB).filter(UserDB.email == user.email).first()
    if not db_user or not verify_password(user.password, db_user.hashed_password):
        raise HTTPException(status_code=401, detail="Incorrect email or password")
    
    access_token = create_access_token(data={"sub": db_user.email})
    return {"access_token": access_token, "token_type": "bearer"}

# REST Survey endpoints
@app.post("/api/surveys")
async def create_survey_rest(
    survey: SurveyCreate,
    current_user: UserDB = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    new_survey = SurveyDB(
        title=survey.title,
        description=survey.description,
        owner_id=current_user.id
    )
    db.add(new_survey)
    db.commit()
    db.refresh(new_survey)
    return {"id": new_survey.id, "title": new_survey.title, "description": new_survey.description}

@app.get("/api/surveys")
async def get_surveys_rest(db: Session = Depends(get_db)):
    surveys = db.query(SurveyDB).filter(SurveyDB.is_active == True).all()
    return [{"id": s.id, "title": s.title, "description": s.description} for s in surveys]

@app.get("/api/surveys/{survey_id}")
async def get_survey_rest(survey_id: int, db: Session = Depends(get_db)):
    survey = db.query(SurveyDB).filter(SurveyDB.id == survey_id).first()
    if not survey:
        raise HTTPException(status_code=404, detail="Survey not found")
    
    questions = [
        {
            "id": q.id,
            "question_text": q.question_text,
            "question_type": q.question_type,
            "order": q.order,
            "required": q.required
        } for q in survey.questions
    ]
    
    return {
        "id": survey.id,
        "title": survey.title,
        "description": survey.description,
        "questions": questions
    }

@app.post("/api/surveys/{survey_id}/questions")
async def add_question_rest(
    survey_id: int,
    question: QuestionCreate,
    current_user: UserDB = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    survey = db.query(SurveyDB).filter(SurveyDB.id == survey_id, SurveyDB.owner_id == current_user.id).first()
    if not survey:
        raise HTTPException(status_code=404, detail="Survey not found or unauthorized")
    
    new_question = QuestionDB(
        survey_id=survey_id,
        question_text=question.question_text,
        question_type=question.question_type,
        order=question.order,
        required=question.required
    )
    db.add(new_question)
    db.commit()
    db.refresh(new_question)
    return {"id": new_question.id, "question_text": new_question.question_text}

@app.post("/api/responses")
async def submit_response_rest(response: ResponseSubmit, db: Session = Depends(get_db)):
    survey = db.query(SurveyDB).filter(SurveyDB.id == response.survey_id).first()
    if not survey:
        raise HTTPException(status_code=404, detail="Survey not found")
    
    new_response = ResponseDB(survey_id=response.survey_id)
    db.add(new_response)
    db.commit()
    db.refresh(new_response)
    
    for answer in response.answers:
        new_answer = AnswerDB(
            response_id=new_response.id,
            question_id=answer.question_id,
            answer_text=answer.answer_text
        )
        db.add(new_answer)
    
    db.commit()
    return {"id": new_response.id, "message": "Response submitted successfully"}

@app.get("/api/surveys/{survey_id}/responses")
async def get_responses_rest(
    survey_id: int,
    current_user: UserDB = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    survey = db.query(SurveyDB).filter(SurveyDB.id == survey_id, SurveyDB.owner_id == current_user.id).first()
    if not survey:
        raise HTTPException(status_code=404, detail="Survey not found or unauthorized")
    
    responses = []
    for resp in survey.responses:
        answers = [
            {
                "question_id": a.question_id,
                "answer_text": a.answer_text
            } for a in resp.answers
        ]
        responses.append({
            "id": resp.id,
            "submitted_at": resp.submitted_at.isoformat(),
            "answers": answers
        })
    
    return responses

# Swagger endpoints
@app.get("/swagger")
async def get_swagger_spec():
    return FileResponse("openapi.yaml", media_type="application/yaml")

@app.get("/swagger.json")
async def get_swagger_spec_json():
    import yaml
    with open("openapi.yaml", "r") as f:
        spec = yaml.safe_load(f)
    return JSONResponse(content=spec)

# GraphQL endpoint
graphql_app = GraphQLRouter(schema, context_getter=get_context)
app.include_router(graphql_app, prefix="/graphql")