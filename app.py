"""
GuardianIA v3.0 FINAL MILITAR - Aplicación Principal Flask
Convertido de PHP a Python/Flask
Base de datos: MySQL via AppServ / phpMyAdmin
Desarrollador: Anderson Mamian Chicangana
"""
from flask import Flask, redirect, url_for
from flask_login import LoginManager
from config import FlaskConfig
from models import db, User
from utils.database import init_database
from utils.security import log_military_event

# Crear aplicación Flask
app = Flask(__name__)
app.config.from_object(FlaskConfig)

# Inicializar extensiones
db.init_app(app)

# Configurar Flask-Login
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'auth.login'
login_manager.login_message = 'Debe iniciar sesión para acceder a esta página'
login_manager.login_message_category = 'warning'


@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))


# ========================================
# REGISTRAR BLUEPRINTS
# ========================================
from blueprints.auth import auth_bp
from blueprints.admin import admin_bp
from blueprints.user import user_bp
from blueprints.chatbot import chatbot_bp
from blueprints.security import security_bp
from blueprints.ai_modules import ai_bp
from blueprints.system import system_bp
from blueprints.pages import pages_bp

app.register_blueprint(auth_bp)
app.register_blueprint(admin_bp)
app.register_blueprint(user_bp)
app.register_blueprint(chatbot_bp)
app.register_blueprint(security_bp)
app.register_blueprint(ai_bp)
app.register_blueprint(system_bp)
app.register_blueprint(pages_bp)


# ========================================
# MANEJADORES DE ERRORES
# ========================================
@app.errorhandler(404)
def not_found(e):
    return redirect(url_for('pages.error_page', message='Página no encontrada'))


@app.errorhandler(500)
def server_error(e):
    return redirect(url_for('pages.error_page', message='Error interno del servidor'))


@app.errorhandler(403)
def forbidden(e):
    return redirect(url_for('pages.error_page', message='Acceso denegado'))


# ========================================
# CONTEXTO GLOBAL PARA TEMPLATES
# ========================================
@app.context_processor
def inject_globals():
    from config import APP_NAME, APP_VERSION, DEVELOPER
    from flask_login import current_user
    from flask import session
    return {
        'app_name': APP_NAME,
        'app_version': APP_VERSION,
        'developer': DEVELOPER,
        'current_year': __import__('datetime').datetime.now().year
    }


# ========================================
# INICIALIZACIÓN
# ========================================
if __name__ == '__main__':
    # Inicializar base de datos
    init_database(app)

    log_military_event('SYSTEM_START', 'GuardianIA v3.0 Flask iniciado')
    print(f"""
    ╔══════════════════════════════════════════════════╗
    ║     GuardianIA v3.0 FINAL MILITAR - Flask        ║
    ║     Desarrollador: Anderson Mamian Chicangana     ║
    ║     Base de datos: MySQL (AppServ/phpMyAdmin)     ║
    ║     URL: http://localhost:5000                     ║
    ╚══════════════════════════════════════════════════╝
    """)

    app.run(host='0.0.0.0', port=8080, debug=True)
