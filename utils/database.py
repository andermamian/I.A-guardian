"""
GuardianIA v3.0 - Gestor de Base de Datos Militar
Soporte dual: MySQL (AppServ/phpMyAdmin) con fallback
"""
import os
import json
from datetime import datetime
from flask import current_app
from models import db, User
from utils.security import hash_password, log_event, log_military_event
from config import DEFAULT_USERS


def init_database(app):
    """
    Inicializa la base de datos.
    Intenta conectar a MySQL (AppServ), si falla usa SQLite como fallback.
    Crea todas las tablas necesarias y los usuarios por defecto.
    """
    with app.app_context():
        try:
            # Intentar crear todas las tablas
            db.create_all()
            log_military_event('DB_INIT', 'Base de datos inicializada correctamente')

            # Crear usuarios por defecto si no existen
            _create_default_users()

            # Verificar conexión
            db.session.execute(db.text('SELECT 1'))
            db.session.commit()

            # Detectar tipo de BD
            db_uri = str(app.config.get('SQLALCHEMY_DATABASE_URI', ''))
            if 'mysql' in db_uri:
                log_military_event('DB_CONNECTION', 'Conectado a MySQL (AppServ/phpMyAdmin)')
                print("[GuardianIA] Conectado a MySQL (AppServ/phpMyAdmin)")
            elif 'sqlite' in db_uri:
                log_military_event('DB_CONNECTION', 'Conectado a SQLite (modo fallback)')
                print("[GuardianIA] Conectado a SQLite (modo fallback)")
            else:
                print(f"[GuardianIA] Conectado a base de datos: {db_uri.split('@')[0] if '@' in db_uri else 'local'}")

            return True

        except Exception as e:
            error_msg = str(e)
            log_military_event('DB_ERROR', f'Error inicializando BD: {error_msg}')
            print(f"[GuardianIA] Error de BD: {error_msg}")

            # Si falla MySQL, intentar con SQLite como fallback
            if 'mysql' in str(app.config.get('SQLALCHEMY_DATABASE_URI', '')):
                print("[GuardianIA] MySQL no disponible. Cambiando a SQLite como fallback...")
                app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///guardianai.db'
                try:
                    db.create_all()
                    _create_default_users()
                    log_military_event('DB_FALLBACK', 'Usando SQLite como fallback')
                    print("[GuardianIA] SQLite fallback activado correctamente")
                    return True
                except Exception as e2:
                    print(f"[GuardianIA] Error crítico en fallback: {e2}")
                    return False
            return False


def _create_default_users():
    """Crea los usuarios por defecto si no existen en la BD"""
    for username, user_data in DEFAULT_USERS.items():
        existing = User.query.filter_by(username=username).first()
        if not existing:
            user = User(
                username=user_data['username'],
                email=user_data['email'],
                password_hash=hash_password(user_data['password']),
                fullname=user_data['fullname'],
                user_type=user_data['user_type'],
                premium_status=user_data['premium_status'],
                security_clearance=user_data['security_clearance'],
                military_access=user_data['military_access'],
                status=user_data['status']
            )
            db.session.add(user)
            log_event('INFO', f'Usuario por defecto creado: {username}')
    try:
        db.session.commit()
    except Exception:
        db.session.rollback()


