import os
from sqlalchemy import create_engine
from models import Base

os.makedirs('data', exist_ok=True)

db_url = os.environ.get('DATABASE_URL', 'sqlite:///data/nps.db')
engine = create_engine(db_url)

Base.metadata.create_all(engine)

print("Database initialized successfully!")