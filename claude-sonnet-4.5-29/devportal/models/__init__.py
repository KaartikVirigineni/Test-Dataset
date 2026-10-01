from sqlalchemy import engine_from_config
from sqlalchemy.orm import sessionmaker, configure_mappers
from pyramid.config import Configurator
import zope.sqlalchemy

from .base import Base
from .user import User
from .api_key import APIKey
from .project import Project

configure_mappers()


def get_engine(settings, prefix='sqlalchemy.'):
    return engine_from_config(settings, prefix)


def get_session_factory(engine):
    factory = sessionmaker()
    factory.configure(bind=engine)
    return factory


def get_tm_session(session_factory, transaction_manager):
    dbsession = session_factory()
    zope.sqlalchemy.register(
        dbsession, transaction_manager=transaction_manager
    )
    return dbsession


def includeme(config: Configurator):
    settings = config.get_settings()
    engine = get_engine(settings)
    
    session_factory = get_session_factory(engine)
    config.registry['dbsession_factory'] = session_factory
    
    config.add_request_method(
        lambda request: get_tm_session(
            session_factory,
            request.tm
        ),
        'dbsession',
        reify=True
    )
    
    Base.metadata.create_all(engine)