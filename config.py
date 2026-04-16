"""
GuardianIA v3.0 FINAL - Configuración Principal MILITAR MEJORADA
Anderson Mamian Chicangana - Membresía Premium Activada
Sistema con Soporte Dual de Base de Datos + Encriptación Militar
Convertido de PHP a Python/Flask
Base de datos: MySQL via AppServ / phpMyAdmin
"""
import os
import hashlib
import secrets
from datetime import datetime, timedelta
from dotenv import load_dotenv

# Cargar variables de entorno
load_dotenv()

# Zona horaria
TIMEZONE = 'America/Bogota'

# Configuración de la aplicación
APP_NAME = 'GuardianIA v3.0 FINAL MILITAR'
APP_VERSION = '3.0.0-MILITARY'
APP_URL = os.getenv('APP_URL', 'http://localhost:5000')
DEVELOPER = 'Anderson Mamian Chicangana'
DEVELOPER_EMAIL = 'anderson@guardianai.com'

# ========================================
# CONFIGURACIÓN MILITAR DE SEGURIDAD
# ========================================
MILITARY_ENCRYPTION_ENABLED = True
FIPS_140_2_COMPLIANCE = True
QUANTUM_RESISTANCE_ENABLED = True

# Algoritmos de encriptación militar
MILITARY_AES_KEY_SIZE = 256
MILITARY_RSA_KEY_SIZE = 4096
MILITARY_HASH_ALGORITHM = 'sha256'
MILITARY_KDF_ITERATIONS = 100000

# Claves maestras militares
MASTER_ENCRYPTION_KEY = hashlib.sha256(
    f'GuardianIA_v3.0_Anderson_Premium_Military_MasterKey_2024_{datetime.now().strftime("%Y-%m-%d")}'.encode()
).hexdigest()

QUANTUM_SEED_KEY = hashlib.sha256(
    f'QuantumSeed_{DEVELOPER}_{APP_VERSION}'.encode()
).hexdigest()

MILITARY_SALT = hashlib.sha256(
    f'MilitarySalt_GuardianIA_{datetime.now().strftime("%Y-%W")}'.encode()
).hexdigest()

# Perfect Forward Secrecy
PFS_ENABLED = True
KEY_ROTATION_INTERVAL = 3600
SESSION_KEY_LIFETIME = 1800

# ========================================
# CONFIGURACIÓN DE BASE DE DATOS - MySQL AppServ / phpMyAdmin
# ========================================
# Detectar si es local o producción
IS_LOCAL = os.getenv('IS_LOCAL', 'true').lower() == 'true'

if IS_LOCAL:
    # ========================================
    # CONFIGURACIÓN LOCAL (AppServ / XAMPP / WAMP)
    # Conexión a MySQL local vía AppServ con phpMyAdmin
    # ========================================
    DB_PRIMARY_HOST = os.getenv('DB_HOST', 'localhost')
    DB_PRIMARY_USER = os.getenv('DB_USER', 'root')
    DB_PRIMARY_PASS = os.getenv('DB_PASS', '')  # Vacío por defecto en AppServ
    DB_PRIMARY_NAME = os.getenv('DB_NAME', 'guardianai_local')
    DB_PRIMARY_PORT = int(os.getenv('DB_PORT', '3306'))

    # Fallback local (mismas credenciales)
    DB_FALLBACK_HOST = DB_PRIMARY_HOST
    DB_FALLBACK_USER = DB_PRIMARY_USER
    DB_FALLBACK_PASS = DB_PRIMARY_PASS
    DB_FALLBACK_NAME = DB_PRIMARY_NAME
    DB_FALLBACK_PORT = DB_PRIMARY_PORT
else:
    # ========================================
    # CONFIGURACIÓN PRODUCCIÓN (Hosting compartido)
    # ========================================
    DB_PRIMARY_HOST = os.getenv('DB_PRIMARY_HOST', 'localhost')
    DB_PRIMARY_USER = os.getenv('DB_PRIMARY_USER', 'guardia2_ander')
    DB_PRIMARY_PASS = os.getenv('DB_PRIMARY_PASS', '')
    DB_PRIMARY_NAME = os.getenv('DB_PRIMARY_NAME', 'guardia2_guardianai_db')
    DB_PRIMARY_PORT = int(os.getenv('DB_PRIMARY_PORT', '3306'))

    DB_FALLBACK_HOST = os.getenv('DB_FALLBACK_HOST', 'localhost')
    DB_FALLBACK_USER = os.getenv('DB_FALLBACK_USER', 'cpses_gu39cqdp5x')
    DB_FALLBACK_PASS = os.getenv('DB_FALLBACK_PASS', '')
    DB_FALLBACK_NAME = os.getenv('DB_FALLBACK_NAME', 'guardia2_guardianai_db')
    DB_FALLBACK_PORT = int(os.getenv('DB_FALLBACK_PORT', '3306'))

