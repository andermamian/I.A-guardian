"""
GuardianIA v3.0 - Blueprint de Autenticación
Login, Logout, Register - Compatible con MySQL AppServ
"""
from flask import Blueprint, request, jsonify, render_template, redirect, url_for, session
from flask_login import login_user, logout_user, login_required, current_user
from datetime import datetime
from models import db, User
from utils.security import (
    hash_password, verify_password, sanitize_input, validate_email,
    log_security_event, log_military_event, generate_token
)
from config import DEFAULT_USERS, MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_TIME

auth_bp = Blueprint('auth', __name__)


def authenticate_user(username, password):
    """Autentica un usuario contra la BD MySQL o usuarios por defecto"""
    # Intentar autenticar desde la base de datos (MySQL AppServ)
    try:
        user = User.query.filter_by(username=username).first()
        if user:
            # Verificar si está bloqueado
            if user.locked_until and user.locked_until > datetime.utcnow():
                return {
                    'success': False,
                    'message': 'Cuenta bloqueada temporalmente. Intente más tarde.'
                }

            # Verificar contraseña con hash bcrypt
            if verify_password(password, user.password_hash):
                # Login exitoso - resetear intentos
                user.login_attempts = 0
                user.last_login = datetime.utcnow()
                user.locked_until = None
                db.session.commit()

                log_military_event('AUTH_SUCCESS', f'Usuario {username} autenticado desde MySQL',
                                   user_id=user.id)
                return {
                    'success': True,
                    'user': user.to_dict(),
                    'source': 'database',
                    'connection': 'MySQL AppServ'
                }
            else:
                # Contraseña incorrecta - incrementar intentos
                user.login_attempts = (user.login_attempts or 0) + 1
                if user.login_attempts >= MAX_LOGIN_ATTEMPTS:
                    from datetime import timedelta
                    user.locked_until = datetime.utcnow() + timedelta(seconds=LOGIN_LOCKOUT_TIME)
                db.session.commit()

                return {
                    'success': False,
                    'message': 'Credenciales incorrectas'
                }
    except Exception as e:
        log_security_event('AUTH_DB_ERROR', f'Error consultando BD: {str(e)}', 'warning')

    # Fallback: autenticar contra usuarios por defecto
    if username in DEFAULT_USERS:
        default_user = DEFAULT_USERS[username]
        if password == default_user['password']:
            log_military_event('AUTH_SUCCESS', f'Usuario {username} autenticado desde defaults',
                               user_id=default_user['id'])
            return {
                'success': True,
                'user': {
                    'id': default_user['id'],
                    'username': default_user['username'],
                    'email': default_user['email'],
                    'fullname': default_user['fullname'],
                    'user_type': default_user['user_type'],
                    'premium_status': default_user['premium_status'],
                    'security_clearance': default_user['security_clearance'],
                    'military_access': default_user['military_access'],
                    'status': default_user['status'],
                    'last_login': datetime.now().isoformat(),
                    'created_at': default_user['created_at']
                },
                'source': 'default_users',
                'connection': 'fallback'
            }

    return {
        'success': False,
        'message': 'Credenciales incorrectas'
    }


