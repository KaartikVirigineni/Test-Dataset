from pyramid.view import view_config
from pyramid.response import Response
from .models import Feedback
from .auth import get_current_user
import os


@view_config(route_name='health', renderer='json', request_method='GET')
def health(request):
    return {'status': 'healthy'}


@view_config(route_name='create_feedback', renderer='json', request_method='POST')
def create_feedback(request):
    user = get_current_user(request)
    if not user:
        request.response.status = 401
        return {'error': 'Unauthorized'}
    
    data = request.json_body
    score = data.get('score')
    
    if score is None or not isinstance(score, int) or score < 0 or score > 10:
        request.response.status = 400
        return {'error': 'Score must be an integer between 0 and 10'}
    
    feedback = Feedback(
        score=score,
        email=data.get('email'),
        comment=data.get('comment'),
        category=data.get('category'),
        user_id=user.id
    )
    
    request.dbsession.add(feedback)
    request.dbsession.flush()
    
    request.response.status = 201
    return {
        'id': feedback.id,
        'score': feedback.score,
        'email': feedback.email,
        'comment': feedback.comment,
        'category': feedback.category,
        'created_at': feedback.created_at.isoformat(),
        'user_id': feedback.user_id
    }


@view_config(route_name='list_feedback', renderer='json', request_method='GET')
def list_feedback(request):
    user = get_current_user(request)
    if not user:
        request.response.status = 401
        return {'error': 'Unauthorized'}
    
    feedbacks = request.dbsession.query(Feedback).order_by(Feedback.created_at.desc()).all()
    
    return {
        'feedback': [
            {
                'id': f.id,
                'score': f.score,
                'email': f.email,
                'comment': f.comment,
                'category': f.category,
                'created_at': f.created_at.isoformat(),
                'user_id': f.user_id
            }
            for f in feedbacks
        ]
    }


@view_config(route_name='get_feedback', renderer='json', request_method='GET')
def get_feedback(request):
    user = get_current_user(request)
    if not user:
        request.response.status = 401
        return {'error': 'Unauthorized'}
    
    feedback_id = int(request.matchdict['id'])
    feedback = request.dbsession.query(Feedback).filter_by(id=feedback_id).first()
    
    if not feedback:
        request.response.status = 404
        return {'error': 'Feedback not found'}
    
    return {
        'id': feedback.id,
        'score': feedback.score,
        'email': feedback.email,
        'comment': feedback.comment,
        'category': feedback.category,
        'created_at': feedback.created_at.isoformat(),
        'user_id': feedback.user_id
    }


@view_config(route_name='update_feedback', renderer='json', request_method='PUT')
def update_feedback(request):
    user = get_current_user(request)
    if not user:
        request.response.status = 401
        return {'error': 'Unauthorized'}
    
    feedback_id = int(request.matchdict['id'])
    feedback = request.dbsession.query(Feedback).filter_by(id=feedback_id).first()
    
    if not feedback:
        request.response.status = 404
        return {'error': 'Feedback not found'}
    
    data = request.json_body
    
    if 'score' in data:
        score = data['score']
        if not isinstance(score, int) or score < 0 or score > 10:
            request.response.status = 400
            return {'error': 'Score must be an integer between 0 and 10'}
        feedback.score = score
    
    if 'email' in data:
        feedback.email = data['email']
    if 'comment' in data:
        feedback.comment = data['comment']
    if 'category' in data:
        feedback.category = data['category']
    
    request.dbsession.flush()
    
    return {
        'id': feedback.id,
        'score': feedback.score,
        'email': feedback.email,
        'comment': feedback.comment,
        'category': feedback.category,
        'created_at': feedback.created_at.isoformat(),
        'user_id': feedback.user_id
    }


@view_config(route_name='delete_feedback', renderer='json', request_method='DELETE')
def delete_feedback(request):
    user = get_current_user(request)
    if not user:
        request.response.status = 401
        return {'error': 'Unauthorized'}
    
    feedback_id = int(request.matchdict['id'])
    feedback = request.dbsession.query(Feedback).filter_by(id=feedback_id).first()
    
    if not feedback:
        request.response.status = 404
        return {'error': 'Feedback not found'}
    
    request.dbsession.delete(feedback)
    request.dbsession.flush()
    
    return {'message': 'Feedback deleted successfully'}


@view_config(route_name='nps_stats', renderer='json', request_method='GET')
def nps_stats(request):
    user = get_current_user(request)
    if not user:
        request.response.status = 401
        return {'error': 'Unauthorized'}
    
    feedbacks = request.dbsession.query(Feedback).all()
    
    if not feedbacks:
        return {
            'total': 0,
            'nps_score': 0,
            'promoters': 0,
            'passives': 0,
            'detractors': 0
        }
    
    promoters = len([f for f in feedbacks if f.score >= 9])
    passives = len([f for f in feedbacks if 7 <= f.score <= 8])
    detractors = len([f for f in feedbacks if f.score <= 6])
    total = len(feedbacks)
    
    nps_score = ((promoters - detractors) / total * 100) if total > 0 else 0
    
    return {
        'total': total,
        'nps_score': round(nps_score, 2),
        'promoters': promoters,
        'passives': passives,
        'detractors': detractors
    }


@view_config(route_name='swagger', request_method='GET')
def swagger(request):
    swagger_path = os.path.join(os.path.dirname(__file__), 'openapi.yaml')
    with open(swagger_path, 'r') as f:
        content = f.read()
    return Response(content, content_type='application/yaml')


@view_config(route_name='swagger_ui', request_method='GET', renderer='templates/swagger_ui.html')
def swagger_ui(request):
    return {}


def includeme(config):
    config.add_route('health', '/health')
    config.add_route('create_feedback', '/api/feedback')
    config.add_route('list_feedback', '/api/feedback')
    config.add_route('get_feedback', '/api/feedback/{id}')
    config.add_route('update_feedback', '/api/feedback/{id}')
    config.add_route('delete_feedback', '/api/feedback/{id}')
    config.add_route('nps_stats', '/api/stats/nps')
    config.add_route('swagger', '/swagger')
    config.add_route('swagger_ui', '/docs')
    
    config.add_static_view(name='static', path='static')