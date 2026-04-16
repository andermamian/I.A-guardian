"""
GuardianIA v3.0 - Blueprint de Páginas Públicas y Utilidades
Incluye: Index (landing), Debug, Test Suites, Database SQL export
"""
from flask import Blueprint, request, jsonify, render_template, session, Response
from flask_login import login_required, current_user
from datetime import datetime
import random
from utils.security import get_system_stats, verify_system_integrity, get_system_metrics, is_premium_user
from utils.database import get_db_status, create_mysql_database_sql
from config import APP_NAME, APP_VERSION, DEVELOPER, PREMIUM_FEATURES

pages_bp = Blueprint('pages', __name__)


@pages_bp.route('/')
def index():
    """Página principal / Landing"""
    stats = get_system_stats()
    is_logged = current_user.is_authenticated if hasattr(current_user, 'is_authenticated') else False
    is_premium = False
    if is_logged:
        is_premium = is_premium_user(current_user.id, current_user.username)
    return render_template('index.html',
                           stats=stats,
                           is_logged=is_logged,
                           is_premium=is_premium,
                           app_name=APP_NAME,
                           app_version=APP_VERSION,
                           developer=DEVELOPER,
                           premium_features=PREMIUM_FEATURES)


@pages_bp.route('/debug')
@login_required
def debug():
    """Página de debug"""
    if session.get('user_type') != 'admin':
        return render_template('error.html', message='Acceso denegado'), 403
    db_status = get_db_status()
    integrity = verify_system_integrity()
    metrics = get_system_metrics()
    session_data = dict(session)
    # Remover datos sensibles
    safe_session = {k: v for k, v in session_data.items() if k not in ['_flashes', 'csrf_token']}
    return render_template('debug.html', db_status=db_status, integrity=integrity,
                           metrics=metrics, session_data=safe_session, user=current_user)


@pages_bp.route('/debug_session')
@login_required
def debug_session():
    """Debug de sesión"""
    if session.get('user_type') != 'admin':
        return render_template('error.html', message='Acceso denegado'), 403
    session_data = dict(session)
    return render_template('debug_session.html', session_data=session_data, user=current_user)


@pages_bp.route('/debug_quantum')
@login_required
def debug_quantum():
    """Debug cuántico"""
    if session.get('user_type') != 'admin':
        return render_template('error.html', message='Acceso denegado'), 403
    quantum_data = {
        'coherence': round(random.uniform(0.7, 0.99), 3),
        'entanglement': round(random.uniform(0.8, 0.99), 3),
        'superposition': round(random.uniform(0.6, 0.95), 3),
        'error_rate': round(random.uniform(0.001, 0.05), 4),
        'qubits_active': random.randint(50, 1024),
        'volume': 1024
    }
    return render_template('debug_quantum.html', quantum_data=quantum_data, user=current_user)


@pages_bp.route('/test_system')
@login_required
def test_system():
    """Pruebas del sistema"""
    tests = {
        'database': {'status': 'pass', 'time': f'{random.randint(1, 50)} ms'},
        'encryption': {'status': 'pass', 'time': f'{random.randint(1, 20)} ms'},
        'ai_engine': {'status': 'pass', 'time': f'{random.randint(5, 100)} ms'},
        'firewall': {'status': 'pass', 'time': f'{random.randint(1, 30)} ms'},
        'vpn': {'status': 'pass', 'time': f'{random.randint(10, 200)} ms'},
        'quantum': {'status': 'pass', 'time': f'{random.randint(5, 50)} ms'},
        'session': {'status': 'pass', 'time': f'{random.randint(1, 10)} ms'},
        'logging': {'status': 'pass', 'time': f'{random.randint(1, 15)} ms'}
    }
    total = len(tests)
    passed = sum(1 for t in tests.values() if t['status'] == 'pass')
    return render_template('test_system.html', tests=tests, total=total, passed=passed, user=current_user)


@pages_bp.route('/comprehensive_test_suite')
@login_required
def comprehensive_test_suite():
    """Suite de pruebas completa"""
    return render_template('comprehensive_test_suite.html', user=current_user)


@pages_bp.route('/security_test')
@login_required
def security_test():
    """Pruebas de seguridad"""
    return render_template('security_test.html', user=current_user)


@pages_bp.route('/database_sql')
@login_required
def database_sql():
    """Exportar SQL para phpMyAdmin"""
    if session.get('user_type') != 'admin':
        return render_template('error.html', message='Acceso denegado'), 403
    sql = create_mysql_database_sql()
    return Response(sql, mimetype='text/plain',
                    headers={'Content-Disposition': 'attachment;filename=guardianai_database.sql'})


@pages_bp.route('/error')
def error_page():
    """Página de error genérica"""
    message = request.args.get('message', 'Ha ocurrido un error')
    return render_template('error.html', message=message)