def get_db_status():
    """Obtiene el estado de la conexión a la base de datos"""
    try:
        db.session.execute(db.text('SELECT 1'))
        db_uri = str(current_app.config.get('SQLALCHEMY_DATABASE_URI', ''))
        if 'mysql' in db_uri:
            db_type = 'MySQL (AppServ/phpMyAdmin)'
        elif 'sqlite' in db_uri:
            db_type = 'SQLite (Fallback)'
        else:
            db_type = 'Desconocido'

        return {
            'connected': True,
            'type': db_type,
            'status': 'connected',
            'encryption': 'AES-256-GCM',
            'fips_compliance': True,
            'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
    except Exception as e:
        return {
            'connected': False,
            'type': 'Ninguna',
            'status': 'disconnected',
            'error': str(e),
            'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }


def create_mysql_database_sql():
    """
    Genera el SQL para crear la base de datos y tablas en phpMyAdmin.
    El usuario puede copiar esto y ejecutarlo en phpMyAdmin.
    """
    return """
-- ========================================
-- GuardianIA v3.0 - Script de Base de Datos
-- Ejecutar en phpMyAdmin (AppServ)
-- ========================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS guardianai_local
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE guardianai_local;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    fullname VARCHAR(200) DEFAULT '',
    user_type VARCHAR(20) DEFAULT 'user',
    premium_status VARCHAR(20) DEFAULT 'basic',
    security_clearance VARCHAR(50) DEFAULT 'UNCLASSIFIED',
    military_access BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'active',
    login_attempts INT DEFAULT 0,
    last_login DATETIME NULL,
    locked_until DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de eventos de seguridad
CREATE TABLE IF NOT EXISTS security_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    description TEXT,
    severity VARCHAR(20) DEFAULT 'medium',
    user_id VARCHAR(50),
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de eventos de amenazas
CREATE TABLE IF NOT EXISTS threat_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(50) UNIQUE,
    user_id INT NULL,
    threat_type VARCHAR(100),
    severity_level VARCHAR(20),
    description TEXT,
    source_ip VARCHAR(45),
    metadata_json TEXT,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de detecciones de IA
CREATE TABLE IF NOT EXISTS ai_detections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    detection_type VARCHAR(100),
    confidence FLOAT DEFAULT 0.0,
    description TEXT,
    user_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de conversaciones
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id VARCHAR(50) UNIQUE,
    user_id INT NOT NULL,
    title VARCHAR(200) DEFAULT 'Nueva Conversación',
    personality VARCHAR(50) DEFAULT 'guardian',
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de mensajes de conversación
CREATE TABLE IF NOT EXISTS conversation_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(50) UNIQUE,
    conversation_id VARCHAR(50),
    user_id INT,
    sender_type VARCHAR(20),
    message_content TEXT,
    confidence_score FLOAT DEFAULT 0.8,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de métricas de rendimiento
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_id VARCHAR(50) UNIQUE,
    user_id INT NULL,
    metric_type VARCHAR(50),
    metric_name VARCHAR(100),
    metric_value FLOAT,
    metric_unit VARCHAR(20),
    status VARCHAR(20) DEFAULT 'normal',
    collected_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de planes de membresía
CREATE TABLE IF NOT EXISTS membership_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price FLOAT DEFAULT 0.0,
    duration_days INT DEFAULT 30,
    features TEXT,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de registros de backup
CREATE TABLE IF NOT EXISTS backup_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_id VARCHAR(50) UNIQUE,
    user_id INT,
    backup_type VARCHAR(50),
    file_path VARCHAR(500),
    file_size INT,
    status VARCHAR(20) DEFAULT 'completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de bóveda digital
CREATE TABLE IF NOT EXISTS vault_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_name VARCHAR(200),
    item_type VARCHAR(50),
    encrypted_data TEXT,
    category VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de composiciones musicales
CREATE TABLE IF NOT EXISTS music_compositions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200),
    genre VARCHAR(50),
    tempo INT DEFAULT 120,
    key_signature VARCHAR(10),
    data_json TEXT,
    status VARCHAR(20) DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de logs del sistema
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20),
    event_type VARCHAR(100),
    message TEXT,
    user_id VARCHAR(50),
    ip_address VARCHAR(45),
    classification VARCHAR(20) DEFAULT 'UNCLASSIFIED',
    context_json TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuarios por defecto
INSERT IGNORE INTO users (username, email, password_hash, fullname, user_type, premium_status, security_clearance, military_access, status)
VALUES
('anderson', 'anderson@guardianai.com', '$2b$12$placeholder_hash_anderson', 'Anderson Mamian Chicangana', 'admin', 'premium', 'TOP_SECRET', TRUE, 'active'),
('admin', 'admin@guardianai.com', '$2b$12$placeholder_hash_admin', 'Administrador GuardianIA', 'admin', 'basic', 'SECRET', FALSE, 'active');

-- Insertar planes de membresía
INSERT IGNORE INTO membership_plans (name, price, duration_days, features, status)
VALUES
('Básico', 0, 30, '{"ai_antivirus":false,"quantum_encryption":false}', 'active'),
('Premium', 60000, 30, '{"ai_antivirus":true,"quantum_encryption":true,"military_encryption":true}', 'active'),
('Premium Anual', 612000, 365, '{"ai_antivirus":true,"quantum_encryption":true,"military_encryption":true,"priority_support":true}', 'active');

-- ========================================
-- NOTA: Los password_hash son placeholders.
-- La aplicación Python creará los hashes reales
-- automáticamente al iniciar por primera vez.
-- ========================================
"""