DB_CHARSET = 'utf8mb4'
DB_TIMEOUT = 5
DB_RETRY_ATTEMPTS = 3

# ========================================
# URI de SQLAlchemy - MySQL via AppServ (PyMySQL driver)
# Formato: mysql+pymysql://usuario:contraseña@host:puerto/base_de_datos
# ========================================
SQLALCHEMY_DATABASE_URI = os.getenv(
    'DATABASE_URL',
    f'mysql+pymysql://{DB_PRIMARY_USER}:{DB_PRIMARY_PASS}@{DB_PRIMARY_HOST}:{DB_PRIMARY_PORT}/{DB_PRIMARY_NAME}?charset={DB_CHARSET}'
)

# URI de fallback
SQLALCHEMY_DATABASE_URI_FALLBACK = (
    f'mysql+pymysql://{DB_FALLBACK_USER}:{DB_FALLBACK_PASS}@{DB_FALLBACK_HOST}:{DB_FALLBACK_PORT}/{DB_FALLBACK_NAME}?charset={DB_CHARSET}'
)

SQLALCHEMY_TRACK_MODIFICATIONS = False
# Engine options - se configuran dinámicamente según el tipo de BD
_db_uri = os.getenv('DATABASE_URL', SQLALCHEMY_DATABASE_URI)
if 'sqlite' in _db_uri:
    SQLALCHEMY_ENGINE_OPTIONS = {
        'pool_pre_ping': True,
    }
else:
    SQLALCHEMY_ENGINE_OPTIONS = {
        'pool_pre_ping': True,
        'pool_recycle': 3600,
        'pool_size': 10,
        'max_overflow': 20,
        'connect_args': {
            'connect_timeout': DB_TIMEOUT
        }
    }

# Seguridad
SECRET_KEY = os.getenv('SECRET_KEY', secrets.token_hex(32))
ENCRYPTION_KEY = MASTER_ENCRYPTION_KEY
SESSION_LIFETIME = 3600 * 8  # 8 horas
CSRF_TOKEN_LIFETIME = 1800  # 30 minutos
MAX_LOGIN_ATTEMPTS = 3
LOGIN_LOCKOUT_TIME = 1800  # 30 minutos

# Premium
PREMIUM_ENABLED = True
PREMIUM_USER = 'anderson'
MONTHLY_PRICE = 60000
ANNUAL_DISCOUNT = 0.15
PREMIUM_FEATURES = {
    'ai_antivirus': True,
    'quantum_encryption': True,
    'military_encryption': True,
    'predictive_analysis': True,
    'ai_vpn': True,
    'advanced_chatbot': True,
    'real_time_monitoring': True,
    'unlimited_conversations': True,
    'priority_support': True,
    'fips_compliance': True,
    'quantum_resistance': True
}

# Configuración de IA
AI_DETECTION_THRESHOLD = 0.85
CONSCIOUSNESS_THRESHOLD = 0.7
THREAT_LEVEL_HIGH = 8
AI_LEARNING_ENABLED = True
NEURAL_NETWORK_DEPTH = 7

# IA Mejorada con Redes Neuronales Profundas
DEEP_LEARNING_ENABLED = True
ENHANCED_NEURAL_NETWORK_DEPTH = 12
MAX_NEURONS_PER_LAYER = 2048
BATCH_NORMALIZATION = True
DROPOUT_REGULARIZATION = True
LEARNING_RATE_DECAY = True
EARLY_STOPPING_PATIENCE = 50
GRADIENT_CLIPPING = True
ADAPTIVE_LEARNING_RATE = True

# Procesamiento Cuántico para IA
QUANTUM_AI_ENABLED = True
QUANTUM_ENHANCEMENT_LEVEL = 'HIGH'
QUANTUM_PARALLELISM = True
QUANTUM_SUPERPOSITION = True
QUANTUM_ENTANGLEMENT = True
QUANTUM_COHERENCE_TIME = 100
QUANTUM_ERROR_CORRECTION = True
QUANTUM_VOLUME = 1024

