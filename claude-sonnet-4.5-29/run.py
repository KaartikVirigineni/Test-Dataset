from waitress import serve
from pyramid.paster import get_app
import os

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 6543))
    app = get_app('development.ini')
    serve(app, host='0.0.0.0', port=port)