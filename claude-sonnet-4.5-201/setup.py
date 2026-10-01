from setuptools import setup, find_packages

requires = [
    'pyramid',
    'waitress',
    'pyramid-tm',
    'SQLAlchemy',
    'pyramid-jwt',
    'PyJWT',
    'passlib',
    'bcrypt',
    'marshmallow',
    'pyramid-cors',
    'zope.sqlalchemy',
]

setup(
    name='nps_feedback_collector',
    version='1.0.0',
    description='NPS Feedback Collector API',
    packages=find_packages(),
    include_package_data=True,
    install_requires=requires,
    entry_points={
        'paste.app_factory': [
            'main = app:main',
        ],
    },
)