# Algoritmos de Aprendizaje Avanzados
FEDERATED_LEARNING = True
META_LEARNING = True
TRANSFER_LEARNING = True
CONTINUAL_LEARNING = True
REINFORCEMENT_LEARNING = True
SELF_SUPERVISED_LEARNING = True
FEW_SHOT_LEARNING = True
ZERO_SHOT_LEARNING = True

# Optimizadores
OPTIMIZER_TYPE = 'adam'
MOMENTUM = 0.9
BETA1 = 0.9
BETA2 = 0.999
EPSILON = 1e-8
WEIGHT_DECAY = 0.0001

# Regularización
L1_REGULARIZATION = 0.0001
L2_REGULARIZATION = 0.001
ELASTIC_NET_RATIO = 0.5
DROPOUT_RATE = 0.2
MAX_NORM = 1.0

# VPN
VPN_ENABLED = True
VPN_SERVERS = {
    'colombia-bogota': 'Bogota, Colombia',
    'usa-miami': 'Miami, USA',
    'spain-madrid': 'Madrid, España',
    'japan-tokyo': 'Tokio, Japón',
    'military-secure': 'Servidor Militar Seguro'
}

# Logs
LOG_LEVEL = 'INFO'
LOG_ROTATION_SIZE = 10485760
LOG_RETENTION_DAYS = 90

# Usuarios por defecto (si no hay base de datos o tabla vacía)
DEFAULT_USERS = {
    'anderson': {
        'id': 1,
        'username': 'anderson',
        'password': os.getenv('DEFAULT_USER_ANDERSON_PASS', 'Ander12345@'),
        'email': 'anderson@guardianai.com',
        'fullname': 'Anderson Mamian Chicangana',
        'user_type': 'admin',
        'premium_status': 'premium',
        'security_clearance': 'TOP_SECRET',
        'military_access': True,
        'status': 'active',
        'created_at': '2025-08-23 00:00:00'
    },
    'admin': {
        'id': 2,
        'username': 'admin',
        'password': os.getenv('DEFAULT_USER_ADMIN_PASS', 'admin123'),
        'email': 'admin@guardianai.com',
        'fullname': 'Administrador GuardianIA',
        'user_type': 'admin',
        'premium_status': 'basic',
        'security_clearance': 'SECRET',
        'military_access': False,
        'status': 'active',
        'created_at': '2025-08-23 00:00:00'
    }
}

# Configuración militar adicional
MILITARY_CONFIG = {
    'classification_levels': ['UNCLASSIFIED', 'CONFIDENTIAL', 'SECRET', 'TOP_SECRET'],
    'military_algorithms': ['AES-256-GCM', 'RSA-4096', 'SHA-512', 'ECDSA-P384'],
    'post_quantum_algorithms': ['CRYSTALS-Kyber', 'CRYSTALS-Dilithium', 'SPHINCS+'],
    'audit_enabled': True,
    'compliance_standards': ['FIPS-140-2', 'NIST-SP-800-53', 'ISO-27001']
}

# Métricas de evaluación
METRICS_CONFIG = {
    'precision': True,
    'recall': True,
    'f1_score': True,
    'auc_roc': True,
    'confusion_matrix': True,
    'cross_entropy': True
}

# Directorios necesarios
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
REQUIRED_DIRS = ['logs', 'uploads', 'cache', 'military', 'keys', 'compositions', 'saved_compositions', 'models/checkpoints', 'models/pretrained']

for d in REQUIRED_DIRS:
    dir_path = os.path.join(BASE_DIR, d)
    os.makedirs(dir_path, exist_ok=True)


class FlaskConfig:
    """Configuración para Flask con MySQL AppServ"""
    SECRET_KEY = SECRET_KEY
    SQLALCHEMY_DATABASE_URI = SQLALCHEMY_DATABASE_URI
    SQLALCHEMY_TRACK_MODIFICATIONS = SQLALCHEMY_TRACK_MODIFICATIONS
    SQLALCHEMY_ENGINE_OPTIONS = SQLALCHEMY_ENGINE_OPTIONS
    PERMANENT_SESSION_LIFETIME = timedelta(hours=8)
    SESSION_COOKIE_NAME = 'GUARDIANAI_MILITARY_SESSION'
    SESSION_COOKIE_HTTPONLY = True
    SESSION_COOKIE_SAMESITE = 'Lax'
    WTF_CSRF_TIME_LIMIT = CSRF_TOKEN_LIFETIME
    MAX_CONTENT_LENGTH = 16 * 1024 * 1024  # 16MB max upload
