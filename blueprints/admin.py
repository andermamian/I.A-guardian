"""
GuardianIA v3.0 - Blueprint de Admin Dashboard
"""
from flask import Blueprint, request, jsonify, render_template, session
from flask_login import login_required, current_user
from datetime import datetime
from models import db, User, SecurityEvent
from utils.security import (
    get_system_stats, get_system_metrics, verify_system_integrity,
    log_security_event, log_military_event, is_premium_user
)
from config import APP_NAME, APP_VERSION, DEVELOPER, PREMIUM_FEATURES, MILITARY_CONFIG

admin_bp = Blueprint('admin', __name__)


@admin_bp.route('/admin_dashboard')
@login_required
def dashboard():
    """Panel de administración"""
    if session.get('user_type') != 'admin':
        return render_template('error.html', message='Acceso denegado. Se requiere rol de administrador.'), 403

    stats = get_system_stats()
    integrity = verify_system_integrity()
    metrics = get_system_metrics()

    # Obtener usuarios recientes
    recent_users = []
    try:
        recent_users = User.query.order_by(User.created_at.desc()).limit(10).all()
    except Exception:
        pass

    # Obtener eventos de seguridad recientes
    recent_events = []
    try:
        recent_events = SecurityEvent.query.order_by(SecurityEvent.created_at.desc()).limit(20).all()
    except Exception:
        pass

    return render_template('admin_dashboard.html',
                           stats=stats,
                           integrity=integrity,
                           metrics=metrics,
                           recent_users=recent_users,
                           recent_events=recent_events,
                           app_name=APP_NAME,
                           app_version=APP_VERSION,
                           developer=DEVELOPER,
                           premium_features=PREMIUM_FEATURES,
                           military_config=MILITARY_CONFIG,
                           user=current_user)


@admin_bp.route('/admin_dashboard', methods=['POST'])
@login_required
def admin_action():
    """Acciones del panel de administración"""
    if session.get('user_type') != 'admin':
        return jsonify({'success': False, 'message': 'Acceso denegado'}), 403

    action = request.form.get('log_action') or request.form.get('action', '')

    if action:
        log_security_event('ADMIN_ACTION', f'Acción administrativa: {action}',
                           'info', current_user.id, request.remote_addr)

    return jsonify({'success': True, 'message': f'Acción {action} registrada'})
