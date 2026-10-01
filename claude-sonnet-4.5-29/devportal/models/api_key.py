from sqlalchemy import Column, Integer, String, Boolean, ForeignKey
from sqlalchemy.orm import relationship
from .base import Base, TimestampMixin
import secrets


class APIKey(Base, TimestampMixin):
    __tablename__ = 'api_keys'
    
    id = Column(Integer, primary_key=True)
    key = Column(String(64), unique=True, nullable=False, index=True)
    name = Column(String(120), nullable=False)
    user_id = Column(Integer, ForeignKey('users.id'), nullable=False)
    is_active = Column(Boolean, default=True, nullable=False)
    
    user = relationship('User', backref='api_keys')
    
    @staticmethod
    def generate_key():
        return secrets.token_urlsafe(48)