from sqlalchemy import create_engine
from models import Base, User, Role
import bcrypt
import os

def init_database():
    database_url = os.getenv('DATABASE_URL', 'sqlite:///gists.db')
    engine = create_engine(database_url)
    Base.metadata.drop_all(engine)
    Base.metadata.create_all(engine)
    
    from sqlalchemy.orm import sessionmaker
    Session = sessionmaker(bind=engine)
    session = Session()
    
    # Create default admin user
    admin_password = bcrypt.hashpw('admin123'.encode('utf-8'), bcrypt.gensalt())
    admin = User(
        username='admin',
        email='admin@gistmanager.com',
        password_hash=admin_password.decode('utf-8'),
        role=Role.ADMIN
    )
    
    # Create default regular user
    user_password = bcrypt.hashpw('user123'.encode('utf-8'), bcrypt.gensalt())
    user = User(
        username='user',
        email='user@gistmanager.com',
        password_hash=user_password.decode('utf-8'),
        role=Role.USER
    )
    
    session.add(admin)
    session.add(user)
    session.commit()
    session.close()
    
    print("Database initialized successfully!")
    print("Default admin user: admin / admin123")
    print("Default regular user: user / user123")

if __name__ == '__main__':
    init_database()