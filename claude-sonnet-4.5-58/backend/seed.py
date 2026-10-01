from .database import SessionLocal
from .models import Resource

def seed_data():
    db = SessionLocal()
    
    if db.query(Resource).count() == 0:
        resources = [
            Resource(
                title="Getting Started Guide",
                content="Learn how to integrate with the DevPortal API. This guide covers authentication, basic requests, and best practices.",
                category="Tutorial"
            ),
            Resource(
                title="API Reference",
                content="Complete API reference documentation including all available endpoints, parameters, and response formats.",
                category="Documentation"
            ),
            Resource(
                title="GraphQL Schema",
                content="Explore the GraphQL schema with queries and mutations for managing projects and resources.",
                category="Documentation"
            ),
            Resource(
                title="Authentication Best Practices",
                content="Security guidelines for managing API keys and tokens. Learn how to keep your credentials safe.",
                category="Security"
            ),
            Resource(
                title="Rate Limiting",
                content="Understanding rate limits and how to handle them in your applications. Includes retry strategies.",
                category="Tutorial"
            ),
        ]
        
        for resource in resources:
            db.add(resource)
        
        db.commit()
    
    db.close()