"""
GuardianIA v3.0 - Blueprint de User Dashboard y módulos de usuario
"""
from flask import Blueprint, request, jsonify, render_template, session
from flask_login import login_required, current_user
from datetime import datetime
import random
from models import db, User
from utils.security import (
    get_system_stats, get_system_metrics, verify_system_integrity,
    is_premium_user, log_security_event, log_military_event
)
from config import (
    APP_NAME, APP_VERSION, DEVELOPER, PREMIUM_FEATURES,
    MILITARY_CONFIG, VPN_SERVERS, PREMIUM_ENABLED
)

user_bp = Blueprint('user', __name__)


def get_user_membership_level(user):
    """Obtiene el nivel de membresía del usuario"""
    if not user:
        return 'basic'
    ps = user.premium_status if hasattr(user, 'premium_status') else session.get('premium_status', 'basic')
    if ps == 'premium':
        return 'premium'
    return 'basic'


def check_feature_access(feature_name, user=None):
    """Verifica si el usuario tiene acceso a una funcionalidad"""
    level = get_user_membership_level(user or current_user)
    if level == 'premium':
        return True
    # Funcionalidades básicas disponibles para todos
    basic_features = ['user_dashboard', 'chatbot', 'settings', 'user_settings',
                      'user_security', 'user_performance', 'user_assistant']
    return feature_name in basic_features


@user_bp.route('/user_dashboard')
@login_required
def dashboard():
    """Panel de usuario"""
    stats = get_system_stats()
    metrics = get_system_metrics()
    is_premium = is_premium_user(current_user.id, current_user.username)
    membership_level = get_user_membership_level(current_user)

    # Estadísticas simuladas del usuario
    user_stats = {
        'threats_blocked': random.randint(5, 30),
        'scans_completed': random.randint(10, 50),
        'security_score': random.randint(85, 99),
        'ai_interactions': random.randint(20, 100),
        'vpn_sessions': random.randint(0, 15),
        'files_encrypted': random.randint(5, 40),
        'last_scan': datetime.now().strftime('%Y-%m-%d %H:%M'),
        'protection_status': 'Activa'
    }

    # Actividad reciente simulada
    recent_activity = [
        {'type': 'scan', 'description': 'Escaneo de seguridad completado', 'time': '2 min ago', 'status': 'success'},
        {'type': 'threat', 'description': 'Amenaza bloqueada: Malware detectado', 'time': '15 min ago', 'status': 'warning'},
        {'type': 'vpn', 'description': 'Conexión VPN establecida', 'time': '1 hora ago', 'status': 'info'},
        {'type': 'ai', 'description': 'Análisis predictivo completado', 'time': '3 horas ago', 'status': 'success'},
        {'type': 'backup', 'description': 'Backup automático realizado', 'time': '6 horas ago', 'status': 'success'},
    ]

    return render_template('user_dashboard.html',
                           stats=stats,
                           metrics=metrics,
                           user_stats=user_stats,
                           recent_activity=recent_activity,
                           is_premium=is_premium,
                           membership_level=membership_level,
                           premium_features=PREMIUM_FEATURES,
                           app_name=APP_NAME,
                           app_version=APP_VERSION,
                           user=current_user)


@user_bp.route('/user_settings', methods=['GET', 'POST'])
@login_required
def settings():
    """Configuración del usuario"""
    if request.method == 'POST':
        action = request.form.get('action', '')
        if action == 'update_profile':
            try:
                current_user.fullname = request.form.get('fullname', current_user.fullname)
                current_user.email = request.form.get('email', current_user.email)
                db.session.commit()
                return jsonify({'success': True, 'message': 'Perfil actualizado'})
            except Exception as e:
                db.session.rollback()
                return jsonify({'success': False, 'message': str(e)})
        elif action == 'change_password':
            from utils.security import verify_password, hash_password
            old_pass = request.form.get('old_password', '')
            new_pass = request.form.get('new_password', '')
            if verify_password(old_pass, current_user.password_hash):
                current_user.password_hash = hash_password(new_pass)
                db.session.commit()
                return jsonify({'success': True, 'message': 'Contraseña actualizada'})
            return jsonify({'success': False, 'message': 'Contraseña actual incorrecta'})

    return render_template('user_settings.html', user=current_user,
                           is_premium=is_premium_user(current_user.id, current_user.username))


@user_bp.route('/user_security')
@login_required
def security():
    """Seguridad del usuario"""
    metrics = get_system_metrics()
    return render_template('user_security.html', user=current_user, metrics=metrics,
                           is_premium=is_premium_user(current_user.id, current_user.username))


@user_bp.route('/user_performance')
@login_required
def performance():
    """Rendimiento del usuario"""
    metrics = get_system_metrics()
    return render_template('user_performance.html', user=current_user, metrics=metrics,
                           is_premium=is_premium_user(current_user.id, current_user.username))


@user_bp.route('/user_permissions')
@login_required
def permissions():
    """Permisos del usuario"""
    return render_template('user_permissions.html', user=current_user,
                           premium_features=PREMIUM_FEATURES,
                           is_premium=is_premium_user(current_user.id, current_user.username))


@user_bp.route('/user_assistant', methods=['GET', 'POST'])
@login_required
def assistant():
    """Asistente de usuario"""
    if request.method == 'POST':
        action = request.form.get('action', '')
        if action == 'ask':
            question = request.form.get('question', '')
            # Respuesta simulada del asistente
            response = f"Gracias por tu pregunta. Como asistente de GuardianIA, puedo ayudarte con: seguridad, configuración, rendimiento y más. Tu pregunta fue: '{question}'"
            return jsonify({'success': True, 'response': response})
    return render_template('user_assistant.html', user=current_user,
                           is_premium=is_premium_user(current_user.id, current_user.username))


@user_bp.route('/user_test_suite')
@login_required
def test_suite():
    """Suite de pruebas del usuario"""
    return render_template('user_test_suite.html', user=current_user)
