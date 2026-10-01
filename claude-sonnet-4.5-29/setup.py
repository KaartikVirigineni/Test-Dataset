from setuptools import setup, find_packages

setup(
    name='devportal',
    version='1.0.0',
    packages=find_packages(),
    include_package_data=True,
    zip_safe=False,
    install_requires=[
        'pyramid',
        'waitress',
        'pyramid-tm',
        'SQLAlchemy',
        'graphene',
        'PyJWT',
        'bcrypt',
        'marshmallow',
        'pyramid-cors',
        'pyyaml',
    ],
    entry_points={
        'paste.app_factory': [
            'main = devportal:main',
        ],
    },
)