@auth_bp.route('/login', methods=['GET', 'POST'])
def login():
    """Endpoint de login"""
    # Si ya está logueado, redirigir al dashboard
    if current_user.is_authenticated:
        if session.get('user_type') == 'admin':
            return redirect(url_for('admin.dashboard'))
        return redirect(url_for('user.dashboard'))

    if request.method == 'POST':
        # Detectar si es AJAX o form normal
        is_ajax = request.headers.get('X-Requested-With') == 'XMLHttpRequest' or \
                  'application/x-www-form-urlencoded' in (request.content_type or '') or \
                  'multipart/form-data' in (request.content_type or '') or \
                  request.accept_mimetypes.best == 'application/json'

        action = request.form.get('action', 'login')
        username = sanitize_input(request.form.get('username', ''))
        password = request.form.get('password', '')

        if action == 'login':
            if not username or not password:
                if is_ajax:
                    return jsonify({'success': False, 'message': 'Usuario y contraseña son obligatorios'})
                return render_template('login.html', error='Usuario y contraseña son obligatorios')

            try:
                result = authenticate_user(username, password)

                if result['success']:
                    user_data = result['user']

                    # Buscar o crear usuario en BD para flask-login
                    user = User.query.filter_by(username=username).first()
                    if not user:
                        user = User.query.get(user_data.get('id', 1))
                    if not user:
                        # Crear usuario en BD si viene de defaults
                        user = User(
                            username=user_data['username'],
                            email=user_data['email'],
                            password_hash=hash_password(password),
                            fullname=user_data.get('fullname', ''),
                            user_type=user_data.get('user_type', 'user'),
                            premium_status=user_data.get('premium_status', 'basic'),
                            security_clearance=user_data.get('security_clearance', 'UNCLASSIFIED'),
                            military_access=user_data.get('military_access', False),
                            status='active'
                        )
                        db.session.add(user)
                        db.session.commit()

                    # Login con flask-login
                    login_user(user, remember=True)

                    # Establecer variables de sesión (como en PHP)
                    session['user_id'] = user.id
                    session['logged_in'] = True
                    session['login_time'] = datetime.now().isoformat()
                    session['username'] = user.username
                    session['email'] = user.email
                    session['fullname'] = user.fullname
                    session['user_type'] = user.user_type
                    session['premium_status'] = user.premium_status
                    session['security_clearance'] = user.security_clearance
                    session['military_access'] = user.military_access
                    session.permanent = True

                    log_security_event('LOGIN_SUCCESS',
                                       f'Usuario {username} inició sesión exitosamente',
                                       'info', user.id, request.remote_addr)

                    redirect_url = url_for('admin.dashboard') if user.user_type == 'admin' else url_for('user.dashboard')

                    if is_ajax:
                        return jsonify({
                            'success': True,
                            'message': 'Login exitoso',
                            'redirect': redirect_url,
                            'user': {
                                'username': user.username,
                                'user_type': user.user_type,
                                'premium_status': user.premium_status,
                                'fullname': user.fullname,
                                'security_clearance': user.security_clearance
                            },
                            'source': result.get('source', 'unknown'),
                            'connection': result.get('connection')
                        })
                    return redirect(redirect_url)
                else:
                    log_security_event('LOGIN_FAILED',
                                       f'Intento fallido para usuario: {username} desde IP: {request.remote_addr}',
                                       'warning')
                    if is_ajax:
                        return jsonify({
                            'success': False,
                            'message': result.get('message', 'Credenciales incorrectas')
                        })
                    return render_template('login.html', error=result.get('message', 'Credenciales incorrectas'))

            except Exception as e:
                log_security_event('LOGIN_ERROR',
                                   f'Error en autenticación para {username}: {str(e)}',
                                   'error')
                if is_ajax:
                    return jsonify({
                        'success': False,
                        'message': 'Error interno del servidor. Intente nuevamente.'
                    })
                return render_template('login.html', error='Error interno del servidor')

        elif action == 'register':
            email = sanitize_input(request.form.get('email', ''))
            fullname = sanitize_input(request.form.get('fullname', ''))

            if not username or not password or not email:
                return jsonify({
                    'success': False,
                    'message': 'Todos los campos son obligatorios'
                })

            if not validate_email(email):
                return jsonify({
                    'success': False,
                    'message': 'Email no válido'
                })

            if len(password) < 6:
                return jsonify({
                    'success': False,
                    'message': 'La contraseña debe tener al menos 6 caracteres'
                })

            # Verificar si ya existe
            existing = User.query.filter(
                (User.username == username) | (User.email == email)
            ).first()
            if existing:
                return jsonify({
                    'success': False,
                    'message': 'El usuario o email ya existe'
                })

            try:
                new_user = User(
                    username=username,
                    email=email,
                    password_hash=hash_password(password),
                    fullname=fullname or username,
                    user_type='user',
                    premium_status='basic',
                    security_clearance='UNCLASSIFIED',
                    military_access=False,
                    status='active'
                )
                db.session.add(new_user)
                db.session.commit()

                log_security_event('REGISTER_SUCCESS',
                                   f'Nuevo usuario registrado: {username}',
                                   'info', new_user.id, request.remote_addr)

                return jsonify({
                    'success': True,
                    'message': 'Registro exitoso. Ahora puede iniciar sesión.'
                })
            except Exception as e:
                db.session.rollback()
                return jsonify({
                    'success': False,
                    'message': f'Error en el registro: {str(e)}'
                })

    # GET - Mostrar formulario de login
    message = request.args.get('message', '')
    return render_template('login.html', message=message)


@auth_bp.route('/logout')
@login_required
def logout():
    """Endpoint de logout"""
    username = session.get('username', 'unknown')
    user_id = session.get('user_id')

    log_security_event('LOGOUT', f'Usuario {username} cerró sesión',
                       'info', user_id, request.remote_addr)

    logout_user()
    session.clear()

    return redirect(url_for('auth.login', message='logout_success'))
